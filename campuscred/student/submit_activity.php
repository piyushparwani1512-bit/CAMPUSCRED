<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

$errors = [];
$event_name = '';
$category = 'Technical';
$event_date = '';
$organizer = '';
$department = $user['department'] ?? 'Computer Science & Engineering';
$participation_type = 'Participant';
$achievement_description = '';

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
        $errors[] = 'Please enter a valid event date in YYYY-MM-DD format.';
    } elseif ($parsedDate > new DateTime()) {
        $errors[] = 'Event date cannot be in the future.';
    }

    if ($organizer === '' || mb_strlen($organizer) > 200) {
        $errors[] = 'Organizer / Host institution is required (max 200 characters).';
    }
    if ($department === '') {
        $errors[] = 'Please specify the conducting department.';
    }

    if (empty($errors)) {
        if (is_duplicate_submission($pdo, $studentId, $event_name, $event_date)) {
            $errors[] = 'A submission with this Event Name and Date has already been submitted for your account. Duplicate submissions are not permitted.';
        }
    }

    $file = $_FILES['certificate'] ?? null;
    $storedFilename = null;
    $fileHash = null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please attach an official certificate file (PDF, JPG, JPEG, or PNG).';
    } elseif ($file['size'] > MAX_UPLOAD_BYTES) {
        $errors[] = 'Certificate file exceeds maximum limit of 5 MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ALLOWED_MIME_TYPES;

        if (!isset($allowed[$mime])) {
            $errors[] = 'Security verification failed: File content type (' . e($mime) . ') is not an allowed certificate document (only PDF, JPG, and PNG are permitted).';
        } else {
            $ext = $allowed[$mime];
            $storedFilename = bin2hex(random_bytes(20)) . '.' . $ext;
            $fileHash = hash_file('sha256', $file['tmp_name']);
        }
    }

    if (empty($errors) && $storedFilename) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $storedFilename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = 'Failed to store uploaded certificate file on server. Check directory permissions.';
        } else {
            try {
                $submissionCode = generate_submission_code();
                $verificationUuid = generate_verification_uuid();
                $origName = basename($file['name']);

                $stmt = $pdo->prepare("
                    INSERT INTO activities (
                        submission_code, student_id, event_name, category, event_date, organizer, department,
                        participation_type, achievement_description, certificate_path, certificate_original_name,
                        certificate_hash, status, verification_uuid, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Verification', ?, NOW()
                    )
                ");

                $stmt->execute([
                    $submissionCode,
                    $studentId,
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
                    $verificationUuid
                ]);

                $newActivityId = (int)$pdo->lastInsertId();

                log_verification_action(
                    $pdo,
                    $newActivityId,
                    null,
                    'Submitted',
                    null,
                    'Pending Verification',
                    'Initial student submission with proof file (' . $origName . ')'
                );

                set_flash('success', "Achievement successfully submitted! Your Tracking ID is {$submissionCode}. It is now in the queue for faculty verification.");
                header('Location: ' . base_url('student/my_activities.php'));
                exit;

            } catch (Throwable $e) {
                if (is_file($targetPath)) {
                    @unlink($targetPath);
                }
                $errors[] = 'Database error while saving submission: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Submit Achievement · Student Workspace';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-2">
  <div class="col-lg-10 col-xl-9">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(base_url('student/dashboard.php')) ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Submit Achievement</li>
          </ol>
        </nav>
        <h3 class="fw-bolder text-primary mb-0"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Submit Activity or Achievement</h3>
      </div>
      <a href="<?= e(base_url('student/my_activities.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-check me-1"></i>My Submissions
      </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
      <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
        <span class="fw-bold text-secondary small text-uppercase">
          <i class="bi bi-info-circle-fill text-primary me-1"></i>Official Activity Verification Form
        </span>
        <span class="badge bg-warning-subtle text-warning-emphasis border">
          Initial Status: Pending Verification
        </span>
      </div>

      <div class="card-body p-4 bg-white">
        
        <?php if ($errors): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Submission could not be completed:</strong>
            <ul class="mb-0 mt-1 ps-3">
              <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="submit_activity.php" enctype="multipart/form-data" class="needs-validation">
          <?= csrf_field() ?>

          <div class="bg-light p-3 rounded-3 mb-4 border">
            <div class="text-uppercase fw-bold text-muted small mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
              <i class="bi bi-person-badge-fill me-1"></i>Student Identity (Authenticated Account)
            </div>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Student Name</label>
                <input type="text" class="form-control form-control-sm bg-white" value="<?= e($user['name']) ?>" readonly disabled>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small mb-1">College ID / Enrollment</label>
                <input type="text" class="form-control form-control-sm bg-white fw-bold text-primary" value="<?= e($user['student_id_code'] ?: 'SKIT/STU/' . $user['id']) ?>" readonly disabled>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Registered Department</label>
                <input type="text" class="form-control form-control-sm bg-white" value="<?= e($user['department']) ?>" readonly disabled>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold small text-secondary">
                Event / Activity Name <span class="text-danger">*</span>
              </label>
              <input type="text" name="event_name" class="form-control" placeholder="e.g. Smart India Hackathon 2026, Pravah Cultural Fest, IEEE Paper" value="<?= e($event_name) ?>" required maxlength="200">
              <div class="form-text small">Enter full official title of the conference, contest, workshop or fest.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">
                Activity Category <span class="text-danger">*</span>
              </label>
              <select name="category" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">
                Event Date <span class="text-danger">*</span>
              </label>
              <input type="date" name="event_date" class="form-control" value="<?= e($event_date) ?>" max="<?= date('Y-m-d') ?>" required>
              <div class="form-text small">Must not be in the future.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">
                Participation Type / Role <span class="text-danger">*</span>
              </label>
              <select name="participation_type" class="form-select" required>
                <?php foreach ($participationTypes as $ptype): ?>
                  <option value="<?= e($ptype) ?>" <?= $participation_type === $ptype ? 'selected' : '' ?>><?= e($ptype) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-secondary">
                Conducting Department <span class="text-danger">*</span>
              </label>
              <select name="department" class="form-select" required>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= e($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label fw-semibold small text-secondary">
                Event Organizer / Host Institution <span class="text-danger">*</span>
              </label>
              <input type="text" name="organizer" class="form-control" placeholder="e.g. Ministry of Education, IIT Bombay, ACM Student Chapter, SKIT Jaipur" value="<?= e($organizer) ?>" required maxlength="200">
            </div>

            <div class="col-md-12">
              <label class="form-label fw-semibold small text-secondary">
                Achievement Description / Project Synopsis
              </label>
              <textarea name="achievement_description" class="form-control" rows="3" placeholder="Briefly summarize your role, project, prize rank, or competition level..."><?= e($achievement_description) ?></textarea>
              <div class="form-text small">Optional but strongly recommended for quick faculty verification.</div>
            </div>
          </div>

          <div class="p-3 border rounded-3 mb-4 bg-light bg-opacity-25">
            <label class="form-label fw-bold text-dark mb-1">
              <i class="bi bi-file-earmark-arrow-up text-primary me-1"></i>Upload Certificate Proof <span class="text-danger">*</span>
            </label>
            <p class="text-muted small mb-2">
              Upload a clear, legible digital certificate or official scanned letter. Permitted formats: <strong>PDF, JPG, JPEG, PNG</strong>. Maximum size: <strong>5 MB</strong>.
            </p>
            <input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
            <div class="form-text small text-secondary mt-1">
              <i class="bi bi-shield-lock me-1"></i>Security note: File content is cryptographically inspected on server. Executable files are blocked.
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 pt-2 border-top">
            <a href="<?= e(base_url('student/dashboard.php')) ?>" class="btn btn-outline-secondary px-4">
              Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
              <i class="bi bi-check2-circle me-1"></i>Submit for Verification
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
