<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminUser = current_user();

// Key Counts
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Verified') as verified,
        SUM(status = 'Pending Verification' OR status = 'Resubmitted / Pending Review') as pending,
        SUM(status = 'Rejected') as rejected
    FROM activities
")->fetch();

$totalSubmissions = (int)($stats['total'] ?? 0);
$verifiedCount = (int)($stats['verified'] ?? 0);
$pendingCount = (int)($stats['pending'] ?? 0);
$rejectedCount = (int)($stats['rejected'] ?? 0);

// Total registered students & active participating students
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$activeStudents = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM activities")->fetchColumn();

// Verification rate
$verificationRate = $totalSubmissions > 0 ? round(($verifiedCount / $totalSubmissions) * 100, 1) : 0;

// Category breakdown
$catStats = $pdo->query("
    SELECT category, COUNT(*) as total, SUM(status = 'Verified') as verified 
    FROM activities 
    GROUP BY category 
    ORDER BY total DESC
")->fetchAll();

// Recent 8 submissions
$recentSubmissions = $pdo->query("
    SELECT 
        a.id, a.submission_code, a.event_name, a.category, a.event_date, a.status, a.created_at,
        u.name as student_name, u.student_id_code, u.department
    FROM activities a
    JOIN users u ON u.id = a.student_id
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetchAll();

$page_title = 'Institutional Overview · Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <div class="text-uppercase fw-bold text-primary small" style="font-size: 0.72rem; letter-spacing: 1px;">
      <i class="bi bi-shield-lock-fill me-1"></i><?= e(COLLEGE_SHORT_NAME) ?> &middot; Administrative Center
    </div>
    <h3 class="fw-bolder text-dark mb-1">Student Participation Management Dashboard</h3>
    <p class="text-muted small mb-0">Institutional repository metrics, pending certificate reviews, and achievement audit stats.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= e(base_url('admin/verify.php?status=Pending')) ?>" class="btn btn-warning text-dark fw-bold shadow-sm">
      <i class="bi bi-clock-history me-1"></i>Review Pending Queue (<?= $pendingCount ?>)
    </a>
    <a href="<?= e(base_url('admin/verify.php')) ?>" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-shield-check me-1"></i>All Submissions
    </a>
    <a href="<?= e(base_url('admin/reports.php')) ?>" class="btn btn-primary fw-bold shadow-sm">
      <i class="bi bi-bar-chart-line-fill me-1"></i>Analytics &amp; Charts
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-blue">
        <i class="bi bi-people-fill"></i>
      </div>
      <div>
        <div class="metric-value"><?= $totalStudents ?></div>
        <div class="metric-label">Enrolled Students</div>
        <small class="text-muted" style="font-size: 0.72rem;"><?= $activeStudents ?> active submitters</small>
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
        <div class="metric-label">Pending Verification</div>
        <small class="text-muted" style="font-size: 0.72rem;">Requires faculty review</small>
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
        <div class="metric-label">Verified Achievements</div>
        <small class="text-success" style="font-size: 0.72rem;"><?= $verificationRate ?>% acceptance rate</small>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="metric-card">
      <div class="metric-icon-wrap metric-icon-rose">
        <i class="bi bi-folder-fill"></i>
      </div>
      <div>
        <div class="metric-value"><?= $totalSubmissions ?></div>
        <div class="metric-label">Total Submissions</div>
        <small class="text-danger" style="font-size: 0.72rem;"><?= $rejectedCount ?> rejected</small>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="app-card h-100 mb-0">
      <div class="app-card-header">
        <div>
          <h5 class="app-card-title"><i class="bi bi-journal-text text-primary me-2"></i>Latest Activity Submissions</h5>
          <span class="text-muted small">Real-time submissions from across college departments</span>
        </div>
        <a href="<?= e(base_url('admin/verify.php')) ?>" class="btn btn-sm btn-outline-primary">
          Open Verification Panel &rarr;
        </a>
      </div>

      <div class="app-card-body p-0">
        <?php if (empty($recentSubmissions)): ?>
          <div class="empty-state-box">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <h6 class="fw-bold">No Submissions Recorded Yet</h6>
            <p class="text-muted small">When students upload certificates, they will appear here for evaluation.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Student &amp; Roll ID</th>
                  <th>Event Name</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentSubmissions as $row): ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-dark"><?= e($row['student_name']) ?></div>
                      <div class="text-muted small"><?= e($row['student_id_code'] ?: $row['department']) ?></div>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= e($row['event_name']) ?>">
                        <?= e($row['event_name']) ?>
                      </div>
                      <code style="font-size: 0.7rem;"><?= e($row['submission_code']) ?></code>
                    </td>
                    <td><?= category_badge($row['category']) ?></td>
                    <td class="small text-nowrap"><?= format_display_date($row['event_date']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                      <a href="<?= e(base_url('admin/verify.php?q=' . urlencode($row['submission_code']))) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Inspect Record">
                        <i class="bi bi-eye-fill"></i> Review
                      </a>
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
          <h5 class="app-card-title"><i class="bi bi-pie-chart text-primary me-2"></i>Category Activity</h5>
          <span class="text-muted small">Distribution of student participation</span>
        </div>
      </div>
      <div class="app-card-body">
        <?php if (empty($catStats)): ?>
          <p class="text-muted small text-center py-4">No activity records available.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($catStats as $cs): ?>
              <?php $pct = $totalSubmissions > 0 ? round(($cs['total'] / $totalSubmissions) * 100) : 0; ?>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-bold text-secondary small"><?= e($cs['category']) ?></span>
                  <span class="small text-muted">
                    <strong><?= (int)$cs['total'] ?></strong> records (<?= (int)$cs['verified'] ?> verified)
                  </span>
                </div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-4 pt-3 border-top text-center">
            <a href="<?= e(base_url('admin/reports.php')) ?>" class="btn btn-sm btn-outline-primary w-100 fw-bold">
              <i class="bi bi-graph-up me-1"></i>View Full Institutional Analytics
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
