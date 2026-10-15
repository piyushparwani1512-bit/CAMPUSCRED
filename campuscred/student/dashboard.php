<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

// Refresh student profile from DB
$stmtUser = $pdo->prepare('SELECT id, student_id_code, name, email, department, branch, academic_year, phone, created_at FROM users WHERE id = ?');
$stmtUser->execute([$studentId]);
$profile = $stmtUser->fetch() ?: $user;

// Fetch statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Verified') as verified,
        SUM(status = 'Pending Verification' OR status = 'Resubmitted / Pending Review') as pending,
        SUM(status = 'Rejected') as rejected
    FROM activities 
    WHERE student_id = ?
");
$statsStmt->execute([$studentId]);
$stats = $statsStmt->fetch();

$totalActivities = (int)($stats['total'] ?? 0);
$verifiedCount = (int)($stats['verified'] ?? 0);
$pendingCount = (int)($stats['pending'] ?? 0);
$rejectedCount = (int)($stats['rejected'] ?? 0);

// Fetch recent 6 activities
$recentStmt = $pdo->prepare("
    SELECT id, submission_code, event_name, category, event_date, organizer, department, participation_type, status, rejection_reason, review_notes, certificate_path, verification_uuid, created_at 
    FROM activities 
    WHERE student_id = ? 
    ORDER BY created_at DESC 
    LIMIT 6
");
$recentStmt->execute([$studentId]);
$recentActivities = $recentStmt->fetchAll();

// Fetch verified achievements for showcase
$verifiedStmt = $pdo->prepare("
    SELECT id, submission_code, event_name, category, event_date, organizer, participation_type, certificate_path, verification_uuid, reviewed_at 
    FROM activities 
    WHERE student_id = ? AND status = 'Verified' 
    ORDER BY event_date DESC
");
$verifiedStmt->execute([$studentId]);
$verifiedActivities = $verifiedStmt->fetchAll();

// Fetch recruitment inquiries count
$inquiryCount = get_student_inquiries_count($pdo, $studentId);
$shareableUrl = full_url('public_portfolio.php?id=' . $studentId);

$page_title = 'Student Dashboard · ' . e($profile['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($inquiryCount > 0): ?>
  <div class="alert alert-warning border-warning shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-briefcase-fill text-warning fs-3"></i>
      <div>
        <div class="fw-bold text-dark">You have <?= $inquiryCount ?> company recruitment inquiries / interview offers!</div>
        <div class="small text-secondary">Verified employers and talent leads have reviewed your achievements and sent you opportunities.</div>
      </div>
    </div>
    <a href="<?= e(base_url('student/inquiries.php')) ?>" class="btn btn-warning text-dark fw-bold btn-sm px-4">
      <i class="bi bi-envelope-paper-fill me-1"></i>View Offers
    </a>
  </div>
<?php endif; ?>

<!-- Student Profile Hero Banner -->
<div class="profile-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
  <div class="d-flex align-items-center gap-3">
    <div class="profile-avatar">
      <?= strtoupper(substr($profile['name'] ?? 'S', 0, 1)) ?>
    </div>
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
        <h3 class="fw-bold text-white mb-0"><?= e($profile['name']) ?></h3>
        <span class="badge bg-white text-primary fw-bold px-2 py-1" style="font-size: 0.75rem;">
          <i class="bi bi-person-badge me-1"></i><?= e($profile['student_id_code'] ?: 'STUDENT') ?>
        </span>
        <?php if (!empty($profile['is_open_to_work'])): ?>
          <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.72rem;">
            <i class="bi bi-briefcase me-1"></i>Open to Recruiters
          </span>
        <?php endif; ?>
      </div>
      <div class="d-flex flex-wrap gap-2 text-white-50 small mt-2">
        <span class="profile-chip"><i class="bi bi-building"></i> <?= e($profile['department']) ?></span>
        <?php if (!empty($profile['branch'])): ?>
          <span class="profile-chip"><i class="bi bi-diagram-3"></i> Branch: <?= e($profile['branch']) ?></span>
        <?php endif; ?>
        <?php if (!empty($profile['academic_year'])): ?>
          <span class="profile-chip"><i class="bi bi-calendar-event"></i> <?= e($profile['academic_year']) ?></span>
        <?php endif; ?>
        <span class="profile-chip"><i class="bi bi-envelope"></i> <?= e($profile['email']) ?></span>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 flex-wrap align-self-start align-self-md-center mt-3 mt-md-0">
    <a href="<?= e(base_url('student/submit_activity.php')) ?>" class="btn btn-light text-primary fw-bold shadow-sm">
      <i class="bi bi-plus-circle-fill me-1"></i>Submit Achievement
    </a>
    <a href="<?= e(base_url('student/portfolio.php')) ?>" class="btn btn-outline-light fw-bold">
      <i class="bi bi-mortarboard me-1"></i>Verified Portfolio
    </a>
    <a href="<?= e(base_url('student/profile.php')) ?>" class="btn btn-outline-light" title="Manage Career Profile & Skills">
      <i class="bi bi-sliders me-1"></i>Career Profile
    </a>
    <?php if ($inquiryCount > 0): ?>
      <a href="<?= e(base_url('student/inquiries.php')) ?>" class="btn btn-warning text-dark fw-bold">
        <i class="bi bi-briefcase-fill me-1"></i>Offers (<?= $inquiryCount ?>)
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- 4 Key Achievement Status Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-blue">
        <i class="bi bi-folder-check"></i>
      </div>
      <div>
        <div class="metric-value"><?= $totalActivities ?></div>
        <div class="metric-label">Total Submitted</div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-emerald">
        <i class="bi bi-patch-check-fill"></i>
      </div>
      <div>
        <div class="metric-value text-success"><?= $verifiedCount ?></div>
        <div class="metric-label">Verified Records</div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-amber">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div>
        <div class="metric-value text-warning"><?= $pendingCount ?></div>
        <div class="metric-label">Pending Review</div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-rose">
        <i class="bi bi-x-octagon"></i>
      </div>
      <div>
        <div class="metric-value text-danger"><?= $rejectedCount ?></div>
        <div class="metric-label">Rejected / Needs Fix</div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content Grid -->
<div class="row g-4">
  <div class="col-lg-8">
    <div class="app-card h-100 mb-0">
      <div class="app-card-header">
        <div>
          <h5 class="app-card-title"><i class="bi bi-clock-history text-primary me-2"></i>Recent Activity Submissions</h5>
          <span class="text-muted small">Latest participation records sent for institutional verification</span>
        </div>
        <a href="<?= e(base_url('student/my_activities.php')) ?>" class="btn btn-sm btn-outline-primary">
          View All History &rarr;
        </a>
      </div>

      <div class="app-card-body p-0">
        <?php if (empty($recentActivities)): ?>
          <div class="empty-state-box">
            <div class="empty-state-icon"><i class="bi bi-trophy"></i></div>
            <h5 class="fw-bold text-dark">No Achievement Records Yet</h5>
            <p class="text-muted mx-auto" style="max-width: 440px;">
              You have not submitted any academic, technical, cultural, sports, or extracurricular achievements yet. Start building your verified college credential portfolio!
            </p>
            <a href="<?= e(base_url('student/submit_activity.php')) ?>" class="btn btn-primary px-4 fw-bold shadow-sm">
              <i class="bi bi-plus-lg me-1"></i>Submit Your First Achievement
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Submission ID</th>
                  <th>Event &amp; Organizer</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentActivities as $act): ?>
                  <tr>
                    <td>
                      <code class="fw-bold text-primary"><?= e($act['submission_code']) ?></code>
                      <div class="text-muted" style="font-size: 0.72rem;"><?= e($act['participation_type']) ?></div>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= e($act['event_name']) ?></div>
                      <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= e($act['organizer']) ?></div>
                    </td>
                    <td><?= category_badge($act['category']) ?></td>
                    <td class="text-nowrap small text-secondary"><?= format_display_date($act['event_date']) ?></td>
                    <td>
                      <?= status_badge($act['status']) ?>
                      <?php if ($act['status'] === 'Rejected' && !empty($act['rejection_reason'])): ?>
                        <div class="mt-1 small text-danger" title="<?= e($act['rejection_reason']) ?>">
                          <i class="bi bi-exclamation-circle me-1"></i><small>Reason: <?= e(mb_strimwidth($act['rejection_reason'], 0, 35, '...')) ?></small>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                      <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="viewCertificateModal('<?= e(base_url('certificate.php?id=' . (int)$act['id'])) ?>', '<?= e(addslashes($act['event_name'])) ?>')" title="View Uploaded Certificate">
                        <i class="bi bi-file-earmark-text"></i>
                      </button>

                      <?php if ($act['status'] === 'Rejected'): ?>
                        <a href="<?= e(base_url('student/resubmit_activity.php?id=' . (int)$act['id'])) ?>" class="btn btn-sm btn-warning py-1 px-2 text-dark fw-bold ms-1" title="Fix and Resubmit">
                          <i class="bi bi-arrow-repeat me-1"></i>Fix &amp; Resubmit
                        </a>
                      <?php endif; ?>

                      <?php if ($act['status'] === 'Verified'): ?>
                        <a href="<?= e(base_url('verify_credential.php?uuid=' . urlencode($act['verification_uuid']))) ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 px-2 ms-1" title="View Verified QR Credential">
                          <i class="bi bi-qr-code"></i>
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
  </div>

  <div class="col-lg-4">
    <div class="app-card h-100 mb-0">
      <div class="app-card-header">
        <div>
          <h5 class="app-card-title"><i class="bi bi-award-fill text-warning me-2"></i>Verified Credentials</h5>
          <span class="text-muted small">Officially endorsed achievements</span>
        </div>
      </div>
      <div class="app-card-body">
        <?php if (empty($verifiedActivities)): ?>
          <div class="text-center py-4">
            <i class="bi bi-shield-slash display-5 text-muted opacity-50"></i>
            <h6 class="fw-bold text-dark mt-2">No Verified Records Yet</h6>
            <p class="text-muted small mb-0">Once faculty review and verify your submissions, they will appear here with instant verification QR badges.</p>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach (array_slice($verifiedActivities, 0, 4) as $v): ?>
              <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                  <span class="badge bg-success-subtle text-success border border-success-subtle small">
                    <i class="bi bi-patch-check-fill me-1"></i><?= e($v['participation_type']) ?>
                  </span>
                  <small class="text-muted"><?= format_display_date($v['event_date']) ?></small>
                </div>
                <div class="fw-bold text-dark mb-1 small"><?= e($v['event_name']) ?></div>
                <div class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= e($v['organizer']) ?></div>
                
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                  <code class="small text-muted"><?= e($v['submission_code']) ?></code>
                  <div class="d-flex gap-1">
                    <a href="<?= e(base_url('certificate.php?id=' . (int)$v['id'])) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                      <i class="bi bi-download me-1"></i>Proof
                    </a>
                    <a href="<?= e(base_url('verify_credential.php?uuid=' . urlencode($v['verification_uuid']))) ?>" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">
                      <i class="bi bi-qr-code me-1"></i>QR
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-3 pt-3 border-top text-center">
            <a href="<?= e(base_url('student/portfolio.php')) ?>" class="btn btn-primary btn-sm w-100 fw-bold">
              <i class="bi bi-file-earmark-pdf-fill me-1"></i>Download Full Verified Portfolio (PDF)
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
