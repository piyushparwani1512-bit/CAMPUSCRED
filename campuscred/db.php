<?php
// CampusCred - Database Connection & Schema Auto-Initializer
require_once __DIR__ . '/config.php';

// If $pdo was already provided by install.php or an external runner, skip reconnecting
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        // 1. Direct connection to the configured database (Standard for InfinityFree, cPanel & production)
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // 2. Auto-initialize schema and demo data if needed
        auto_migrate_schema($pdo);

    } catch (PDOException $e) {
        // If direct connection fails on localhost/dev, attempt to create the database once
        $isLocalhost = in_array(DB_HOST, ['127.0.0.1', 'localhost', '::1', 'localhost:3306']);
        if ($isLocalhost) {
            try {
                $initPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS);
                $initPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                auto_migrate_schema($pdo);
            } catch (Throwable $ignore) {
                // Fall through to display error screen
            }
        }

        if (!isset($pdo)) {
            $inlineError = '';
            // Handle inline database configuration submission directly on this page
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inline_db_setup') {
                $subHost = trim($_POST['db_host'] ?? '');
                $subName = trim($_POST['db_name'] ?? '');
                $subUser = trim($_POST['db_user'] ?? '');
                $subPass = (string)($_POST['db_pass'] ?? '');

                if ($subHost !== '' && $subName !== '' && $subUser !== '') {
                    $hostParts = explode(':', $subHost, 2);
                    $cleanHost = $hostParts[0];
                    $portStr = isset($hostParts[1]) ? ';port=' . $hostParts[1] : '';
                    try {
                        $pdo = new PDO(
                            "mysql:host={$cleanHost}{$portStr};dbname={$subName};charset=utf8mb4",
                            $subUser,
                            $subPass,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES => false,
                                PDO::ATTR_TIMEOUT => 6
                            ]
                        );

                        // Save to db_config.php
                        $dbConfigCode = "<?php\n"
                            . "// Automatically configured by CampusCred\n"
                            . "define('DB_HOST', " . var_export($subHost, true) . ");\n"
                            . "define('DB_NAME', " . var_export($subName, true) . ");\n"
                            . "define('DB_USER', " . var_export($subUser, true) . ");\n"
                            . "define('DB_PASS', " . var_export($subPass, true) . ");\n";
                        @file_put_contents(__DIR__ . '/db_config.php', $dbConfigCode);

                        // Auto-migrate tables
                        auto_migrate_schema($pdo);

                        // Reload page
                        $redirectTarget = $_SERVER['REQUEST_URI'] ?? 'login.php';
                        header('Location: ' . $redirectTarget);
                        exit;

                    } catch (PDOException $submitErr) {
                        $inlineError = $submitErr->getMessage();
                    }
                } else {
                    $inlineError = 'Please enter MySQL Host, Database Name, and Username.';
                }
            }

            if (!defined('ALLOW_DB_FAILURE') || !ALLOW_DB_FAILURE) {
                ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Database Setup Required · CampusCred</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>
                    body { background: #0b1329; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 12px; }
                    .card { border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
                    .form-control { border-radius: 10px; padding: 10px 14px; font-size: 0.92rem; border: 1px solid #cbd5e1; }
                    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
                    .btn-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border: none; color: white; font-weight: 700; border-radius: 12px; padding: 12px 20px; }
                    .btn-gradient:hover { background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%); color: white; }
                </style>
            </head>
            <body>
                <div class="container" style="max-width: 680px;">
                    <div class="card shadow-lg bg-white overflow-hidden">
                        <div class="bg-primary text-white p-4 text-center" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
                            <div class="display-5 mb-2"><i class="bi bi-database-gear"></i></div>
                            <h3 class="fw-bold mb-1">Database Connection Needed</h3>
                            <p class="mb-0 text-white-50 small">Enter your InfinityFree database credentials below to connect immediately</p>
                        </div>

                        <div class="card-body p-4">
                            <?php if (!empty($inlineError)): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-3 rounded-3 small">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                                    <div><strong>Connection Failed:</strong> <?= htmlspecialchars($inlineError) ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Current Config Status -->
                            <div class="alert alert-light border small text-muted mb-3 p-3 rounded-3">
                                <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Current Status:</div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Host: <code><?= htmlspecialchars(DB_HOST) ?></code></span>
                                    <span>Database: <code><?= htmlspecialchars(DB_NAME) ?></code></span>
                                    <span>User: <code><?= htmlspecialchars(DB_USER) ?></code></span>
                                </div>
                                <div class="text-danger small mt-2 pt-2 border-top">
                                    <strong>Last Error:</strong> <code><?= htmlspecialchars($e->getMessage()) ?></code>
                                </div>
                            </div>

                            <!-- Interactive Inline Setup Form -->
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="inline_db_setup">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-secondary">MySQL Hostname <span class="text-danger">*</span></label>
                                        <input type="text" name="db_host" class="form-control" placeholder="e.g. sql200.infinityfree.com" value="<?= htmlspecialchars(DB_HOST !== '127.0.0.1' ? DB_HOST : '') ?>" required>
                                        <div class="form-text small text-muted">Found in InfinityFree Client Area</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-secondary">Database Name <span class="text-danger">*</span></label>
                                        <input type="text" name="db_name" class="form-control" placeholder="e.g. if0_12345678_campuscred" value="<?= htmlspecialchars(DB_NAME !== 'campuscred' ? DB_NAME : '') ?>" required>
                                        <div class="form-text small text-muted">Created in vPanel &gt; MySQL Databases</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-secondary">MySQL Username <span class="text-danger">*</span></label>
                                        <input type="text" name="db_user" class="form-control" placeholder="e.g. if0_12345678" value="<?= htmlspecialchars(DB_USER !== 'root' ? DB_USER : '') ?>" required>
                                        <div class="form-text small text-muted">Your vPanel username</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-secondary">MySQL Password <span class="text-danger">*</span></label>
                                        <input type="password" name="db_pass" class="form-control" placeholder="Your hosting account password" required>
                                        <div class="form-text small text-muted">Account Details &gt; Password (click Show)</div>
                                    </div>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-gradient btn-lg">
                                        <i class="bi bi-lightning-charge-fill me-1"></i> Connect &amp; Auto-Configure Database
                                    </button>
                                </div>
                            </form>

                            <div class="text-center pt-3 border-top small text-muted">
                                Prefer editing manually? In InfinityFree File Manager, right-click <code>config.php</code> &gt; <strong>Edit</strong> and paste your 4 database parameters.
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}
}

function auto_migrate_schema(PDO $pdo): void {
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    $needsInit = empty($tables);

    if (!$needsInit) {
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'company_name'")->fetch();
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'recruiter_bookmarks'")->fetch();
            $verifiedCheck = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Verified'")->fetchColumn();
            if (!$colCheck || !$tableCheck || $verifiedCheck === 0) {
                $needsInit = true;
            }
        } catch (Throwable $t) {
            $needsInit = true;
        }
    }

    if ($needsInit) {
        initialize_database_tables($pdo);
    }
}

function initialize_database_tables(PDO $pdo): void {
    // 1. Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id_code VARCHAR(50) UNIQUE NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('student', 'admin', 'recruiter') NOT NULL DEFAULT 'student',
            department VARCHAR(100) NOT NULL,
            branch VARCHAR(100) NULL,
            academic_year VARCHAR(30) NULL,
            admission_year INT NULL,
            phone VARCHAR(20) NULL,
            company_name VARCHAR(150) NULL,
            company_website VARCHAR(200) NULL,
            designation VARCHAR(100) NULL,
            industry VARCHAR(100) NULL,
            headline VARCHAR(200) NULL,
            skills VARCHAR(255) NULL,
            bio TEXT NULL,
            linkedin_url VARCHAR(200) NULL,
            github_url VARCHAR(200) NULL,
            cgpa DECIMAL(3,2) NULL,
            is_open_to_work TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_role (role),
            INDEX idx_user_student_id (student_id_code),
            INDEX idx_user_dept (department)
        ) ENGINE=InnoDB;
    ");

    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'recruiter') NOT NULL DEFAULT 'student'");
    } catch (Throwable $ignore) {}

    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('student_id_code', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN student_id_code VARCHAR(50) UNIQUE NULL AFTER id");
    }
    if (!in_array('branch', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN branch VARCHAR(100) NULL AFTER department");
    }
    if (!in_array('academic_year', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN academic_year VARCHAR(30) NULL AFTER branch");
    }
    if (!in_array('admission_year', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN admission_year INT NULL AFTER academic_year");
    }
    if (!in_array('phone', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER admission_year");
    }
    if (!in_array('company_name', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(150) NULL AFTER phone");
    }
    if (!in_array('company_website', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN company_website VARCHAR(200) NULL AFTER company_name");
    }
    if (!in_array('designation', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN designation VARCHAR(100) NULL AFTER company_website");
    }
    if (!in_array('industry', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN industry VARCHAR(100) NULL AFTER designation");
    }
    if (!in_array('headline', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN headline VARCHAR(200) NULL AFTER industry");
    }
    if (!in_array('skills', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN skills VARCHAR(255) NULL AFTER headline");
    }
    if (!in_array('bio', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER skills");
    }
    if (!in_array('linkedin_url', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN linkedin_url VARCHAR(200) NULL AFTER bio");
    }
    if (!in_array('github_url', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN github_url VARCHAR(200) NULL AFTER linkedin_url");
    }
    if (!in_array('cgpa', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN cgpa DECIMAL(3,2) NULL AFTER github_url");
    }
    if (!in_array('is_open_to_work', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_open_to_work TINYINT(1) NOT NULL DEFAULT 1 AFTER cgpa");
    }

    // 2. Activities table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            submission_code VARCHAR(50) UNIQUE NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            event_name VARCHAR(200) NOT NULL,
            category ENUM('Academic', 'Technical', 'Cultural', 'Sports', 'Extracurricular', 'Other') NOT NULL DEFAULT 'Other',
            event_date DATE NOT NULL,
            organizer VARCHAR(200) NOT NULL,
            department VARCHAR(100) NOT NULL,
            participation_type ENUM('Winner', 'Runner-up', '3rd Place', 'Finalist', 'Participant', 'Volunteer', 'Organizer', 'Presenter') NOT NULL DEFAULT 'Participant',
            achievement_description TEXT NULL,
            certificate_path VARCHAR(255) NOT NULL,
            certificate_original_name VARCHAR(255) NOT NULL,
            certificate_hash VARCHAR(64) NULL,
            status ENUM('Pending Verification', 'Verified', 'Rejected', 'Resubmitted / Pending Review') NOT NULL DEFAULT 'Pending Verification',
            verification_uuid VARCHAR(64) UNIQUE NOT NULL,
            reviewer_id INT UNSIGNED NULL,
            rejection_reason TEXT NULL,
            review_notes TEXT NULL,
            reviewed_at DATETIME NULL,
            resubmission_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_activity_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_activity_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_activity_status (status),
            INDEX idx_activity_student (student_id),
            INDEX idx_activity_category (category),
            INDEX idx_activity_dept (department),
            INDEX idx_activity_date (event_date),
            INDEX idx_activity_uuid (verification_uuid)
        ) ENGINE=InnoDB;
    ");

    $actColumns = $pdo->query("SHOW COLUMNS FROM activities")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('submission_code', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN submission_code VARCHAR(50) UNIQUE NULL AFTER id");
    }
    if (!in_array('department', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN department VARCHAR(100) NOT NULL DEFAULT 'Computer Science & Engineering' AFTER organizer");
    }
    if (!in_array('participation_type', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN participation_type ENUM('Winner', 'Runner-up', '3rd Place', 'Finalist', 'Participant', 'Volunteer', 'Organizer', 'Presenter') NOT NULL DEFAULT 'Participant' AFTER department");
    }
    if (!in_array('achievement_description', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN achievement_description TEXT NULL AFTER participation_type");
    }
    if (!in_array('certificate_hash', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN certificate_hash VARCHAR(64) NULL AFTER certificate_original_name");
    }
    if (!in_array('verification_uuid', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN verification_uuid VARCHAR(64) UNIQUE NULL AFTER status");
    }
    if (!in_array('rejection_reason', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN rejection_reason TEXT NULL AFTER reviewer_id");
    }
    if (!in_array('review_notes', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN review_notes TEXT NULL AFTER rejection_reason");
    }
    if (!in_array('resubmission_count', $actColumns)) {
        $pdo->exec("ALTER TABLE activities ADD COLUMN resubmission_count INT NOT NULL DEFAULT 0 AFTER reviewed_at");
    }

    try {
        $pdo->exec("ALTER TABLE activities MODIFY COLUMN status ENUM('Pending Verification', 'Verified', 'Rejected', 'Resubmitted / Pending Review') NOT NULL DEFAULT 'Pending Verification'");
        $pdo->exec("UPDATE activities SET status = 'Pending Verification' WHERE status = 'Pending'");
        $pdo->exec("UPDATE activities SET status = 'Verified' WHERE status = 'Approved'");
        $pdo->exec("UPDATE activities SET submission_code = CONCAT('SUB-2026-', UPPER(HEX(RANDOM_BYTES(3)))) WHERE submission_code IS NULL OR submission_code = ''");
        $pdo->exec("UPDATE activities SET verification_uuid = CONCAT('ver-', HEX(RANDOM_BYTES(6)), '-', HEX(RANDOM_BYTES(4))) WHERE verification_uuid IS NULL OR verification_uuid = ''");
    } catch (Throwable $ignore) {}

    // 3. Verification logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS verification_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activity_id INT UNSIGNED NOT NULL,
            reviewer_id INT UNSIGNED NULL,
            action ENUM('Submitted', 'Verified', 'Rejected', 'Resubmitted', 'Updated') NOT NULL,
            previous_status VARCHAR(50) NULL,
            new_status VARCHAR(50) NOT NULL,
            reason TEXT NULL,
            logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_log_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
            CONSTRAINT fk_log_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_log_activity (activity_id),
            INDEX idx_log_action (action)
        ) ENGINE=InnoDB;
    ");

    // 4. Recruiter Bookmarks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recruiter_bookmarks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recruiter_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            notes TEXT NULL,
            status ENUM('Saved', 'Screening', 'Interviewing', 'Offered', 'Archived') NOT NULL DEFAULT 'Saved',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_bm_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_bm_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uk_recruiter_student (recruiter_id, student_id),
            INDEX idx_bm_recruiter (recruiter_id),
            INDEX idx_bm_student (student_id)
        ) ENGINE=InnoDB;
    ");

    // 5. Recruitment Inquiries table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recruitment_inquiries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recruiter_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            job_title VARCHAR(150) NOT NULL,
            job_type ENUM('Full-time', 'Internship', 'Contract', 'Part-time') NOT NULL DEFAULT 'Full-time',
            location VARCHAR(100) NULL,
            salary_range VARCHAR(100) NULL,
            message TEXT NOT NULL,
            contact_email VARCHAR(190) NOT NULL,
            status ENUM('Sent', 'Viewed', 'Accepted', 'Declined') NOT NULL DEFAULT 'Sent',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_inq_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_inq_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_inq_recruiter (recruiter_id),
            INDEX idx_inq_student (student_id)
        ) ENGINE=InnoDB;
    ");

    // 6. Seed demo users if empty or missing recruiter
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $adminPass = password_hash('Admin@123', PASSWORD_DEFAULT);
    $studentPass = password_hash('Student@123', PASSWORD_DEFAULT);
    $recruiterPass = password_hash('Recruiter@123', PASSWORD_DEFAULT);

    if ($userCount === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO users (id, student_id_code, name, email, password_hash, role, department, branch, academic_year, admission_year, phone, headline, skills, cgpa, is_open_to_work)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([1, 'SKIT/ADM/001', 'Dr. Rajesh Sharma', 'admin@skit.ac.in', $adminPass, 'admin', 'Computer Science & Engineering', 'CSE', 'Faculty Admin', 2018, '9829012345', 'Head of Department & Verification Officer', null, null, 0]);
        $stmt->execute([2, 'SKIT/2023/CSE/014', 'Aravind Swaminathan', 'aravind@skit.ac.in', $studentPass, 'student', 'Computer Science & Engineering', 'CSE', '3rd Year', 2023, '9829112233', 'National Hackathon Winner | Python, Drone Robotics & Full-Stack Developer', 'Python, React, Django, Drone Tech, IoT, Docker, Git', 8.85, 1]);
        $stmt->execute([3, 'SKIT/2022/IT/088', 'Priya Sharma', 'priya.sharma@skit.ac.in', $studentPass, 'student', 'Information Technology', 'IT', '4th Year', 2022, '9829223344', 'ACM-ICPC Regional Finalist | Algorithms & Cloud Architecture Specialist', 'Java, C++, Algorithms, AWS Cloud, Spring Boot, MySQL', 9.10, 1]);
        $stmt->execute([4, 'SKIT/2024/ECE/105', 'Rohit Verma', 'rohit.verma@skit.ac.in', $studentPass, 'student', 'Electronics & Communication', 'ECE', '2nd Year', 2024, '9829334455', 'Autonomous Robotics & Embedded Systems Engineer | ESP32 Enthusiast', 'C, C++, Embedded Systems, ESP32, ROS, Robotics, Hardware Design', 8.40, 1]);
    } else {
        // Update existing student profiles with skills & headline if not set
        $pdo->exec("
            UPDATE users SET 
                headline = 'National Hackathon Winner | Python, Drone Robotics & Full-Stack Developer',
                skills = 'Python, React, Django, Drone Tech, IoT, Docker, Git',
                cgpa = 8.85,
                is_open_to_work = 1
            WHERE id = 2 AND (skills IS NULL OR skills = '')
        ");
        $pdo->exec("
            UPDATE users SET 
                headline = 'ACM-ICPC Regional Finalist | Algorithms & Cloud Architecture Specialist',
                skills = 'Java, C++, Algorithms, AWS Cloud, Spring Boot, MySQL',
                cgpa = 9.10,
                is_open_to_work = 1
            WHERE id = 3 AND (skills IS NULL OR skills = '')
        ");
        $pdo->exec("
            UPDATE users SET 
                headline = 'Autonomous Robotics & Embedded Systems Engineer | ESP32 Enthusiast',
                skills = 'C, C++, Embedded Systems, ESP32, ROS, Robotics, Hardware Design',
                cgpa = 8.40,
                is_open_to_work = 1
            WHERE id = 4 AND (skills IS NULL OR skills = '')
        ");
    }

    // Seed recruiter user if not present
    $recruiterExists = $pdo->query("SELECT id FROM users WHERE email = 'recruiter@techcorp.com'")->fetch();
    if (!$recruiterExists) {
        $recStmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, department, phone, company_name, company_website, designation, industry)
            VALUES (?, ?, ?, 'recruiter', 'Human Resources', ?, ?, ?, ?, ?)
        ");
        $recStmt->execute([
            'Pooja Malhotra',
            'recruiter@techcorp.com',
            $recruiterPass,
            '9876543210',
            'TechCorp Innovations & Labs',
            'https://techcorp.example.com',
            'Senior Talent Acquisition & University Hiring Lead',
            'Enterprise Software, Cloud & AI Solutions'
        ]);
        $recruiterId = (int)$pdo->lastInsertId();

        // Seed demo bookmark
        try {
            $pdo->exec("INSERT IGNORE INTO recruiter_bookmarks (recruiter_id, student_id, notes, status) VALUES ({$recruiterId}, 2, 'Impressive 1st place in Smart India Hackathon. Great fit for our Autonomous Systems & Cloud engineering track.', 'Interviewing')");
            $pdo->exec("INSERT IGNORE INTO recruiter_bookmarks (recruiter_id, student_id, notes, status) VALUES ({$recruiterId}, 3, 'Outstanding ACM-ICPC standing and high CGPA. Excellent fit for Backend & Algorithms role.', 'Screening')");

            // Seed demo inquiry
            $pdo->exec("INSERT INTO recruitment_inquiries (recruiter_id, student_id, job_title, job_type, location, salary_range, message, contact_email, status)
                VALUES (
                    {$recruiterId},
                    2,
                    'Graduate Software Engineer - Cloud & Autonomous Systems',
                    'Full-time',
                    'Bangalore / Hybrid',
                    '₹12 - ₹16 LPA',
                    'Hello Aravind,\n\nWe were highly impressed by your verified achievements at Smart India Hackathon and your background in Drone Tech and Python. We would love to invite you for an introductory technical discussion with our engineering director.\n\nBest regards,\nPooja Malhotra\nTalent Acquisition, TechCorp Labs',
                    'recruiter@techcorp.com',
                    'Sent'
                )
            ");
        } catch (Throwable $e) {}
    }

    // Ensure valid roles and roll codes for all users
    try {
        $pdo->exec("UPDATE users SET role = 'student' WHERE role = '' OR role IS NULL");
        $pdo->exec("UPDATE users SET student_id_code = CONCAT('SKIT/2024/STU/', LPAD(id, 3, '0')) WHERE (student_id_code IS NULL OR student_id_code = '') AND role = 'student'");
        $pdo->exec("UPDATE users SET student_id_code = CONCAT('SKIT/ADM/', LPAD(id, 3, '0')) WHERE (student_id_code IS NULL OR student_id_code = '') AND role = 'admin'");
    } catch (Throwable $e) {}

    // Ensure sample upload certificates exist
    $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : (__DIR__ . '/uploads');
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $sampleJpg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    foreach (['sample_cert_pravah.jpg', 'sample_cert_sports.jpg', 'sample_cert_mnit.jpg'] as $imgName) {
        $imgPath = $uploadDir . '/' . $imgName;
        if (!file_exists($imgPath)) {
            @file_put_contents($imgPath, $sampleJpg);
        }
    }

    // 5. Seed sample activities if empty or if no verified activities exist
    $verifiedCount = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Verified'")->fetchColumn();
    if ($verifiedCount === 0) {
        $actStmt = $pdo->prepare("
            INSERT INTO activities (
                id, submission_code, student_id, event_name, category, event_date, organizer, department, participation_type,
                achievement_description, certificate_path, certificate_original_name, status, verification_uuid, reviewer_id,
                rejection_reason, review_notes, reviewed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                verification_uuid = VALUES(verification_uuid),
                certificate_path = VALUES(certificate_path),
                certificate_original_name = VALUES(certificate_original_name)
        ");

        $actStmt->execute([
            1, 'SUB-2026-CSE001', 2, 'Smart India Hackathon 2025 (Hardware & Software)', 'Technical', '2025-12-14',
            'Ministry of Education & AICTE', 'Computer Science & Engineering', 'Winner',
            'Secured 1st Place across 35 collegiate teams for developing an automated agricultural drone surveillance system.',
            'sample_cert_sih.pdf', 'SIH_2025_First_Prize_Certificate.pdf', 'Verified',
            'ver-c9b8f2a1-4e7d-4293-8b1e-72fa9108c1a1', 1,
            null, 'Verified genuine against AICTE national registry credentials.', '2026-01-10 14:30:00'
        ]);

        $actStmt->execute([
            2, 'SUB-2026-CSE002', 2, 'Pravah 2026 Inter-College Cultural Festival', 'Cultural', '2026-02-18',
            'SKIT Jaipur Cultural Council', 'Computer Science & Engineering', 'Winner',
            'First prize in Solo Classical Instrumental (Flute) competition among 18 universities.',
            'sample_cert_pravah.jpg', 'Pravah_Instrumental_1st_Place.jpg', 'Verified',
            'ver-a1e4d7c8-9b2f-4130-98ef-83bd0219d2b2', 1,
            null, 'Verified by SKIT Cultural Committee record book.', '2026-02-22 11:15:00'
        ]);

        $actStmt->execute([
            3, 'SUB-2026-CSE003', 2, 'National Cyber Security Research Workshop', 'Academic', '2026-03-05',
            'IIT Roorkee & IEEE Computer Society', 'Computer Science & Engineering', 'Presenter',
            'Delivered paper presentation on Zero-Trust Architecture for Distributed Campus Networks.',
            'sample_cert_paper.pdf', 'IEEE_CyberSecurity_Paper_Cert.pdf', 'Pending Verification',
            'ver-3f82a9c1-74d8-4be0-86d1-419bcf8231c3', null,
            null, null, null
        ]);

        $actStmt->execute([
            4, 'SUB-2026-IT001', 3, 'Rajasthan State Inter-University Basketball Tournament', 'Sports', '2026-01-28',
            'Rajasthan Technical University (RTU)', 'Information Technology', 'Runner-up',
            'Represented SKIT Jaipur as Team Vice-Captain; reached tournament finals and secured Silver medal.',
            'sample_cert_sports.jpg', 'RTU_Basketball_Silver_Medal.jpg', 'Verified',
            'ver-6d19e4a2-11c5-4d7a-b94f-5612ac9831d4', 1,
            null, 'Confirmed with Department of Physical Education.', '2026-02-02 16:45:00'
        ]);

        $actStmt->execute([
            5, 'SUB-2026-IT002', 3, 'ACM International Collegiate Programming Contest (ICPC)', 'Technical', '2026-02-10',
            'ACM India Chapter', 'Information Technology', 'Finalist',
            'Qualified for regional finals; ranked in top 5% among over 400 collegiate teams.',
            'sample_cert_icpc.pdf', 'ICPC_Regional_Finalist.pdf', 'Verified',
            'ver-7f91a2e3-55b6-4c8d-ae12-9843bf1042e5', 1,
            null, 'Verified with official ACM ranking bulletin.', '2026-02-15 10:20:00'
        ]);

        $actStmt->execute([
            6, 'SUB-2026-IT003', 3, 'Green Campus Tree Plantation & Cleanliness Drive', 'Extracurricular', '2026-03-12',
            'NSS Unit - SKIT Jaipur', 'Information Technology', 'Volunteer',
            'Coordinated 60 student volunteers for eco-restoration and campus plastic elimination campaign.',
            'sample_cert_nss.pdf', 'NSS_Cleanliness_Drive_Volunteer.pdf', 'Pending Verification',
            'ver-9c84e1b2-33d7-4f9a-8a19-6127dc8451f6', null,
            null, null, null
        ]);

        $actStmt->execute([
            7, 'SUB-2026-ECE001', 4, 'IEEE Robotics & Embedded IoT Expo', 'Technical', '2026-02-25',
            'MNIT Jaipur', 'Electronics & Communication', 'Participant',
            'Demonstrated autonomous line-following obstacle detection robot with ESP32 integration.',
            'sample_cert_mnit.jpg', 'MNIT_Robotics_Participation.jpg', 'Rejected',
            'ver-4a71b8e9-22c6-4d1e-9b88-1234ef5678a7', 1,
            'Uploaded certificate is blurry, illegible, and the participant name is cropped. Please upload a clear, high-resolution scan showing full credentials.',
            'Rejected due to illegible scan.', '2026-03-01 09:30:00'
        ]);

        $actStmt->execute([
            8, 'SUB-2026-ECE002', 4, 'Annual Blood Donation & Red Cross Camp', 'Extracurricular', '2026-03-08',
            'Indian Red Cross Society & SKIT Youth Club', 'Electronics & Communication', 'Volunteer',
            'Assisted medical officers with donor registry and blood unit logistics; awarded citation.',
            'sample_cert_redcross.pdf', 'RedCross_Donor_Camp_Citation.pdf', 'Resubmitted / Pending Review',
            'ver-5b82c9d1-33e7-4f2a-8c99-2345fa6789b8', null,
            null, null, null
        ]);

        $logStmt = $pdo->prepare("
            INSERT INTO verification_logs (activity_id, reviewer_id, action, previous_status, new_status, reason, logged_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([1, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-01-05 10:00:00']);
        $logStmt->execute([1, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified genuine against AICTE national registry credentials.', '2026-01-10 14:30:00']);
        $logStmt->execute([2, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-02-19 09:00:00']);
        $logStmt->execute([2, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified by SKIT Cultural Committee record book.', '2026-02-22 11:15:00']);
        $logStmt->execute([4, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-01-29 11:00:00']);
        $logStmt->execute([4, 1, 'Verified', 'Pending Verification', 'Verified', 'Confirmed with Department of Physical Education.', '2026-02-02 16:45:00']);
        $logStmt->execute([5, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-02-11 16:30:00']);
        $logStmt->execute([5, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified with official ACM ranking bulletin.', '2026-02-15 10:20:00']);
        $logStmt->execute([7, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-02-26 15:20:00']);
        $logStmt->execute([7, 1, 'Rejected', 'Pending Verification', 'Rejected', 'Uploaded certificate is blurry, illegible, and the participant name is cropped. Please upload a clear, high-resolution scan showing full credentials.', '2026-03-01 09:30:00']);
        $logStmt->execute([8, null, 'Submitted', null, 'Pending Verification', 'Initial submission by student', '2026-03-09 11:00:00']);
        $logStmt->execute([8, 1, 'Rejected', 'Pending Verification', 'Rejected', 'Certificate missing official stamp.', '2026-03-10 12:00:00']);
        $logStmt->execute([8, null, 'Resubmitted', 'Rejected', 'Resubmitted / Pending Review', 'Re-uploaded stamped certificate from Red Cross convenor.', '2026-03-11 15:00:00']);
    }
}