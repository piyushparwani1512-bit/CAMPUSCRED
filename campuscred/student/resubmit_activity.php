<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

$activityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
if (!$activityId) {
    set_flash('error', 'Invalid activity ID specified for resubmission.');
    header('Location: ' . base_url('student/my_activities.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, u.name as reviewer_name 
    FROM activities a 
    LEFT JOIN users u ON u.id = a.reviewer_id 
    WHERE a.id = ? AND a.student_id = ?
");
$stmt->execute([$activityId, $studentId]);
$activity = $stmt->fetch();

if (!$activity) {
    set_flash('error', 'Activity record not found or does not belong to your account.');
    header('Location: ' . base_url('student/my_activities.php'));
    exit;
}

if ($activity['status'] !== 'Rejected') {
    set_flash('error', 'Only rejected activities can be resubmitted. This record status is ' . $activity['status']);
    header('Location: ' . base_url('student/my_activities.php'));
    exit;
}

$errors = [];
$event_name = $activity['event_name'];
$category = $activity['category'];
$event_date = $activity['event_date'];
$organizer = $activity['organizer'];
$department = $activity['department'];
$participation_type = $activity['participation_type'];
$achievement_description = $activity['achievement_description'] ?? '';
$resubmission_notes = '';

$categories = ['Academic', 'Technical', 'Cultural', 'Sports', 'Extracurricular', 'Other'];
$participationTypes = ['Winner', 'Runner-up', '3rd Place', 'Finalist', 'Participant', 'Volunteer', 'Organizer', 'Presenter'];
$departments = [
    'Computer Science & Engineering',
    'Information Technology',
    'Electronics & Communication',
    'Electrical Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Management Studies',
    'Other / Central Unit'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $event_name = trim($_POST['event_name'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $event_date = trim($_POST['event_date'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $participation_type = $_POST['participation_type'] ?? 'Participant';
    $achievement_description = trim($_POST['achievement_description'] ?? '');
    $resubmission_notes = trim($_POST['resubmission_notes'] ?? '');

    if ($event_name === '' || mb_strlen($event_name) > 200) {
        $errors[] = 'Event or activity name is required (max 200 characters).';
    }
    if (!in_array($category, $categories, true)) {
        $errors[] = 'Please select a valid activity category.';
    }
    if (!in_array($participation_type, $participationTypes, true)) {
        $errors[] = 'Please select a valid participation type.';
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $event_date);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $event_date) {
        $errors[] = 'Please enter a valid event date.';
    } elseif ($parsedDate > new DateTime()) {
        $errors[] = 'Event date cannot be in the future.';
    }

    if ($organizer === '' || mb_strlen($organizer) > 200) {
        $errors[] = 'Organizer / Host institution is required.';
    }

    $file = $_FILES['certificate'] ?? null;
    $hasNewFile = $file && $file['error'] === UPLOAD_ERR_OK;
    $storedFilename = $activity['certificate_path'];
    $origName = $activity['certificate_original_name'];
    $fileHash = $activity['certificate_hash'];

    if ($hasNewFile) {
        if ($file['size'] > MAX_UPLOAD_BYTES) {
            $errors[] = 'New certificate file exceeds 5 MB limit.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ALLOWED_MIME_TYPES;

            if (!isset($allowed[$mime])) {
                $errors[] = 'Only PDF, JPG, and PNG documents are supported.';
            } else {
                $ext = $allowed[$mime];
                $storedFilename = bin2hex(random_bytes(20)) . '.' . $ext;
                $origName = basename($file['name']);
                $fileHash = hash_file('sha256', $file['tmp_name']);
            }
        }
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/../uploads';
        $uploadOk = true;

        if ($hasNewFile) {
            $targetPath = $uploadDir . '/' . $storedFilename;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $errors[] = 'Failed to save corrected certificate file.';
                $uploadOk = false;
            }
        }

        if ($uploadOk) {
            try {
                $updateStmt = $pdo->prepare("
                    UPDATE activities 
                    SET 
                        event_name = ?,
                        category = ?,
                        event_date = ?,
                        organizer = ?,
                        department = ?,
                        participation_type = ?,
                        achievement_description = ?,
                        certificate_path = ?,
                        certificate_original_name = ?,
                        certificate_hash = ?,
                        status = 'Resubmitted / Pending Review',
                        resubmission_count = resubmission_count + 1,
                        updated_at = NOW()
                    WHERE id = ? AND student_id = ?
                ");

                $updateStmt->execute([
                    $event_name,
                    $category,
                    $event_date,
                    $organizer,
                    $department,
                    $participation_type,
                    $achievement_description !== '' ? $achievement_description : null,
                    $storedFilename,
                    $origName,
                    $fileHash,
                    $activityId,
                    $studentId
                ]);

                $auditReason = "Student resubmitted corrected entry. (Previous rejection: " . ($activity['rejection_reason'] ?: 'None') . ")";
                if ($resubmission_notes !== '') {
                    $auditReason .= " — Student note: " . $resubmission_notes;
                }

                log_verification_action(
                    $pdo,
                    $activityId,
                    null,
                    'Resubmitted',
                    'Rejected',
                    'Resubmitted / Pending Review',
                    $auditReason
                );

                set_flash('success', 'Your achievement has been resubmitted successfully with corrections and sent to the faculty review queue.');
                header('Location: ' . base_url('student/my_activities.php'));
                exit;

            } catch (Throwable $e) {
                $errors[] = 'Database update failed: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Resubmit Achievement · ' . e($activity['submission_code']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-2">
  <div class="col-lg-10 col-xl-9">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(base_url('student/dashboard.php')) ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= e(base_url('student/my_activities.php')) ?>" class="text-decoration-none">My Activities</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resubmit</li>
          </ol>
        </nav>
        <h3 class="fw-bolder text-warning-emphasis mb-0">
          <i class="bi bi-arrow-repeat me-2"></i>Resubmit Rejected Achievement
        </h3>
      </div>
      <a href="<?= e(base_url('student/my_activities.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Cancel &amp; Return
      </a>
    </div>

    <div class="card border-danger shadow-sm rounded-4 mb-4">
      <div class="card-header bg-danger text-white py-2 px-3 fw-bold small d-flex justify-content-between align-items-center">
        <span><i class="bi bi-exclamation-octagon-fill me-1"></i>Official Faculty Rejection Notice</span>
        <span>Tracking: <code><?= e($activity['submission_code']) ?></code></span>
      </div>
      <div class="card-body p-3 bg-danger-subtle bg-opacity-25">
        <div class="text-danger fw-bold small mb-1">
          Reviewer: <?= e($activity['reviewer_name'] ?: 'Department Review Committee') ?> 
          &middot; Reviewed on <?= format_display_datetime($activity['reviewed_at']) ?>
        </div>
        <p class="mb-2 text-dark fs-6 fst-italic">
          &ldquo;<?= e($activity['rejection_reason'] ?: 'Certificate illegible or missing mandatory verification seal.') ?>&rdquo;
        </p>
        <div class="small text-secondary">
          <i class="bi bi-info-circle me-1"></i>Please address the remarks above by updating the event information or uploading a verified, clear replacement certificate scan.
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
      <div class="card-header bg-white p-3 border-bottom">
        <span class="fw-bold text-secondary small text-uppercase">
          <i class="bi bi-pencil-square text-primary me-1"></i>Update Submission Details
        </span>
      </div>

      <div class="card-body p-4 bg-white">
        
        <?php if ($errors): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Please resolve the following issues:</strong>
            <ul class="mb-0 mt-1 ps-3">
              <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="resubmit_activity.php" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="activity_id" value="<?= (int)$activity['id'] ?>">

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold small text-secondary">Event / Activity Name <span class="text-danger">*</span></label>
              <input type="text" name="event_name" class="form-control" value="<?= e($event_name) ?>" required maxlength="200">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">Activity Category <span class="text-danger">*</span></label>
              <select name="category" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">Event Date <span class="text-danger">*</span></label>
              <input type="date" name="event_date" class="form-control" value="<?= e($event_date) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">Participation Type <span class="text-danger">*</span></label>
              <select name="participation_type" class="form-select" required>
                <?php foreach ($participationTypes as $ptype): ?>
                  <option value="<?= e($ptype) ?>" <?= $participation_type === $ptype ? 'selected' : '' ?>><?= e($ptype) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">Department <span class="text-danger">*</span></label>
              <select name="department" class="form-select" required>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= e($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label fw-semibold small text-secondary">Organizer / Host Institution <span class="text-danger">*</span></label>
              <input type="text" name="organizer" class="form-control" value="<?= e($organizer) ?>" required maxlength="200">
            </div>

            <div class="col-md-12">
              <label class="form-label fw-semibold small text-secondary">Achievement Description</label>
              <textarea name="achievement_description" class="form-control" rows="3"><?= e($achievement_description) ?></textarea>
            </div>
          </div>

          <div class="p-3 border rounded-3 mb-3 bg-light bg-opacity-50">
            <label class="form-label fw-bold text-dark mb-1">
              <i class="bi bi-file-earmark-arrow-up text-primary me-1"></i>Upload Corrected Certificate Proof
            </label>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="text-muted small">Current stored file:</span>
              <a href="<?= e(base_url('certificate.php?id=' . (int)$activity['id'])) ?>" target="_blank" class="badge bg-secondary text-decoration-none">
                <i class="bi bi-eye me-1"></i><?= e($activity['certificate_original_name']) ?>
              </a>
            </div>
            <input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            <div class="form-text small text-secondary mt-1">
              Leave empty if you only corrected text fields and do not need to replace the attached document.
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">
              Correction Note for Reviewer (Optional)
            </label>
            <input type="text" name="resubmission_notes" class="form-control" placeholder="e.g. Re-scanned high-resolution copy with college seal clearly visible." value="<?= e($resubmission_notes) ?>">
          </div>

          <div class="d-flex justify-content-end gap-2 pt-2 border-top">
            <a href="<?= e(base_url('student/my_activities.php')) ?>" class="btn btn-outline-secondary px-4">Cancel</a>
            <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm text-dark">
              <i class="bi bi-check2-all me-1"></i>Submit Resubmission for Re-Verification
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
