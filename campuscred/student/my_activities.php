<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

// Filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$allowedStatuses = ['Pending Verification', 'Verified', 'Rejected', 'Resubmitted / Pending Review'];
$categories = ['Academic', 'Technical', 'Cultural', 'Sports', 'Extracurricular', 'Other'];

// Build SQL query
$sql = "
    SELECT 
        a.id, a.submission_code, a.event_name, a.category, a.event_date, a.organizer, a.department,
        a.participation_type, a.achievement_description, a.certificate_path, a.certificate_original_name,
        a.status, a.rejection_reason, a.review_notes, a.reviewed_at, a.verification_uuid, a.resubmission_count,
        a.created_at, u.name as reviewer_name
    FROM activities a
    LEFT JOIN users u ON u.id = a.reviewer_id
    WHERE a.student_id = ?
";
$params = [$studentId];

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter !== '' && in_array($categoryFilter, $categories, true)) {
    $sql .= " AND a.category = ?";
    $params[] = $categoryFilter;
}

if ($searchQuery !== '') {
    $sql .= " AND (a.event_name LIKE ? OR a.submission_code LIKE ? OR a.organizer LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

$page_title = 'My Activity Records · ' . e($user['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h3 class="fw-bolder text-primary mb-1"><i class="bi bi-collection-fill me-2"></i>My Submissions &amp; Activity Records</h3>
    <p class="text-muted small mb-0">Track verification status, review faculty feedback, and manage your credentials.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('student/submit_activity.php')) ?>" class="btn btn-primary fw-bold shadow-sm">
      <i class="bi bi-plus-circle-fill me-1"></i>Submit New Achievement
    </a>
    <a href="<?= e(base_url('student/portfolio.php')) ?>" class="btn btn-outline-primary fw-bold">
      <i class="bi bi-mortarboard-fill me-1"></i>Verified Portfolio
    </a>
  </div>
</div>

<!-- Filter Bar Card -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-body p-3">
    <form method="get" class="row g-2 align-items-center">
      
      <div class="col-md-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search event, code, organizer..." value="<?= e($searchQuery) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Verification Statuses</option>
          <?php foreach ($allowedStatuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <select name="category" class="form-select form-select-sm">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <?php if ($statusFilter || $categoryFilter || $searchQuery): ?>
          <a href="my_activities.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
            <i class="bi bi-x-lg"></i>
          </a>
        <?php endif; ?>
      </div>

    </form>
  </div>
</div>

<!-- Activities Record List -->
<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
    <h5 class="app-card-title mb-0">
      <i class="bi bi-journal-check text-primary me-2"></i>Activity Submissions (<?= count($activities) ?>)
    </h5>
  </div>

  <div class="card-body p-0">
    <?php if (empty($activities)): ?>
      <div class="empty-state-box">
        <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
        <h5 class="fw-bold text-dark">No Matching Activities Found</h5>
        <p class="text-muted small">No submissions match your selected filter criteria. Try resetting the filters or submit a new activity.</p>
        <a href="my_activities.php" class="btn btn-outline-primary btn-sm me-2">Clear Filter</a>
        <a href="submit_activity.php" class="btn btn-primary btn-sm">Submit New Achievement</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th>Tracking ID</th>
              <th>Event Title</th>
              <th>Category</th>
              <th>Date</th>
              <th>Organizer &amp; Dept</th>
              <th>Status &amp; Review Remarks</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activities as $act): ?>
              <tr>
                <td>
                  <code class="fw-bold text-primary"><?= e($act['submission_code']) ?></code>
                  <div class="text-muted" style="font-size: 0.72rem;">
                    <span class="badge bg-light text-dark border"><?= e($act['participation_type']) ?></span>
                  </div>
                  <?php if ((int)$act['resubmission_count'] > 0): ?>
                    <span class="badge bg-info-subtle text-info border" style="font-size: 0.65rem;" title="Resubmitted after rejection">
                      Resubmitted (<?= (int)$act['resubmission_count'] ?>)
                    </span>
                  <?php endif; ?>
                </td>

                <td style="max-width: 260px;">
                  <div class="fw-bold text-dark"><?= e($act['event_name']) ?></div>
                  <?php if (!empty($act['achievement_description'])): ?>
                    <div class="text-muted small text-truncate" style="max-width: 250px;" title="<?= e($act['achievement_description']) ?>">
                      <?= e($act['achievement_description']) ?>
                    </div>
                  <?php endif; ?>
                </td>

                <td><?= category_badge($act['category']) ?></td>

                <td class="text-nowrap small text-secondary">
                  <div><?= format_display_date($act['event_date']) ?></div>
                  <div class="text-muted" style="font-size: 0.72rem;">Submitted: <?= date('d M', strtotime($act['created_at'])) ?></div>
                </td>

                <td class="small">
                  <div class="fw-semibold text-secondary"><?= e($act['organizer']) ?></div>
                  <div class="text-muted" style="font-size: 0.72rem;"><?= e($act['department']) ?></div>
                </td>

                <td style="max-width: 260px;">
                  <div><?= status_badge($act['status']) ?></div>
                  
                  <?php if ($act['status'] === 'Rejected'): ?>
                    <div class="alert alert-danger py-1 px-2 my-1 small" style="font-size: 0.78rem;">
                      <strong>Rejection Reason:</strong> <?= e($act['rejection_reason'] ?: 'Document not acceptable.') ?>
                    </div>
                  <?php elseif ($act['status'] === 'Verified'): ?>
                    <div class="text-success small mt-1" style="font-size: 0.75rem;">
                      <i class="bi bi-shield-check me-1"></i>Verified by <?= e($act['reviewer_name'] ?: 'Faculty Authority') ?>
                    </div>
                  <?php elseif ($act['status'] === 'Resubmitted / Pending Review'): ?>
                    <div class="text-info small mt-1" style="font-size: 0.75rem;">
                      <i class="bi bi-arrow-repeat me-1"></i>Waiting for faculty re-check
                    </div>
                  <?php endif; ?>
                </td>

                <td class="text-end text-nowrap">
                  <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="viewCertificateModal('<?= e(base_url('certificate.php?id=' . (int)$act['id'])) ?>', '<?= e(addslashes($act['event_name'])) ?>')" title="View Uploaded Certificate">
                    <i class="bi bi-file-earmark-text me-1"></i>Proof
                  </button>

                  <?php if ($act['status'] === 'Rejected'): ?>
                    <a href="<?= e(base_url('student/resubmit_activity.php?id=' . (int)$act['id'])) ?>" class="btn btn-sm btn-warning py-1 px-2 text-dark fw-bold ms-1" title="Fix and Resubmit">
                      <i class="bi bi-arrow-repeat me-1"></i>Fix &amp; Resubmit
                    </a>
                  <?php endif; ?>

                  <?php if ($act['status'] === 'Verified'): ?>
                    <a href="<?= e(base_url('verify_credential.php?uuid=' . urlencode($act['verification_uuid']))) ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 px-2 ms-1" title="Open Public Verification QR">
                      <i class="bi bi-qr-code me-1"></i>QR
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
