<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminUser = current_user();
$adminId = (int)$adminUser['id'];

// Handle Verification Action (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activity_id'], $_POST['decision'])) {
    verify_csrf();
    $activityId = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
    $decision = trim($_POST['decision'] ?? '');
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    $reviewNotes = trim($_POST['review_notes'] ?? '');

    if (!$activityId || !in_array($decision, ['Verified', 'Rejected'], true)) {
        set_flash('error', 'Invalid verification action request.');
    } elseif ($decision === 'Rejected' && $rejectionReason === '') {
        set_flash('error', 'A formal rejection reason is mandatory when declining an achievement proof.');
    } else {
        $stmtCheck = $pdo->prepare("SELECT id, status, event_name, student_id, submission_code FROM activities WHERE id = ?");
        $stmtCheck->execute([$activityId]);
        $currentAct = $stmtCheck->fetch();

        if (!$currentAct) {
            set_flash('error', 'Activity record does not exist.');
        } else {
            $prevStatus = $currentAct['status'];
            $newStatus = $decision;

            if ($decision === 'Verified') {
                $stmtUpdate = $pdo->prepare("
                    UPDATE activities 
                    SET 
                        status = 'Verified',
                        reviewer_id = ?,
                        rejection_reason = NULL,
                        review_notes = ?,
                        reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$adminId, $reviewNotes !== '' ? $reviewNotes : 'Verified genuine by faculty admin.', $activityId]);

                log_verification_action(
                    $pdo,
                    $activityId,
                    $adminId,
                    'Verified',
                    $prevStatus,
                    'Verified',
                    $reviewNotes !== '' ? $reviewNotes : 'Verified genuine against institutional criteria'
                );

                set_flash('success', "Submission {$currentAct['submission_code']} successfully VERIFIED! Digital QR credential has been activated.");

            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE activities 
                    SET 
                        status = 'Rejected',
                        reviewer_id = ?,
                        rejection_reason = ?,
                        review_notes = ?,
                        reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$adminId, $rejectionReason, $reviewNotes !== '' ? $reviewNotes : null, $activityId]);

                log_verification_action(
                    $pdo,
                    $activityId,
                    $adminId,
                    'Rejected',
                    $prevStatus,
                    'Rejected',
                    $rejectionReason
                );

                set_flash('error', "Submission {$currentAct['submission_code']} marked as REJECTED. Student can view feedback and resubmit.");
            }
        }
    }

    header('Location: ' . base_url('admin/verify.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));
    exit;
}

$searchQuery = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? 'Pending');
$filterDept = trim($_GET['department'] ?? '');
$filterCat = trim($_GET['category'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$allowedStatuses = ['Pending', 'Pending Verification', 'Resubmitted / Pending Review', 'Verified', 'Rejected', 'All'];
$categories = ['Academic', 'Technical', 'Cultural', 'Sports', 'Extracurricular', 'Other'];
$departments = [
    'Computer Science & Engineering',
    'Information Technology',
    'Electronics & Communication',
    'Electrical Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Management Studies'
];

$countPending = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Pending Verification'")->fetchColumn();
$countResubmitted = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Resubmitted / Pending Review'")->fetchColumn();
$countVerified = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Verified'")->fetchColumn();
$countRejected = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Rejected'")->fetchColumn();
$countAll = $countPending + $countResubmitted + $countVerified + $countRejected;

$sql = "
    SELECT 
        a.*, 
        u.name as student_name, u.email as student_email, u.student_id_code,
        u.department as student_dept, u.branch as student_branch, u.academic_year as student_year,
        rev.name as reviewer_name
    FROM activities a
    JOIN users u ON u.id = a.student_id
    LEFT JOIN users rev ON rev.id = a.reviewer_id
    WHERE 1=1
";
$params = [];

if ($filterStatus === 'Pending') {
    $sql .= " AND a.status IN ('Pending Verification', 'Resubmitted / Pending Review')";
} elseif ($filterStatus !== 'All' && in_array($filterStatus, $allowedStatuses, true)) {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
}

if ($filterDept !== '') {
    $sql .= " AND (a.department = ? OR u.department = ?)";
    $params[] = $filterDept;
    $params[] = $filterDept;
}

if ($filterCat !== '') {
    $sql .= " AND a.category = ?";
    $params[] = $filterCat;
}

if ($fromDate !== '') {
    $sql .= " AND a.event_date >= ?";
    $params[] = $fromDate;
}

if ($toDate !== '') {
    $sql .= " AND a.event_date <= ?";
    $params[] = $toDate;
}

if ($searchQuery !== '') {
    $sql .= " AND (u.name LIKE ? OR u.student_id_code LIKE ? OR a.event_name LIKE ? OR a.submission_code LIKE ? OR a.organizer LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY (a.status = 'Pending Verification' OR a.status = 'Resubmitted / Pending Review') DESC, a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$page_title = 'Certificate Verification Module · Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bolder text-primary mb-1">
      <i class="bi bi-patch-check-fill text-primary me-2"></i>Certificate Verification &amp; Evaluation Center
    </h3>
    <p class="text-muted small mb-0">Review submitted evidence, inspect original files, approve credentials, or record formal rejection feedback.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('admin/audit_logs.php')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-journal-text me-1"></i>Audit Timeline
    </a>
    <a href="<?= e(base_url('admin/reports.php')) ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-bar-chart me-1"></i>Reports &amp; Analytics
    </a>
  </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
  <a href="verify.php?status=Pending" class="btn btn-sm <?= $filterStatus === 'Pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
    <i class="bi bi-hourglass-split me-1"></i>Action Required Queue 
    <span class="badge bg-warning text-dark ms-1"><?= $countPending + $countResubmitted ?></span>
  </a>
  <a href="verify.php?status=Resubmitted%20%2F%20Pending%20Review" class="btn btn-sm <?= $filterStatus === 'Resubmitted / Pending Review' ? 'btn-info text-dark' : 'btn-outline-info text-dark' ?>">
    <i class="bi bi-arrow-repeat me-1"></i>Resubmitted Submissions
    <span class="badge bg-secondary ms-1"><?= $countResubmitted ?></span>
  </a>
  <a href="verify.php?status=Verified" class="btn btn-sm <?= $filterStatus === 'Verified' ? 'btn-success' : 'btn-outline-success' ?>">
    <i class="bi bi-check2-circle me-1"></i>Verified Achievements
    <span class="badge bg-secondary ms-1"><?= $countVerified ?></span>
  </a>
  <a href="verify.php?status=Rejected" class="btn btn-sm <?= $filterStatus === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">
    <i class="bi bi-x-circle me-1"></i>Rejected
    <span class="badge bg-secondary ms-1"><?= $countRejected ?></span>
  </a>
  <a href="verify.php?status=All" class="btn btn-sm <?= $filterStatus === 'All' ? 'btn-dark' : 'btn-outline-secondary' ?>">
    <i class="bi bi-collection me-1"></i>All Submissions (<?= $countAll ?>)
  </a>
</div>

<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-body p-3">
    <form method="get" class="row g-2 align-items-center">
      <input type="hidden" name="status" value="<?= e($filterStatus) ?>">

      <div class="col-md-3">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search student, roll, event, ID..." value="<?= e($searchQuery) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="department" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept) ?>" <?= $filterDept === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="category" class="form-select form-select-sm">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $filterCat === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-1">
        <input type="date" name="from_date" class="form-control form-control-sm" title="From Date" value="<?= e($fromDate) ?>">
        <input type="date" name="to_date" class="form-control form-control-sm" title="To Date" value="<?= e($toDate) ?>">
      </div>

      <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold" title="Apply Filters">
          <i class="bi bi-funnel"></i>
        </button>
        <?php if ($searchQuery || $filterDept || $filterCat || $fromDate || $toDate): ?>
          <a href="verify.php?status=<?= urlencode($filterStatus) ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
            <i class="bi bi-x-lg"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (empty($submissions)): ?>
  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body empty-state-box">
      <div class="empty-state-icon text-success"><i class="bi bi-check-all"></i></div>
      <h5 class="fw-bold text-dark">Verification Queue Clear</h5>
      <p class="text-muted small mx-auto" style="max-width: 420px;">
        There are currently no student submissions matching your filter criteria.
      </p>
      <a href="verify.php?status=All" class="btn btn-sm btn-outline-primary">View All Past Records</a>
    </div>
  </div>
<?php else: ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($submissions as $sub): ?>
      <?php 
        $isPending = in_array($sub['status'], ['Pending Verification', 'Resubmitted / Pending Review'], true);
        $certUrl = base_url('certificate.php?id=' . (int)$sub['id']);
        $qrVerifyUrl = base_url('verify_credential.php?uuid=' . urlencode($sub['verification_uuid']));
      ?>
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden <?= $isPending ? 'border-start border-4 border-warning' : '' ?>">
        
        <div class="card-header bg-white p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center gap-2">
            <code class="fw-bold text-primary fs-6"><?= e($sub['submission_code']) ?></code>
            <?= status_badge($sub['status']) ?>
            <?= category_badge($sub['category']) ?>
            <span class="badge bg-light text-dark border"><?= e($sub['participation_type']) ?></span>
            <?php if ((int)$sub['resubmission_count'] > 0): ?>
              <span class="badge bg-info-subtle text-info border small" title="Resubmission Count">
                <i class="bi bi-arrow-repeat me-1"></i>Resubmission #<?= (int)$sub['resubmission_count'] ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="text-muted small">
            <i class="bi bi-calendar3 me-1"></i>Event Date: <strong><?= format_display_date($sub['event_date']) ?></strong>
            &middot; Submitted: <?= date('d M Y, h:i A', strtotime($sub['created_at'])) ?>
          </div>
        </div>

        <div class="card-body p-4 bg-white">
          <div class="row g-4">
            
            <div class="col-lg-7">
              <h5 class="fw-bold text-dark mb-1"><?= e($sub['event_name']) ?></h5>
              <div class="text-muted small mb-3">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Organizer: <strong><?= e($sub['organizer']) ?></strong> 
                &middot; Conducting Dept: <strong><?= e($sub['department']) ?></strong>
              </div>

              <?php if (!empty($sub['achievement_description'])): ?>
                <div class="p-3 bg-light rounded-3 text-secondary small mb-3 border-start border-3 border-primary">
                  <strong>Student Description:</strong><br>
                  <?= nl2br(e($sub['achievement_description'])) ?>
                </div>
              <?php endif; ?>

              <div class="p-3 bg-light bg-opacity-75 rounded-3 border">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="fw-bold text-dark">
                    <i class="bi bi-person-fill text-primary me-1"></i><?= e($sub['student_name']) ?>
                  </div>
                  <span class="badge bg-primary-subtle text-primary border small"><?= e($sub['student_id_code'] ?: 'ID Verified') ?></span>
                </div>
                <div class="text-muted small">
                  <?= e($sub['student_dept']) ?> &middot; Branch: <?= e($sub['student_branch'] ?: 'General') ?> &middot; <?= e($sub['student_year']) ?>
                </div>
                <div class="text-muted small mt-1">
                  <i class="bi bi-envelope me-1"></i><?= e($sub['student_email']) ?>
                </div>
              </div>

              <?php if (!empty($sub['rejection_reason'])): ?>
                <div class="alert alert-danger py-2 px-3 small mt-3 mb-0">
                  <strong>Rejection Note on Record:</strong> <?= e($sub['rejection_reason']) ?>
                  <?php if ($sub['reviewer_name']): ?>
                    <span class="text-muted">(by <?= e($sub['reviewer_name']) ?>)</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if ($sub['status'] === 'Verified'): ?>
                <div class="text-success small mt-3">
                  <i class="bi bi-shield-check me-1"></i>Verified on <?= format_display_datetime($sub['reviewed_at']) ?> by <strong><?= e($sub['reviewer_name'] ?: 'Faculty Reviewer') ?></strong>
                  <?php if (!empty($sub['review_notes'])): ?>
                    &middot; Note: <em><?= e($sub['review_notes']) ?></em>
                  <?php endif; ?>
                  <span class="ms-2">
                    <a href="<?= e($qrVerifyUrl) ?>" target="_blank" class="fw-bold text-success text-decoration-none">
                      <i class="bi bi-qr-code me-1"></i>Public QR Credential &rarr;
                    </a>
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <div class="col-lg-5 border-start">
              <div class="d-flex flex-column h-100 justify-content-between">
                
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-secondary small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                      <i class="bi bi-paperclip me-1"></i>Attached Certificate Proof
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.78rem;" onclick="viewCertificateModal('<?= e($certUrl) ?>', '<?= e(addslashes($sub['event_name'])) ?>')">
                      <i class="bi bi-arrows-fullscreen me-1"></i>Modal Viewer
                    </button>
                  </div>

                  <div class="p-3 border rounded-3 bg-light text-center">
                    <i class="bi bi-file-earmark-check display-6 text-primary mb-2"></i>
                    <div class="fw-semibold text-truncate small mb-2" title="<?= e($sub['certificate_original_name']) ?>">
                      <?= e($sub['certificate_original_name']) ?>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                      <a href="<?= e($certUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Full Proof
                      </a>
                    </div>
                  </div>
                </div>

                <div>
                  <div class="border-top pt-3">
                    <div class="fw-bold text-dark small mb-2">Record Faculty Decision:</div>

                    <form method="post" action="verify.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . e($_SERVER['QUERY_STRING']) : '' ?>" class="d-flex flex-column gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="activity_id" value="<?= (int)$sub['id'] ?>">

                      <div>
                        <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Review remarks / endorsement citation (optional for approval)">
                      </div>

                      <div class="collapse" id="rejectBox_<?= (int)$sub['id'] ?>">
                        <div class="p-2 border border-danger rounded-3 bg-danger-subtle bg-opacity-10 mt-1 mb-2">
                          <label class="form-label text-danger fw-bold small mb-1">
                            Rejection Reason <span class="text-danger">*</span>
                          </label>
                          <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" placeholder="Explain clearly why this certificate is rejected..."></textarea>
                          <button type="submit" name="decision" value="Rejected" class="btn btn-sm btn-danger w-100 fw-bold mt-2" onclick="return confirm('Confirm rejecting this submission? Student will receive your rejection reason.');">
                            <i class="bi bi-x-circle me-1"></i>Confirm &amp; Record Rejection
                          </button>
                        </div>
                      </div>

                      <div class="d-flex gap-2">
                        <button type="submit" name="decision" value="Verified" class="btn btn-sm btn-success fw-bold flex-grow-1 shadow-sm" onclick="return confirm('Approve and verify this achievement? This will generate an official digital verification QR code.');">
                          <i class="bi bi-check-circle-fill me-1"></i>Approve &amp; Verify
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="collapse" data-bs-target="#rejectBox_<?= (int)$sub['id'] ?>">
                          <i class="bi bi-x-circle me-1"></i>Reject...
                        </button>
                      </div>
                    </form>

                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
