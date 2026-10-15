<?php
/**
 * SKIT021 - Student Participation & Achievement Management System
 * Secure Certificate Streamer & Access-Controlled File Viewer
 */
require_once __DIR__ . '/includes/bootstrap.php';

$currentUser = current_user();

// Access control check
$activityId = (int)($_GET['id'] ?? 0);
$subId = trim($_GET['sub_id'] ?? '');
$uuid = trim($_GET['uuid'] ?? '');

if ($activityId <= 0 && empty($subId) && empty($uuid)) {
    http_response_code(400);
    exit('Invalid certificate request identifier.');
}

// Fetch activity
if ($activityId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $activityId]);
} elseif (!empty($uuid)) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE verification_uuid = :uuid LIMIT 1");
    $stmt->execute([':uuid' => $uuid]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE submission_code = :sid LIMIT 1");
    $stmt->execute([':sid' => $subId]);
}
$activity = $stmt->fetch();

if (!$activity) {
    http_response_code(404);
    exit('Certificate record not found.');
}

// Access Permissions:
// 1. Admins have global access
// 2. The submitting student has access
// 3. Registered recruiters have access to all 'Verified' credentials
// 4. Visitors with the authentic verification_uuid have access to 'Verified' credentials
$userRole = is_logged_in() ? ($currentUser['role'] ?? '') : 'guest';
$isOwner = is_logged_in() && (int)$activity['student_id'] === (int)($currentUser['id'] ?? 0);
$isVerified = ($activity['status'] === 'Verified');
$hasValidUuid = (!empty($uuid) && $activity['verification_uuid'] === $uuid && $isVerified);

$allowed = false;
if ($userRole === 'admin') {
    $allowed = true;
} elseif ($isOwner) {
    $allowed = true;
} elseif ($userRole === 'recruiter' && $isVerified) {
    $allowed = true;
} elseif ($hasValidUuid) {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    exit('Access denied. You do not possess authorized credentials or this achievement has not yet been verified.');
}

$storedPath = $activity['certificate_path'];
if (empty($storedPath)) {
    http_response_code(404);
    exit('No certificate file attached to this record.');
}

// Resolve physical file location safely within UPLOAD_DIR
$candidate1 = UPLOAD_DIR . '/' . basename($storedPath);
$candidate2 = file_exists($storedPath) ? $storedPath : null;

$fullPath = false;
if (file_exists($candidate1)) {
    $fullPath = realpath($candidate1);
} elseif ($candidate2 && file_exists($candidate2)) {
    $fullPath = realpath($candidate2);
}

$uploadDirReal = realpath(UPLOAD_DIR);
if ($fullPath === false || $uploadDirReal === false || strpos($fullPath, $uploadDirReal) !== 0 || !file_exists($fullPath)) {
    http_response_code(404);
    exit('Certificate proof file could not be located on the server.');
}

// Detect MIME type securely using finfo
$mimeType = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $fullPath);
        if ($detected) $mimeType = $detected;
        finfo_close($finfo);
    }
}

$allowedMimes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'application/pdf'
];

if (!in_array($mimeType, $allowedMimes)) {
    $mimeType = 'application/octet-stream';
}

$filename = $activity['certificate_original_name'] ?: basename($fullPath);
$sanitizedFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);

// Stream file with security headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . $sanitizedFilename . '"');
header('Cache-Control: private, max-age=3600, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

readfile($fullPath);
exit;
