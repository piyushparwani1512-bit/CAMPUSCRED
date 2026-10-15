<?php
/**
 * SKIT021 - Student Participation & Achievement Management System
 * Admin Portal: Centralized Student Directory & Unified Cross-Department Profiles
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$search = trim($_GET['search'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');
$yearFilter = trim($_GET['academic_year'] ?? '');

$student = null;
$verifiedActivities = [];
$allActivities = [];
$stats = [
    'total' => 0,
    'verified' => 0,
    'pending' => 0,
    'rejected' => 0,
    'academic' => 0,
    'technical' => 0,
    'cultural' => 0,
    'sports' => 0,
    'extracurricular' => 0,
    'other' => 0
];

if ($studentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'student' LIMIT 1");
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch();

    if ($student) {
        // Fetch verified activities only for the consolidated record
        $stmtV = $pdo->prepare("
            SELECT a.*, u.name AS reviewer_name 
            FROM activities a 
            LEFT JOIN users u ON a.reviewer_id = u.id 
            WHERE a.student_id = :uid AND a.status = 'Verified' 
            ORDER BY a.event_date DESC
        ");
        $stmtV->execute([':uid' => $studentId]);
        $verifiedActivities = $stmtV->fetchAll();

        // Fetch full participation history including pending, rejected, resubmitted
        $stmtAll = $pdo->prepare("
            SELECT a.*, u.name AS reviewer_name 
            FROM activities a 
            LEFT JOIN users u ON a.reviewer_id = u.id 
            WHERE a.student_id = :uid 
            ORDER BY a.created_at DESC
        ");
        $stmtAll->execute([':uid' => $studentId]);
        $allActivities = $stmtAll->fetchAll();

        // Compute metrics
        foreach ($allActivities as $act) {
            $stats['total']++;
            if ($act['status'] === 'Verified') $stats['verified']++;
            elseif ($act['status'] === 'Rejected') $stats['rejected']++;
            else $stats['pending']++;

            $catKey = strtolower($act['category']);
            if (isset($stats[$catKey])) {
                $stats[$catKey]++;
            } else {
                $stats['other']++;
            }
        }
    }
}

// Student listing query
$listQuery = "
    SELECT u.id, u.name, u.student_id_code, u.department, u.branch, u.academic_year, u.email,
           COUNT(a.id) AS total_submissions,
           SUM(CASE WHEN a.status = 'Verified' THEN 1 ELSE 0 END) AS verified_count,
           SUM(CASE WHEN a.status IN ('Pending Verification', 'Resubmitted / Pending Review') THEN 1 ELSE 0 END) AS pending_count
    FROM users u
    LEFT JOIN activities a ON u.id = a.student_id
    WHERE u.role = 'student'
";
$params = [];

if ($search !== '') {
    $listQuery .= " AND (u.name LIKE :search OR u.student_id_code LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%{$search}%";
}
if ($deptFilter !== '') {
    $listQuery .= " AND u.department = :dept";
    $params[':dept'] = $deptFilter;
}
if ($yearFilter !== '') {
    $listQuery .= " AND u.academic_year = :year";
    $params[':year'] = $yearFilter;
}

$listQuery .= " GROUP BY u.id ORDER BY u.name ASC";
$stmtList = $pdo->prepare($listQuery);
$stmtList->execute($params);
$studentsList = $stmtList->fetchAll();

$page_title = 'Centralized Student Participation Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h2 class="h3 fw-bold text-navy mb-1">
            <i class="bi bi-person-lines-fill text-gold me-2"></i>Centralized Student Participation Directory
        </h2>
        <p class="text-muted mb-0">Unified institutional profiles aggregating participation across all academic departments</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <?php if ($student): ?>
            <a href="student_records.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>Back to Directory
            </a>
            <button onclick="window.print()" class="btn btn-gold text-navy fw-semibold">
                <i class="bi bi-printer me-1"></i>Print Profile
            </button>
        <?php else: ?>
            <span class="badge bg-navy px-3 py-2 fs-6">
                <i class="bi bi-people-fill me-1"></i><?= count($studentsList); ?> Registered Students
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($student): ?>
    <!-- Single Student Consolidated Profile View -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 bg-light rounded-3">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <div class="rounded-circle bg-navy text-gold d-inline-flex align-items-center justify-content-center shadow" style="width: 85px; height: 85px; font-size: 2.2rem; font-weight: 700;">
                        <?= strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="h4 fw-bold text-navy mb-0"><?= e($student['name']); ?></h3>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Active Student</span>
                    </div>
                    <div class="text-muted small mb-2">
                        <span class="fw-semibold text-dark">College ID:</span> <span class="badge bg-secondary"><?= e($student['student_id_code'] ?: 'N/A'); ?></span> |
                        <span class="fw-semibold text-dark">Email:</span> <?= e($student['email']); ?>
                    </div>
                    <div class="row g-2 text-muted small">
                        <div class="col-sm-6">
                            <i class="bi bi-building me-1 text-gold"></i><strong>Dept:</strong> <?= e($student['department']); ?>
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-diagram-3 me-1 text-gold"></i><strong>Branch:</strong> <?= e($student['branch'] ?: 'N/A'); ?>
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-mortarboard me-1 text-gold"></i><strong>Class/Year:</strong> <?= e($student['academic_year'] ?: 'N/A'); ?>
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-calendar-check me-1 text-gold"></i><strong>Enrolled:</strong> <?= format_display_date($student['created_at']); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="p-3 bg-white border rounded text-center">
                        <div class="text-uppercase text-muted small fw-bold mb-1">Official Institutional Standing</div>
                        <div class="fs-4 fw-bold text-success mb-1">
                            <i class="bi bi-shield-fill-check me-1"></i><?= $stats['verified']; ?> Verified
                        </div>
                        <div class="small text-muted">Out of <?= $stats['total']; ?> total submissions recorded</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 text-center py-2">
                <div class="text-muted small fw-semibold">Total Submissions</div>
                <div class="fs-4 fw-bold text-primary"><?= $stats['total']; ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 text-center py-2">
                <div class="text-muted small fw-semibold">Verified Records</div>
                <div class="fs-4 fw-bold text-success"><?= $stats['verified']; ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm border-start border-warning border-4 text-center py-2">
                <div class="text-muted small fw-semibold">Pending / In Review</div>
                <div class="fs-4 fw-bold text-warning"><?= $stats['pending']; ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm border-start border-danger border-4 text-center py-2">
                <div class="text-muted small fw-semibold">Rejected Submissions</div>
                <div class="fs-4 fw-bold text-danger"><?= $stats['rejected']; ?></div>
            </div>
        </div>
    </div>

    <!-- Category Participation Breakdown -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-navy mb-0">
                <i class="bi bi-pie-chart-fill text-gold me-2"></i>Multi-Department Category Participation
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-book text-primary fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Academic</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['academic']; ?></div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-cpu text-info fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Technical</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['technical']; ?></div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-music-note-beamed text-purple fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Cultural</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['cultural']; ?></div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-trophy text-warning fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Sports</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['sports']; ?></div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-stars text-danger fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Extracurricular</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['extracurricular']; ?></div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="p-2 border rounded bg-light">
                        <i class="bi bi-grid text-secondary fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold text-muted">Other</div>
                        <div class="fs-5 fw-bold text-navy"><?= $stats['other']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Tabs: Consolidated Verified Record vs Full History -->
    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-navy" id="verified-tab" data-bs-toggle="tab" data-bs-target="#verified-pane" type="button" role="tab">
                <i class="bi bi-patch-check-fill text-success me-1"></i>Consolidated Verified Achievements (<?= count($verifiedActivities); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-navy" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-pane" type="button" role="tab">
                <i class="bi bi-clock-history text-secondary me-1"></i>Complete Participation & Review Audit (<?= count($allActivities); ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">
        <!-- Tab 1: Consolidated Verified Record Only -->
        <div class="tab-pane fade show active" id="verified-pane" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-navy mb-0">Official Verified Records</h6>
                        <small class="text-muted">Strictly verified achievements approved by college administration</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                        Tamper-Evident QR Enabled
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($verifiedActivities)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-shield-slash text-muted" style="font-size: 3rem;"></i>
                            <h6 class="fw-bold text-muted mt-2">No Verified Achievements Yet</h6>
                            <p class="text-muted small">Only admin-approved submissions appear in the consolidated verified transcript.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th>Submission ID</th>
                                        <th>Event / Activity</th>
                                        <th>Category</th>
                                        <th>Role / Type</th>
                                        <th>Event Date</th>
                                        <th>Reviewer / Verified On</th>
                                        <th class="text-center">Certificate</th>
                                        <th class="text-center">QR Verification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verifiedActivities as $act): ?>
                                        <tr>
                                            <td>
                                                <code class="fw-bold text-navy"><?= e($act['submission_code']); ?></code>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-navy"><?= e($act['event_name']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($act['organizer']); ?></div>
                                            </td>
                                            <td><?= category_badge($act['category']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-dark border">
                                                    <?= e($act['participation_type']); ?>
                                                </span>
                                            </td>
                                            <td><?= format_display_date($act['event_date']); ?></td>
                                            <td>
                                                <div class="small fw-semibold text-dark">
                                                    <i class="bi bi-person-check me-1 text-success"></i><?= e($act['reviewer_name'] ?? 'Admin Office'); ?>
                                                </div>
                                                <div class="small text-muted"><?= format_display_datetime($act['reviewed_at']); ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($act['certificate_path'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="previewCertificate('../certificate.php?id=<?= $act['id']; ?>', '<?= e(addslashes($act['event_name'])); ?>', '<?= e($act['certificate_original_name']); ?>')">
                                                        <i class="bi bi-file-earmark-check me-1"></i>View Proof
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="../verify_credential.php?code=<?= urlencode($act['verification_uuid']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-qr-code-scan me-1"></i>Verify
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

        <!-- Tab 2: Complete Participation & Review Audit -->
        <div class="tab-pane fade" id="all-pane" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-navy mb-0">Complete Institutional Audit Log</h6>
                    <small class="text-muted">Chronological trail of all submissions, review decisions, and resubmission cycles</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($allActivities)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
                            <h6 class="fw-bold text-muted mt-2">No Activities Submitted</h6>
                            <p class="text-muted small">This student has not submitted any activities yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th>Submitted On</th>
                                        <th>ID & Activity</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Reviewer & Notes</th>
                                        <th>Certificate</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allActivities as $act): ?>
                                        <tr>
                                            <td class="small text-muted">
                                                <?= format_display_date($act['created_at']); ?><br>
                                                <span class="text-black-50"><?= date('h:i A', strtotime($act['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-navy"><?= e($act['event_name']); ?></div>
                                                <div class="small text-muted"><code><?= e($act['submission_code']); ?></code> | <?= e($act['organizer']); ?></div>
                                            </td>
                                            <td><?= category_badge($act['category']); ?></td>
                                            <td><?= status_badge($act['status']); ?></td>
                                            <td>
                                                <?php if ($act['status'] === 'Verified'): ?>
                                                    <div class="small text-success fw-semibold">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Approved by <?= e($act['reviewer_name'] ?? 'Admin'); ?>
                                                    </div>
                                                    <div class="small text-muted"><?= format_display_datetime($act['reviewed_at']); ?></div>
                                                <?php elseif ($act['status'] === 'Rejected'): ?>
                                                    <div class="small text-danger fw-semibold">
                                                        <i class="bi bi-x-circle-fill me-1"></i>Rejected by <?= e($act['reviewer_name'] ?? 'Admin'); ?>
                                                    </div>
                                                    <div class="small text-danger bg-danger-subtle p-1 rounded mt-1 border border-danger-subtle">
                                                        <strong>Reason:</strong> <?= e($act['rejection_reason'] ?: 'Requirements not satisfied'); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-warning fw-semibold">
                                                        <i class="bi bi-clock-history me-1"></i>Awaiting Admin Review
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($act['certificate_path'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary"
                                                            onclick="previewCertificate('../certificate.php?id=<?= $act['id']; ?>', '<?= e(addslashes($act['event_name'])); ?>', '<?= e($act['certificate_original_name']); ?>')">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (in_array($act['status'], ['Pending Verification', 'Resubmitted / Pending Review'])): ?>
                                                    <a href="verify.php?status=all" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-shield-check me-1"></i>Review
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Completed</span>
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
    </div>

<?php else: ?>
    <!-- Student Directory Listing View -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="student_records.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by student name, roll number, or email..." value="<?= e($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach (DEPARTMENTS as $dept): ?>
                            <option value="<?= $dept; ?>" <?= $deptFilter === $dept ? 'selected' : ''; ?>><?= $dept; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="academic_year" class="form-select">
                        <option value="">All Years</option>
                        <option value="1st Year" <?= $yearFilter === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2nd Year" <?= $yearFilter === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3rd Year" <?= $yearFilter === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4th Year" <?= $yearFilter === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if ($search !== '' || $deptFilter !== '' || $yearFilter !== ''): ?>
                        <a href="student_records.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-navy mb-0">Student Registry & Participation Summary</h5>
            <span class="badge bg-secondary"><?= count($studentsList); ?> Students Found</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($studentsList)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h6 class="fw-bold text-muted mt-2">No Students Found</h6>
                    <p class="text-muted small">No student records match your query.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Student Details</th>
                                <th>College ID / Roll No</th>
                                <th>Department</th>
                                <th>Branch / Year</th>
                                <th class="text-center">Total Submitted</th>
                                <th class="text-center">Verified Records</th>
                                <th class="text-center">Pending Review</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentsList as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-navy text-gold fw-bold d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                                <?= strtoupper(substr($s['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <a href="student_records.php?student_id=<?= $s['id']; ?>" class="fw-bold text-navy text-decoration-none">
                                                    <?= e($s['name']); ?>
                                                </a>
                                                <div class="small text-muted"><?= e($s['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= e($s['student_id_code'] ?: 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><span class="fw-semibold text-dark"><?= e($s['department']); ?></span></td>
                                    <td class="small text-muted">
                                        <?= e($s['branch'] ?: 'N/A'); ?> &bull; <?= e($s['academic_year'] ?: 'N/A'); ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= (int)$s['total_submissions']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                                            <?= (int)$s['verified_count']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int)$s['pending_count'] > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2">
                                                <?= (int)$s['pending_count']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="student_records.php?student_id=<?= $s['id']; ?>" class="btn btn-sm btn-outline-navy">
                                            <i class="bi bi-folder2-open me-1"></i>View Profile
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
