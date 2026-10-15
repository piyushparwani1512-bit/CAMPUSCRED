<?php
/**
 * SKIT021 - Student Participation & Achievement Management System
 * Admin Portal: Reports & Analytics Dashboard (Chart.js + CSV + PDF Export)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

// Handle filter inputs
$deptFilter     = trim($_GET['department'] ?? '');
$branchFilter   = trim($_GET['branch'] ?? '');
$yearFilter     = trim($_GET['academic_year'] ?? '');
$catFilter      = trim($_GET['category'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$startDate      = trim($_GET['start_date'] ?? '');
$endDate        = trim($_GET['end_date'] ?? '');
$exportMode     = trim($_GET['export'] ?? '');

// Base query for filtered records
$where = ["1=1"];
$params = [];

if ($deptFilter !== '') {
    $where[] = "u.department = :dept";
    $params[':dept'] = $deptFilter;
}
if ($branchFilter !== '') {
    $where[] = "u.branch = :branch";
    $params[':branch'] = $branchFilter;
}
if ($yearFilter !== '') {
    $where[] = "u.academic_year = :year";
    $params[':year'] = $yearFilter;
}
if ($catFilter !== '') {
    $where[] = "a.category = :cat";
    $params[':cat'] = $catFilter;
}
if ($statusFilter !== '') {
    $where[] = "a.status = :status";
    $params[':status'] = $statusFilter;
}
if ($startDate !== '') {
    $where[] = "a.event_date >= :sdate";
    $params[':sdate'] = $startDate;
}
if ($endDate !== '') {
    $where[] = "a.event_date <= :edate";
    $params[':edate'] = $endDate;
}

$whereClause = implode(" AND ", $where);

// Handle CSV Export
if ($exportMode === 'csv') {
    $csvQuery = "
        SELECT a.submission_code, u.name AS student_name, u.student_id_code, u.department, u.branch, u.academic_year,
               a.event_name, a.category, a.participation_type, a.event_date, a.organizer, a.status,
               rev.name AS reviewer_name, a.reviewed_at, a.rejection_reason
        FROM activities a
        JOIN users u ON a.student_id = u.id
        LEFT JOIN users rev ON a.reviewer_id = rev.id
        WHERE {$whereClause}
        ORDER BY a.event_date DESC
    ";
    $stmtCsv = $pdo->prepare($csvQuery);
    $stmtCsv->execute($params);
    $records = $stmtCsv->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=participation_report_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Submission ID', 'Student Name', 'College ID / Roll No', 'Department', 'Branch', 'Academic Year',
        'Event Name', 'Category', 'Role/Type', 'Event Date', 'Organizer', 'Status',
        'Reviewer', 'Review Date', 'Rejection Reason'
    ]);
    foreach ($records as $row) {
        fputcsv($output, [
            $row['submission_code'],
            $row['student_name'],
            $row['student_id_code'] ?: 'N/A',
            $row['department'],
            $row['branch'] ?: 'N/A',
            $row['academic_year'] ?: 'N/A',
            $row['event_name'],
            $row['category'],
            $row['participation_type'],
            $row['event_date'],
            $row['organizer'],
            $row['status'],
            $row['reviewer_name'] ?? 'N/A',
            $row['reviewed_at'] ?? 'N/A',
            $row['rejection_reason'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Fetch KPI metrics
$kpiQuery = "
    SELECT 
        COUNT(a.id) AS total_submissions,
        COUNT(DISTINCT a.student_id) AS total_students_participated,
        SUM(CASE WHEN a.status = 'Verified' THEN 1 ELSE 0 END) AS total_verified,
        SUM(CASE WHEN a.status IN ('Pending Verification', 'Resubmitted / Pending Review') THEN 1 ELSE 0 END) AS total_pending,
        SUM(CASE WHEN a.status = 'Rejected' THEN 1 ELSE 0 END) AS total_rejected
    FROM activities a
    JOIN users u ON a.student_id = u.id
    WHERE {$whereClause}
";
$stmtKpi = $pdo->prepare($kpiQuery);
$stmtKpi->execute($params);
$kpi = $stmtKpi->fetch();

// Total registered students in institution
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// 1. Department-wise Participation
$deptQuery = "
    SELECT u.department, COUNT(a.id) as count
    FROM activities a
    JOIN users u ON a.student_id = u.id
    WHERE {$whereClause}
    GROUP BY u.department
    ORDER BY count DESC
";
$stmtDept = $pdo->prepare($deptQuery);
$stmtDept->execute($params);
$deptData = $stmtDept->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Category-wise Distribution
$catQuery = "
    SELECT a.category, COUNT(a.id) as count
    FROM activities a
    JOIN users u ON a.student_id = u.id
    WHERE {$whereClause}
    GROUP BY a.category
    ORDER BY count DESC
";
$stmtCat = $pdo->prepare($catQuery);
$stmtCat->execute($params);
$catData = $stmtCat->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Status Distribution
$statusQuery = "
    SELECT a.status, COUNT(a.id) as count
    FROM activities a
    JOIN users u ON a.student_id = u.id
    WHERE {$whereClause}
    GROUP BY a.status
";
$stmtStatus = $pdo->prepare($statusQuery);
$stmtStatus->execute($params);
$statusData = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Monthly Trend (last 12 months)
$trendQuery = "
    SELECT DATE_FORMAT(a.event_date, '%Y-%m') AS ym, COUNT(a.id) as count
    FROM activities a
    JOIN users u ON a.student_id = u.id
    WHERE {$whereClause} AND a.event_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
";
$stmtTrend = $pdo->prepare($trendQuery);
$stmtTrend->execute($params);
$trendRows = $stmtTrend->fetchAll();
$trendLabels = [];
$trendValues = [];
foreach ($trendRows as $tr) {
    $dateObj = DateTime::createFromFormat('Y-m', $tr['ym']);
    $trendLabels[] = $dateObj ? $dateObj->format('M Y') : $tr['ym'];
    $trendValues[] = (int)$tr['count'];
}

// 5. Filtered activities for detailed table
$tableQuery = "
    SELECT a.*, u.name AS student_name, u.student_id_code, u.department, u.branch, u.academic_year,
           rev.name AS reviewer_name
    FROM activities a
    JOIN users u ON a.student_id = u.id
    LEFT JOIN users rev ON a.reviewer_id = rev.id
    WHERE {$whereClause}
    ORDER BY a.event_date DESC
    LIMIT 200
";
$stmtTable = $pdo->prepare($tableQuery);
$stmtTable->execute($params);
$tableRecords = $stmtTable->fetchAll();

// Build query string for re-exporting
$queryParams = $_GET;
unset($queryParams['export']);
$csvExportUrl = 'reports.php?' . http_build_query(array_merge($queryParams, ['export' => 'csv']));

$page_title = 'Institutional Analytics & Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h2 class="h3 fw-bold text-navy mb-1">
            <i class="bi bi-bar-chart-fill text-gold me-2"></i>Institutional Analytics & Reports
        </h2>
        <p class="text-muted mb-0">Dynamic participation trends, department metrics, and exportable verification audits</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="<?= $csvExportUrl; ?>" class="btn btn-outline-success me-2">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
        </a>
        <button onclick="exportReportToPDF()" class="btn btn-gold text-navy fw-semibold">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </button>
    </div>
</div>

<!-- Filter Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold text-navy mb-0">
            <i class="bi bi-funnel-fill text-gold me-2"></i>Report Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="reports.php" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Department</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    <?php foreach (DEPARTMENTS as $dept): ?>
                        <option value="<?= $dept; ?>" <?= $deptFilter === $dept ? 'selected' : ''; ?>><?= $dept; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Academic Year</label>
                <select name="academic_year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    <option value="1st Year" <?= $yearFilter === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2nd Year" <?= $yearFilter === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3rd Year" <?= $yearFilter === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4th Year" <?= $yearFilter === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Activity Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach (ACTIVITY_CATEGORIES as $cat): ?>
                        <option value="<?= $cat; ?>" <?= $catFilter === $cat ? 'selected' : ''; ?>><?= $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Verification Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Verified" <?= $statusFilter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="Pending Verification" <?= $statusFilter === 'Pending Verification' ? 'selected' : ''; ?>>Pending Verification</option>
                    <option value="Resubmitted / Pending Review" <?= $statusFilter === 'Resubmitted / Pending Review' ? 'selected' : ''; ?>>Resubmitted / Review</option>
                    <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Event Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="start_date" class="form-control" value="<?= e($startDate); ?>" title="From Date">
                    <span class="input-group-text">to</span>
                    <input type="date" name="end_date" class="form-control" value="<?= e($endDate); ?>" title="To Date">
                </div>
            </div>
            <div class="col-12 text-end">
                <a href="reports.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters
                </a>
                <button type="submit" class="btn btn-sm btn-navy">
                    <i class="bi bi-filter me-1"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Total Submissions</div>
                    <div class="fs-3 fw-bold text-navy"><?= (int)$kpi['total_submissions']; ?></div>
                </div>
                <div class="fs-1 text-primary opacity-50"><i class="bi bi-collection"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm border-start border-success border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Verified Achievements</div>
                    <div class="fs-3 fw-bold text-success"><?= (int)$kpi['total_verified']; ?></div>
                </div>
                <div class="fs-1 text-success opacity-50"><i class="bi bi-patch-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm border-start border-warning border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Pending / In Review</div>
                    <div class="fs-3 fw-bold text-warning"><?= (int)$kpi['total_pending']; ?></div>
                </div>
                <div class="fs-1 text-warning opacity-50"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm border-start border-info border-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Participating Students</div>
                    <div class="fs-3 fw-bold text-info"><?= (int)$kpi['total_students_participated']; ?> / <?= $totalStudents; ?></div>
                </div>
                <div class="fs-1 text-info opacity-50"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Visualizations -->
<div class="row g-4 mb-4" id="reportChartsContainer">
    <!-- Chart 1: Department-wise Participation -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-navy mb-0">
                    <i class="bi bi-bar-chart-line text-gold me-2"></i>Department-wise Participation
                </h6>
            </div>
            <div class="card-body">
                <canvas id="deptChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Category Distribution -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-navy mb-0">
                    <i class="bi bi-pie-chart text-gold me-2"></i>Category-wise Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="catChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Monthly Trend -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-navy mb-0">
                    <i class="bi bi-graph-up-arrow text-gold me-2"></i>Monthly Participation Trends (Last 12 Months)
                </h6>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 4: Verification Status -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-navy mb-0">
                    <i class="bi bi-shield-check text-gold me-2"></i>Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Filtered Data Table -->
<div class="card border-0 shadow-sm" id="reportTableCard">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-navy mb-0">Filtered Activity Records</h5>
            <small class="text-muted">Displaying up to 200 matching records matching filter criteria</small>
        </div>
        <span class="badge bg-secondary"><?= count($tableRecords); ?> Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tableRecords)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h6 class="fw-bold text-muted mt-2">No Records Found</h6>
                <p class="text-muted small">Try relaxing your filter parameters to view participation logs.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reportTable">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Submission ID</th>
                            <th>Student</th>
                            <th>Department</th>
                            <th>Event Name</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Reviewer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableRecords as $row): ?>
                            <tr>
                                <td><code><?= e($row['submission_code']); ?></code></td>
                                <td>
                                    <div class="fw-bold text-navy"><?= e($row['student_name']); ?></div>
                                    <div class="small text-muted"><?= e($row['student_id_code'] ?: 'N/A'); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($row['department']); ?></div>
                                    <div class="small text-muted"><?= e($row['branch'] ?: 'N/A'); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['event_name']); ?></div>
                                    <div class="small text-muted"><?= e($row['organizer']); ?></div>
                                </td>
                                <td><?= category_badge($row['category']); ?></td>
                                <td class="small"><?= format_display_date($row['event_date']); ?></td>
                                <td><?= status_badge($row['status']); ?></td>
                                <td class="small text-muted"><?= e($row['reviewer_name'] ?? 'Pending'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Initializer & PDF Export Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Department Chart
    const deptLabels = <?= json_encode(array_keys($deptData)); ?>;
    const deptValues = <?= json_encode(array_values($deptData)); ?>;
    const ctxDept = document.getElementById('deptChart');
    if (ctxDept) {
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: deptLabels.length ? deptLabels : ['No Data'],
                datasets: [{
                    label: 'Submissions',
                    data: deptValues.length ? deptValues : [0],
                    backgroundColor: 'rgba(11, 37, 69, 0.85)',
                    borderColor: '#0b2545',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Category Chart
    const catLabels = <?= json_encode(array_keys($catData)); ?>;
    const catValues = <?= json_encode(array_values($catData)); ?>;
    const ctxCat = document.getElementById('catChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['No Data'],
                datasets: [{
                    data: catValues.length ? catValues : [1],
                    backgroundColor: [
                        '#0d6efd', '#0dcaf0', '#6f42c1', '#ffc107', '#fd7e14', '#6c757d'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 3. Monthly Trend Chart
    const trendLabels = <?= json_encode($trendLabels); ?>;
    const trendValues = <?= json_encode($trendValues); ?>;
    const ctxTrend = document.getElementById('trendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: trendLabels.length ? trendLabels : ['Current'],
                datasets: [{
                    label: 'Submissions Count',
                    data: trendValues.length ? trendValues : [0],
                    borderColor: '#c69214',
                    backgroundColor: 'rgba(198, 146, 20, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#0b2545',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 4. Status Chart
    const statusLabels = <?= json_encode(array_keys($statusData)); ?>;
    const statusValues = <?= json_encode(array_values($statusData)); ?>;
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: statusLabels.length ? statusLabels : ['No Data'],
                datasets: [{
                    data: statusValues.length ? statusValues : [1],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0dcaf0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});

// PDF Export Function using jsPDF
function exportReportToPDF() {
    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert("PDF generator library is initializing. Please click again in a moment or use Print (Ctrl+P).");
        window.print();
        return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: "landscape",
        unit: "pt",
        format: "a4"
    });

    // College Header
    doc.setFillColor(11, 37, 69);
    doc.rect(0, 0, 842, 60, "F");

    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text("SWAMI KESHVANAND INSTITUTE OF TECHNOLOGY, JAIPUR", 40, 28);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text("Student Participation & Achievement Management System (SKIT021) — Executive Report", 40, 46);

    // Meta details
    doc.setTextColor(50, 50, 50);
    doc.setFontSize(10);
    const dateStr = "Report Generated: " + new Date().toLocaleString();
    doc.text(dateStr, 40, 85);

    // Filter Summary
    const filterInfo = "Filtered Records: <?= count($tableRecords); ?> | Total Submissions: <?= (int)$kpi['total_submissions']; ?> | Verified: <?= (int)$kpi['total_verified']; ?> | Pending: <?= (int)$kpi['total_pending']; ?>";
    doc.text(filterInfo, 40, 102);

    // Render Table Data onto PDF
    const startY = 125;
    let currentY = startY;

    // Header row
    doc.setFillColor(235, 240, 245);
    doc.rect(40, currentY, 762, 22, "F");
    doc.setFont("helvetica", "bold");
    doc.setFontSize(9);
    doc.setTextColor(11, 37, 69);

    doc.text("ID", 45, currentY + 15);
    doc.text("Student Name", 130, currentY + 15);
    doc.text("Roll / College ID", 240, currentY + 15);
    doc.text("Department", 330, currentY + 15);
    doc.text("Event Name", 430, currentY + 15);
    doc.text("Category", 570, currentY + 15);
    doc.text("Event Date", 650, currentY + 15);
    doc.text("Status", 730, currentY + 15);

    currentY += 26;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(40, 40, 40);

    // Table rows
    const tableRows = document.querySelectorAll("#reportTable tbody tr");
    if (tableRows.length === 0) {
        doc.text("No matching records found for the selected filters.", 45, currentY + 15);
    } else {
        tableRows.forEach((tr, index) => {
            if (index >= 25) return; // Print top 25 rows per PDF page
            const cells = tr.querySelectorAll("td");
            if (cells.length >= 7) {
                const subId = cells[0].innerText.trim();
                const studentName = cells[1].querySelector(".fw-bold")?.innerText.trim() || cells[1].innerText.trim();
                const rollNo = cells[1].querySelector(".small")?.innerText.trim() || "";
                const dept = cells[2].querySelector(".fw-semibold")?.innerText.trim() || cells[2].innerText.trim();
                const eventName = (cells[3].querySelector(".fw-semibold")?.innerText.trim() || cells[3].innerText.trim()).substring(0, 22);
                const category = cells[4].innerText.trim();
                const eventDate = cells[5].innerText.trim();
                const status = cells[6].innerText.trim();

                if (index % 2 === 1) {
                    doc.setFillColor(248, 249, 250);
                    doc.rect(40, currentY - 12, 762, 18, "F");
                }

                doc.text(subId, 45, currentY);
                doc.text(studentName.substring(0, 18), 130, currentY);
                doc.text(rollNo, 240, currentY);
                doc.text(dept.substring(0, 16), 330, currentY);
                doc.text(eventName, 430, currentY);
                doc.text(category, 570, currentY);
                doc.text(eventDate, 650, currentY);
                doc.text(status, 730, currentY);

                currentY += 18;
            }
        });
    }

    // Footer note
    doc.setFontSize(8);
    doc.setTextColor(130, 130, 130);
    doc.text("Confidential — Generated for Authorized SKIT Administration & Institutional Audits only.", 40, 560);

    doc.save("SKIT021_Participation_Report_" + new Date().toISOString().slice(0, 10) + ".pdf");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
