<?php
// CampusCred - Student Participation & Achievement Management System
// Flexible Configuration for Any University, College, or Institution

// ==============================================================================
// 1. DATABASE CONNECTION PARAMETERS
// ==============================================================================
// 💡 DEPLOYING ON INFINITYFREE (or live shared hosting)?
// In InfinityFree File Manager, right-click config.php -> Edit and paste your 4 values:
// (Never use 'localhost' or '127.0.0.1' on InfinityFree!)
//
// Example InfinityFree values:
//   define('DB_HOST', 'sql200.infinityfree.com');
//   define('DB_NAME', 'if0_38123456_campuscred');
//   define('DB_USER', 'if0_38123456');
//   define('DB_PASS', 'YourHostingPassword');

if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'campuscred');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// 2. Institutional Branding & General Use Customization
// You can customize these values for any university, college, school, or organization
define('COLLEGE_NAME', getenv('COLLEGE_NAME') ?: 'Swami Keshvanand Institute of Technology, Management & Gramothan');
define('COLLEGE_SHORT_NAME', getenv('COLLEGE_SHORT') ?: 'SKIT Jaipur');
define('PROJECT_CODE', getenv('PROJECT_CODE') ?: 'SKIT021');
define('APP_NAME', 'CampusCred · Student Participation & Achievement Management System');
define('APP_TAGLINE', 'Centralized Digital Repository, Recruiter Talent Portal & Tamper-Evident Verification');

// Organization Type Label (e.g. University, College, Institute, Academy)
define('ORG_TYPE_LABEL', 'Institution');

// 3. Security & Uploads
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIME_TYPES', [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
]);

// 4. Feature Flags
define('ENABLE_RECRUITER_PORTAL', true);
define('ENABLE_PUBLIC_VERIFICATION', true);
define('ENABLE_PUBLIC_TALENT_DIRECTORY', true);

// 5. Base URL helper - Dynamically auto-detects subfolder or root domain
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false) {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($scriptName, '/campuscred') === 0) {
            define('BASE_URL', '/campuscred');
        } else {
            define('BASE_URL', '');
        }
    }
}