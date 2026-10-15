<?php
// CampusCred - System Setup & Database Seeder Utility
define('ALLOW_DB_FAILURE', true);
require_once __DIR__ . '/config.php';

$message = '';
$messageType = 'info';

// Check prerequisites
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$uploadDir = __DIR__ . '/uploads';
$uploadDirOk = is_dir($uploadDir) && is_writable($uploadDir);
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
    $uploadDirOk = is_dir($uploadDir) && is_writable($uploadDir);
}

// Create sample placeholder certificate files so downloads and views work
$sampleFiles = [
    'sample_cert_sih.pdf' => "%PDF-1.4\n1 0 obj\n<< /Title (Smart India Hackathon 2025 Certificate) /Creator (AICTE) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n5 0 obj\n<< /Length 120 >>\nstream\nBT\n/F1 24 Tf\n50 700 Td\n(Smart India Hackathon 2025 - Certificate of Merit) Tj\nET\nendstream\nendobj\n6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\nxref\n0 7\n0000000000 65535 f\n0000000010 00000 n\n0000000095 00000 n\n0000000144 00000 n\n0000000207 00000 n\n0000000344 00000 n\n0000000516 00000 n\ntrailer\n<< /Size 7 /Root 2 0 R >>\nstartxref\n598\n%%EOF",
    'sample_cert_paper.pdf' => "%PDF-1.4\n1 0 obj\n<< /Title (IEEE Paper Certificate) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n5 0 obj\n<< /Length 110 >>\nstream\nBT\n/F1 20 Tf\n50 700 Td\n(IEEE Research Presentation Certificate) Tj\nET\nendstream\nendobj\n6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\ntrailer\n<< /Size 7 /Root 2 0 R >>\n%%EOF",
    'sample_cert_icpc.pdf' => "%PDF-1.4\n1 0 obj\n<< /Title (ACM ICPC Finalist Certificate) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n5 0 obj\n<< /Length 110 >>\nstream\nBT\n/F1 20 Tf\n50 700 Td\n(ACM ICPC Regional Finalist Certificate) Tj\nET\nendstream\nendobj\n6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\ntrailer\n<< /Size 7 /Root 2 0 R >>\n%%EOF",
    'sample_cert_nss.pdf' => "%PDF-1.4\n1 0 obj\n<< /Title (NSS Certificate) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n5 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 20 Tf\n50 700 Td\n(NSS Social Service Certificate) Tj\nET\nendstream\nendobj\n6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\ntrailer\n<< /Size 7 /Root 2 0 R >>\n%%EOF",
    'sample_cert_redcross.pdf' => "%PDF-1.4\n1 0 obj\n<< /Title (Red Cross Certificate) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 612 792] /Contents 5 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n5 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 20 Tf\n50 700 Td\n(Indian Red Cross Society Citation) Tj\nET\nendstream\nendobj\n6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\ntrailer\n<< /Size 7 /Root 2 0 R >>\n%%EOF",
];

$sampleJpg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
$sampleFiles['sample_cert_pravah.jpg'] = $sampleJpg;
$sampleFiles['sample_cert_sports.jpg'] = $sampleJpg;
$sampleFiles['sample_cert_mnit.jpg'] = $sampleJpg;

foreach ($sampleFiles as $fName => $content) {
    $filePath = $uploadDir . '/' . $fName;
    if (!file_exists($filePath)) {
        @file_put_contents($filePath, $content);
    }
}

// Handle Reset / Seed Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $initPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $initPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        if ($_POST['action'] === 'reset_seed') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("DROP TABLE IF EXISTS recruitment_inquiries;");
            $pdo->exec("DROP TABLE IF EXISTS recruiter_bookmarks;");
            $pdo->exec("DROP TABLE IF EXISTS verification_logs;");
            $pdo->exec("DROP TABLE IF EXISTS activities;");
            $pdo->exec("DROP TABLE IF EXISTS users;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            require_once __DIR__ . '/db.php';
            initialize_database_tables($pdo);

            $message = 'Database successfully reset and re-seeded with demo students, recruiters, and verified achievements!';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error during database operation: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch current stats if connected
$userCount = 0;
$activityCount = 0;
$recruiterCount = 0;
$dbConnected = false;
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbConnected = true;
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $activityCount = (int)$pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
    $recruiterCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'")->fetchColumn();
} catch (Throwable $t) {
    $dbConnected = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Setup &amp; Seeder · <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .hero-banner { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .card { border-radius: 12px; border: 1px solid #e2e8f0; }
        .badge-status { font-size: 13px; font-weight: 600; }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 900px;">

    <div class="hero-banner shadow-sm text-center">
        <span class="badge bg-light text-primary mb-2 px-3 py-1"><?= htmlspecialchars(PROJECT_CODE) ?></span>
        <h2 class="fw-bold mb-1"><?= htmlspecialchars(COLLEGE_SHORT_NAME) ?> — <?= htmlspecialchars(APP_NAME) ?></h2>
        <p class="mb-0 opacity-75">Automated System Health, Database Migrations &amp; Demo Seed Setup</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <!-- Environment Check -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-hdd-network text-primary me-2"></i>Environment Prerequisites
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP 8.0+
                            <span class="badge bg-<?= $phpOk ? 'success' : 'danger' ?>"><?= PHP_VERSION ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            MySQL Connection
                            <span class="badge bg-<?= $dbConnected ? 'success' : 'danger' ?>"><?= $dbConnected ? 'Connected' : 'Disconnected' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Uploads Folder Writable
                            <span class="badge bg-<?= $uploadDirOk ? 'success' : 'danger' ?>"><?= $uploadDirOk ? 'Ready (/uploads)' : 'Not Writable' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Database Name
                            <span class="badge bg-secondary"><?= htmlspecialchars(DB_NAME) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Current Database State -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-database-check text-success me-2"></i>Database Status
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h3 class="fw-bold text-primary mb-0"><?= $userCount ?></h3>
                                <small class="text-muted">Registered Users</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h3 class="fw-bold text-success mb-0"><?= $activityCount ?></h3>
                                <small class="text-muted">Total Submissions</small>
                            </div>
                        </div>
                    </div>
                    <form method="post" onsubmit="return confirm('Reset and re-seed all tables with clean demo data?');">
                        <input type="hidden" name="action" value="reset_seed">
                        <button type="submit" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset &amp; Seed Clean Demo Data
                        </button>
                    </form>
                    <div>
                        <a href="index.php" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Open Application
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credentials Reference Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold py-3">
            <i class="bi bi-key-fill text-warning me-2"></i>Pre-Configured Demo Credentials
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Role</th>
                            <th>Name</th>
                            <th>College ID / Email</th>
                            <th>Department</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-danger">Administrator</span></td>
                            <td>Dr. Rajesh Sharma</td>
                            <td><code>admin@skit.ac.in</code> <br><small class="text-muted">or SKIT/ADM/001</small></td>
                            <td>CSE</td>
                            <td><code>Admin@123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">Student (3rd Yr)</span></td>
                            <td>Aravind Swaminathan</td>
                            <td><code>aravind@skit.ac.in</code> <br><small class="text-muted">or SKIT/2023/CSE/014</small></td>
                            <td>Computer Science</td>
                            <td><code>Student@123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">Student (4th Yr)</span></td>
                            <td>Priya Sharma</td>
                            <td><code>priya.sharma@skit.ac.in</code> <br><small class="text-muted">or SKIT/2022/IT/088</small></td>
                            <td>Information Technology</td>
                            <td><code>Student@123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark fw-bold">Company Recruiter</span></td>
                            <td>Pooja Malhotra</td>
                            <td><code>recruiter@techcorp.com</code> <br><small class="text-muted">TechCorp Labs</small></td>
                            <td>Talent Acquisition</td>
                            <td><code>Recruiter@123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">Student (2nd Yr)</span></td>
                            <td>Rohit Verma</td>
                            <td><code>rohit.verma@skit.ac.in</code> <br><small class="text-muted">or SKIT/2024/ECE/105</small></td>
                            <td>ECE</td>
                            <td><code>Student@123</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light text-muted small">
            Tip: You can also use the <strong>1-Click Demo Login</strong> buttons on the login screen to sign in instantly.
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
