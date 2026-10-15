<?php
/**
 * SKIT021 - Student Participation & Achievement Management System
 * Admin Portal: Immutable Verification Audit Logs & Review History
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

// Filter parameters
$actionFilter = trim($_GET['action'] ?? '');
$adminFilter  = (int)($_GET['reviewer_id'] ?? 0);
$search       = trim($_GET['search'] ?? '');
$dateFilter   = trim($_GET['log_date'] ?? '');

$where = ["1=1"];
$params = [];

if ($actionFilter !== '') {
    $where[] = "vl.action = :action";
    $params[':action'] = $actionFilter;
}
if ($adminFilter > 0) {
    $where[] = "vl.reviewer_id = :reviewer_id";
    $params[':reviewer_id'] = $adminFilter;
}
if ($search !== '') {
    $where[] = "(u.name LIKE :search OR u.student_id_code LIKE :search OR a.submission_code LIKE :search OR a.event_name LIKE :search)";
    $params[':search'] = "%{$search}%";
}
if ($dateFilter !== '') {
    $where[] = "DATE(vl.logged_at) = :log_date";
    $params[':log_date'] = $dateFilter;
}

$whereClause = implode(" AND ", $where);

// Fetch logs
$query = "
    SELECT vl.*, 
           adm.name AS reviewer_name, adm.email AS reviewer_email,
           a.submission_code, a.event_name, a.category, a.certificate_path, a.certificate_original_name,
           u.name AS student_name, u.student_id_code, u.department
    FROM verification_logs vl
    JOIN activities a ON vl.activity_id = a.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN users adm ON vl.reviewer_id = adm.id
    WHERE {$whereClause}
    ORDER BY vl.logged_at DESC
    LIMIT 250
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch summary metrics
$metricApproved = (int)$pdo->query("SELECT COUNT(*) FROM verification_logs WHERE action = 'Verified'")->fetchColumn();
$metricRejected = (int)$pdo->query("SELECT COUNT(*) FROM verification_logs WHERE action = 'Rejected'")->fetchColumn();
$metricTotal    = (int)$pdo->query("SELECT COUNT(*) FROM verification_logs")->fetchColumn();

// Fetch admin list for filter dropdown
$admins = $pdo->query("SELECT id, name FROM users WHERE role = 'admin' ORDER BY name ASC")->fetchAll();

$page_title = 'Verification Audit Logs & History';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h2 class="h3 fw-bold text-navy mb-1">
            <i class="bi bi-clock-history text-gold me-2"></i>Verification Audit Logs
        </h2>
        <p class="text-muted mb-0">Immutable, tamper-evident institutional review history for accreditation and audits</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <button onclick="window.print()" class="btn btn-outline-secondary me-2">
            <i class="bi bi-printer me-1"></i>Print Log
        </button>
        <span class="badge bg-navy px-3 py-2 fs-6">
            <i class="bi bi-shield-lock-fill me-1"></i><?= $metricTotal; ?> Total Audit Entries
        </span>
    </div>
</div>

<!-- Quick Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Total Review Actions</div>
                    <div class="fs-4 fw-bold text-navy"><?= $metricTotal; ?></div>
                </div>
                <div class="fs-2 text-primary opacity-50"><i class="bi bi-journal-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-success border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Approvals Logged</div>
                    <div class="fs-4 fw-bold text-success"><?= $metricApproved; ?></div>
                </div>
                <div class="fs-2 text-success opacity-50"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-danger border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Rejections Logged</div>
                    <div class="fs-4 fw-bold text-danger"><?= $metricRejected; ?></div>
                </div>
                <div class="fs-2 text-danger opacity-50"><i class="bi bi-x-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="audit_logs.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search student, ID, event or code..." value="<?= e($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option value="Verified" <?= $actionFilter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="Rejected" <?= $actionFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="Resubmitted" <?= $actionFilter === 'Resubmitted' ? 'selected' : ''; ?>>Resubmitted</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="reviewer_id" class="form-select form-select-sm">
                    <option value="">All Reviewers / Admins</option>
                    <?php foreach ($admins as $adm): ?>
                        <option value="<?= $adm['id']; ?>" <?= $adminFilter === (int)$adm['id'] ? 'selected' : ''; ?>><?= e($adm['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="log_date" class="form-control form-control-sm" value="<?= e($dateFilter); ?>" title="Date Filter">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-navy w-100" title="Apply Filter"><i class="bi bi-filter"></i></button>
                <?php if ($actionFilter !== '' || $adminFilter > 0 || $search !== '' || $dateFilter !== ''): ?>
                    <a href="audit_logs.php" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Audit Log Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-navy mb-0">Audit Trail Records</h5>
            <small class="text-muted">Detailed review trail showing who verified, previous vs new status, and review reasons</small>
        </div>
        <span class="badge bg-secondary"><?= count($logs); ?> Events Displayed</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                <h6 class="fw-bold text-muted mt-2">No Audit Records Found</h6>
                <p class="text-muted small">No logs match the current query criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Reviewer</th>
                            <th>Student & ID</th>
                            <th>Event Details</th>
                            <th>Status Shift</th>
                            <th>Review Notes / Reason</th>
                            <th class="text-center">Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="small text-muted">
                                    <div class="fw-semibold text-dark"><?= format_display_date($log['logged_at']); ?></div>
                                    <div class="text-black-50"><?= date('h:i:s A', strtotime($log['logged_at'])); ?></div>
                                </td>
                                <td>
                                    <?php if ($log['action'] === 'Verified'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                    <?php elseif ($log['action'] === 'Rejected'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                    <?php elseif ($log['action'] === 'Resubmitted'): ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat me-1"></i>Resubmitted</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= e($log['action']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-bold text-navy"><?= e($log['reviewer_name'] ?? 'System'); ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= e($log['reviewer_email'] ?? 'Administrator'); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($log['student_name']); ?></div>
                                    <div class="small text-muted">
                                        <span class="badge bg-light text-dark border"><?= e($log['student_id_code'] ?: 'N/A'); ?></span>
                                        <?= e($log['department']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-navy"><?= e($log['event_name']); ?></div>
                                    <div class="small text-muted"><code><?= e($log['submission_code']); ?></code> | <?= category_badge($log['category']); ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-muted"><?= e($log['previous_status'] ?: 'Submitted'); ?></span>
                                        <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                        <span class="fw-bold text-dark"><?= e($log['new_status']); ?></span>
                                    </div>
                                </td>
                                <td style="max-width: 250px;">
                                    <?php if (!empty($log['reason'])): ?>
                                        <div class="small text-danger bg-danger-subtle p-2 rounded border border-danger-subtle">
                                            <strong>Reason:</strong> <?= e($log['reason']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small"><em>Standard Review Approval</em></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($log['certificate_path'])): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary"
                                                onclick="previewCertificate('../certificate.php?id=<?= $log['activity_id']; ?>', '<?= e(addslashes($log['event_name'])); ?>', '<?= e($log['certificate_original_name']); ?>')">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
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
