<?php
// CampusCred - Automated Project Packaging & ZIP Downloader
require_once __DIR__ . '/config.php';

$zipFilename = 'campuscred_skit021_project.zip';
$zipFilePath = __DIR__ . '/' . $zipFilename;

// Trigger download if requested or directly accessed with ?action=download
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (!class_exists('ZipArchive')) {
        die('PHP ZipArchive extension is required to package this project. Please enable php_zip in php.ini.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Could not create ZIP file in ' . htmlspecialchars(__DIR__));
    }

    $source = realpath(__DIR__);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $file = realpath($file);
        $relativePath = substr($file, strlen($source) + 1);

        // Replace backslashes for standard zip structure
        $relativePath = str_replace('\\', '/', $relativePath);

        // Exclude zip files, git repositories, or IDE files
        if (
            str_starts_with($relativePath, '.git') ||
            str_starts_with($relativePath, '.vscode') ||
            str_ends_with($relativePath, '.zip')
        ) {
            continue;
        }

        if (is_dir($file)) {
            $zip->addEmptyDir($relativePath);
        } elseif (is_file($file)) {
            $zip->addFile($file, $relativePath);
        }
    }

    $zip->close();

    if (file_exists($zipFilePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipFilePath));
        readfile($zipFilePath);
        exit;
    } else {
        die('Failed to generate ZIP archive.');
    }
}

// Build the zip file in background so it's ready on disk
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $source = realpath(__DIR__);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = realpath($file);
            $relativePath = substr($file, strlen($source) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            if (
                str_starts_with($relativePath, '.git') ||
                str_starts_with($relativePath, '.vscode') ||
                str_ends_with($relativePath, '.zip')
            ) {
                continue;
            }

            if (is_dir($file)) {
                $zip->addEmptyDir($relativePath);
            } elseif (is_file($file)) {
                $zip->addFile($file, $relativePath);
            }
        }
        $zip->close();
    }
}

$zipExists = file_exists($zipFilePath);
$zipSize = $zipExists ? round(filesize($zipFilePath) / 1024, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Download Complete Project Package · <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .hero-banner { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .card { border-radius: 14px; border: 1px solid #e2e8f0; }
        .code-box { background: #0f172a; color: #f8fafc; border-radius: 10px; padding: 14px 18px; font-family: monospace; font-size: 0.88rem; }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 820px;">

    <!-- Hero Banner -->
    <div class="hero-banner shadow-sm text-center">
        <span class="badge bg-light text-primary mb-2 px-3 py-1 fw-bold"><?= htmlspecialchars(PROJECT_CODE) ?></span>
        <h2 class="fw-bold mb-1"><?= htmlspecialchars(COLLEGE_SHORT_NAME) ?> — <?= htmlspecialchars(APP_NAME) ?></h2>
        <p class="mb-0 opacity-75">Full-Stack Application One-Click Project Download</p>
    </div>

    <!-- Download Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4 text-center">
            <div class="display-4 text-primary mb-3">
                <i class="bi bi-file-earmark-zip-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Download Complete Project Archive</h4>
            <p class="text-muted small mx-auto mb-3" style="max-width: 600px;">
                Download the complete, production-ready codebase including the new <strong>Corporate Recruiter &amp; Talent Discovery Portal</strong>, Certificate Inspector, <code>recruiter/</code>, <code>student/</code>, and <code>admin/</code> panels, MySQL database schema (<code>database.sql</code>), responsive Bootstrap 5 theme, QR verification engine, Chart.js modules, and sample certificate proofs in one single ZIP package.
            </p>

            <div class="mb-4">
                <a href="download_project.php?action=download" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow-sm rounded-pill">
                    <i class="bi bi-cloud-arrow-down-fill me-2 fs-5 align-middle"></i>Download Complete Project (.ZIP)
                </a>
            </div>

            <?php if ($zipExists): ?>
                <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                    <i class="bi bi-check-circle-fill me-1"></i>Package Ready on Disk: <code><?= htmlspecialchars($zipFilename) ?></code> (<?= $zipSize ?> KB)
                </div>
            <?php endif; ?>
        </div>

        <div class="card-footer bg-light p-3 border-top">
            <div class="row text-center text-secondary small">
                <div class="col-3">
                    <i class="bi bi-code-slash me-1 text-primary"></i>PHP 8+ Backend
                </div>
                <div class="col-3">
                    <i class="bi bi-database me-1 text-success"></i>MySQL + Auto Migrations
                </div>
                <div class="col-3">
                    <i class="bi bi-briefcase me-1 text-warning"></i>Recruiter Portal
                </div>
                <div class="col-3">
                    <i class="bi bi-qr-code me-1 text-dark"></i>QR &amp; Certificate Viewer
                </div>
            </div>
        </div>
    </div>

    <!-- What's included Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 fw-bold">
            <i class="bi bi-stars text-warning me-2"></i>Key Features &amp; Modules Included
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <h6 class="fw-bold text-primary mb-1"><i class="bi bi-building me-1"></i>Company Recruiter Talent Portal</h6>
                        <p class="small text-muted mb-0">Recruiters can browse verified candidates, search by skills/department, inspect student achievement proofs and certificates, manage candidate pipelines, and dispatch interview invites.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <h6 class="fw-bold text-success mb-1"><i class="bi bi-shield-check me-1"></i>Tamper-Evident Verification</h6>
                        <p class="small text-muted mb-0">Every certificate has a cryptographic SHA-256 hash and unique verification UUID. Anyone scanning the QR code can instantly validate authenticity on the public registry.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <h6 class="fw-bold text-dark mb-1"><i class="bi bi-sliders me-1"></i>General Use &amp; Easy Rebranding</h6>
                        <p class="small text-muted mb-0">Flexible configuration via <code>config.php</code> allows any university, college, school, or polytechnic institute to adopt the platform immediately with custom branding.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <h6 class="fw-bold text-info mb-1"><i class="bi bi-mortarboard me-1"></i>Student Career Hub &amp; PDF Dossier</h6>
                        <p class="small text-muted mb-0">Students can manage their career headline, skills tags, receive company offers, print certified achievement transcripts, and share verified public links.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How to extract and run -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 fw-bold">
            <i class="bi bi-folder-check text-primary me-2"></i>How to Run After Downloading
        </div>
        <div class="card-body p-4">
            <ol class="mb-0 ps-3">
                <li class="mb-3">
                    <strong>Extract the ZIP folder:</strong><br>
                    Extract the contents directly into your local XAMPP <code>htdocs</code> directory:
                    <div class="code-box mt-2">C:\xampp\htdocs\campuscred\</div>
                </li>
                <li class="mb-3">
                    <strong>Start Apache &amp; MySQL:</strong><br>
                    Launch your <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> for both Apache and MySQL.
                </li>
                <li class="mb-3">
                    <strong>Open in Browser:</strong><br>
                    Navigate to:
                    <div class="code-box mt-2">http://localhost/campuscred/</div>
                    <em>The app automatically creates the database, migrates all tables, and seeds demo users on first launch!</em>
                </li>
                <li>
                    <strong>Login with Pre-Configured Demo Accounts:</strong><br>
                    <ul>
                        <li><strong>Company Recruiter:</strong> <code>recruiter@techcorp.com</code> / <code>Recruiter@123</code></li>
                        <li><strong>Student:</strong> <code>aravind@skit.ac.in</code> / <code>Student@123</code></li>
                        <li><strong>Admin:</strong> <code>admin@skit.ac.in</code> / <code>Admin@123</code></li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>

    <!-- Navigation link -->
    <div class="text-center">
        <a href="index.php" class="btn btn-outline-secondary btn-sm px-4">
            <i class="bi bi-house me-1"></i>Return to Application
        </a>
    </div>

</div>
</body>
</html>
