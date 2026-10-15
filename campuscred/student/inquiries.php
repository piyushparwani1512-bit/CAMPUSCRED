<?php
/**
 * CampusCred - Student Inquiries & Recruitment Offers Inbox
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $inqId = (int)($_POST['inquiry_id'] ?? 0);
    $action = $_POST['action'];

    if ($inqId > 0 && in_array($action, ['accept', 'decline'], true)) {
        $newStatus = ($action === 'accept') ? 'Accepted' : 'Declined';
        $up = $pdo->prepare("UPDATE recruitment_inquiries SET status = ? WHERE id = ? AND student_id = ?");
        $up->execute([$newStatus, $inqId, $studentId]);
        set_flash('success', "Recruitment opportunity marked as '{$newStatus}'.");
        header('Location: ' . base_url('student/inquiries.php'));
        exit;
    }
}

// Fetch inquiries for this student
$stmt = $pdo->prepare("
    SELECT 
        inq.*,
        rec.name as recruiter_name, rec.company_name, rec.company_website, rec.designation, rec.industry
    FROM recruitment_inquiries inq
    LEFT JOIN users rec ON rec.id = inq.recruiter_id
    WHERE inq.student_id = ?
    ORDER BY inq.created_at DESC
");
$stmt->execute([$studentId]);
$inquiries = $stmt->fetchAll();

$page_title = 'Company Inquiries & Offers · ' . e($user['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
  <div>
    <h3 class="fw-bolder text-dark mb-1">
      <i class="bi bi-briefcase-fill text-warning me-2"></i>Company Recruitment Inquiries &amp; Offers
    </h3>
    <p class="text-muted small mb-0">Review outreach, interview invitations, and job opportunities sent by partner companies and verified employers.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('student/profile.php')) ?>" class="btn btn-outline-primary btn-sm fw-bold">
      <i class="bi bi-sliders me-1"></i>Career Profile &amp; Skills
    </a>
    <a href="<?= e(base_url('student/dashboard.php')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
  </div>
</div>

<?php if (empty($inquiries)): ?>
  <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
    <div class="display-4 text-muted mb-3"><i class="bi bi-envelope-open"></i></div>
    <h5 class="fw-bold text-dark">No company inquiries yet</h5>
    <p class="text-muted small mx-auto" style="max-width: 500px;">
      As your verified achievements increase and recruiters browse the talent pool, interview requests and job invitations will appear here.
    </p>
    <a href="<?= e(base_url('student/profile.php')) ?>" class="btn btn-primary btn-sm px-4 mx-auto">
      <i class="bi bi-person-lines-fill me-1"></i>Optimize Skills Profile for Recruiters
    </a>
  </div>
<?php else: ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($inquiries as $inq): ?>
      <?php 
        $companyDisplay = $inq['company_name'] ?: 'Recruiter Outreach';
        $status = $inq['status'];
      ?>
      <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
          
          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <span class="badge bg-primary px-3 py-1 fw-bold fs-6">
                <i class="bi bi-building me-1"></i><?= e($companyDisplay) ?>
              </span>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($inq['job_type']) ?></span>
              <?php if (!empty($inq['salary_range'])): ?>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                  <?= e($inq['salary_range']) ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($inq['location'])): ?>
                <span class="badge bg-light text-muted border">
                  <i class="bi bi-geo-alt me-1"></i><?= e($inq['location']) ?>
                </span>
              <?php endif; ?>
            </div>

            <h4 class="fw-bolder text-dark mb-1"><?= e($inq['job_title']) ?></h4>

            <div class="text-secondary small fw-semibold mb-3">
              Dispatched by: <strong><?= e($inq['recruiter_name'] ?: 'Talent Lead') ?></strong>
              <?php if (!empty($inq['designation'])): ?>
                (<?= e($inq['designation']) ?>)
              <?php endif; ?>
              &middot; Contact: <code><?= e($inq['contact_email']) ?></code>
              &middot; Received: <?= format_display_datetime($inq['created_at']) ?>
            </div>

            <div class="p-3 bg-light rounded-3 border text-dark mb-3">
              <div class="fw-bold small text-secondary text-uppercase mb-1">Message from Recruiter:</div>
              <div style="white-space: pre-line; line-height: 1.5;"><?= e($inq['message']) ?></div>
            </div>

            <?php if (!empty($inq['company_website'])): ?>
              <div class="small">
                <i class="bi bi-globe me-1 text-primary"></i>Company Website: 
                <a href="<?= e($inq['company_website']) ?>" target="_blank" class="text-decoration-none"><?= e($inq['company_website']) ?></a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Status & Action column -->
          <div class="text-md-end text-start align-self-stretch d-flex flex-column justify-content-between" style="min-width: 180px;">
            <div class="mb-3">
              <span class="badge <?= $status === 'Accepted' ? 'bg-success' : ($status === 'Declined' ? 'bg-danger' : 'bg-warning text-dark') ?> px-3 py-2 fs-6">
                Status: <?= e($status) ?>
              </span>
            </div>

            <?php if ($status === 'Sent' || $status === 'Viewed'): ?>
              <div class="d-flex flex-column gap-2">
                <form method="POST" action="inquiries.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
                  <button type="submit" name="action" value="accept" class="btn btn-success fw-bold btn-sm w-100">
                    <i class="bi bi-check-circle-fill me-1"></i>Accept &amp; Connect
                  </button>
                </form>
                <form method="POST" action="inquiries.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
                  <button type="submit" name="action" value="decline" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Decline this recruitment opportunity?');">
                    <i class="bi bi-x-circle me-1"></i>Decline
                  </button>
                </form>
              </div>
            <?php else: ?>
              <div class="text-muted small">
                Response recorded on your profile.
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
