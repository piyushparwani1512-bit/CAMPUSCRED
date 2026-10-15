<?php
/**
 * CampusCred - Recruiter Inquiries & Interview Offers Outbox
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('recruiter');

$recruiter = current_user();
$recruiterId = (int)$recruiter['id'];

$stmt = $pdo->prepare("
    SELECT 
        inq.*,
        u.name as student_name, u.student_id_code, u.department, u.email as student_email, u.phone as student_phone
    FROM recruitment_inquiries inq
    JOIN users u ON u.id = inq.student_id
    WHERE inq.recruiter_id = ?
    ORDER BY inq.created_at DESC
");
$stmt->execute([$recruiterId]);
$inquiries = $stmt->fetchAll();

$page_title = 'Recruitment Inquiries Outbox · Recruiter Hub';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <div class="text-uppercase fw-bold text-primary small" style="font-size: 0.72rem; letter-spacing: 1px;">
      <i class="bi bi-envelope-paper-fill me-1"></i>Talent Outreach Outbox
    </div>
    <h3 class="fw-bolder text-dark mb-1">Dispatched Recruitment Inquiries</h3>
    <p class="text-muted small mb-0">Review all interview invites, internship proposals, and full-time job offers sent to student candidates.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="dashboard.php" class="btn btn-primary fw-bold btn-sm">
      <i class="bi bi-search me-1"></i>Search More Candidates
    </a>
  </div>
</div>

<?php if (empty($inquiries)): ?>
  <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
    <div class="display-4 text-primary mb-3"><i class="bi bi-send"></i></div>
    <h5 class="fw-bold text-dark">No inquiries sent yet</h5>
    <p class="text-muted small">You haven't dispatched any recruitment inquiries or interview invites yet.</p>
    <a href="dashboard.php" class="btn btn-primary btn-sm px-4 mx-auto">Explore Candidates Now</a>
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-secondary small text-uppercase">
          <tr>
            <th>Candidate</th>
            <th>Job Role &amp; Opportunity</th>
            <th>Type / Compensation</th>
            <th>Dispatched On</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inquiries as $inq): ?>
            <tr>
              <td>
                <div class="fw-bold text-dark fs-6">
                  <a href="candidate_profile.php?id=<?= (int)$inq['student_id'] ?>" class="text-decoration-none text-dark hover-primary">
                    <?= e($inq['student_name']) ?>
                  </a>
                </div>
                <div class="text-muted small">
                  <span class="badge bg-secondary-subtle text-secondary"><?= e($inq['student_id_code']) ?></span> &middot; <?= e($inq['department']) ?>
                </div>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($inq['job_title']) ?></div>
                <div class="text-muted small">Location: <?= e($inq['location'] ?: 'Not Specified') ?></div>
              </td>
              <td>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($inq['job_type']) ?></span>
                <?php if (!empty($inq['salary_range'])): ?>
                  <div class="text-muted small mt-1"><?= e($inq['salary_range']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="small text-dark"><?= format_display_date($inq['created_at']) ?></div>
                <div class="text-muted" style="font-size: 0.72rem;"><?= date('h:i A', strtotime($inq['created_at'])) ?></div>
              </td>
              <td>
                <?php 
                  $st = $inq['status'];
                  $badgeClass = match ($st) {
                    'Accepted' => 'bg-success text-white',
                    'Declined' => 'bg-danger-subtle text-danger border border-danger-subtle',
                    'Viewed' => 'bg-info-subtle text-info border border-info-subtle',
                    default => 'bg-warning-subtle text-dark border border-warning-subtle',
                  };
                ?>
                <span class="badge <?= $badgeClass ?> px-2 py-1">
                  <?= e($st) ?>
                </span>
              </td>
              <td class="text-end">
                <a href="candidate_profile.php?id=<?= (int)$inq['student_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Candidate Profile">
                  <i class="bi bi-eye"></i> View Profile
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
