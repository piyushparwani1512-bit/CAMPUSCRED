<?php
// CampusCred - Core Utility Functions

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function current_user_id(): ?int {
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function is_admin(): bool {
    return is_logged_in() && (current_user()['role'] ?? '') === 'admin';
}

function is_recruiter(): bool {
    return is_logged_in() && (current_user()['role'] ?? '') === 'recruiter';
}

function is_student(): bool {
    return is_logged_in() && (current_user()['role'] ?? '') === 'student';
}

function redirect_by_role(): void {
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
    $role = current_user()['role'] ?? 'student';
    if ($role === 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($role === 'recruiter') {
        header('Location: ' . base_url('recruiter/dashboard.php'));
    } else {
        header('Location: ' . base_url('student/dashboard.php'));
    }
    exit;
}

function getDB(): PDO {
    global $pdo;
    return $pdo;
}

function sanitize($value): string {
    return e($value);
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Session expired or not logged in. Please sign in to continue.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_role(string|array $roles): void {
    require_login();
    $currentRole = current_user()['role'] ?? '';
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($currentRole, $allowed, true)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function base_url(string $path = ''): string {
    $base = defined('BASE_URL') ? BASE_URL : '/campuscred';
    $trimmed = ltrim($path, '/');
    return rtrim($base, '/') . ($trimmed !== '' ? '/' . $trimmed : '');
}

function full_url(string $path = ''): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $protocol . $host . base_url($path);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals(csrf_token(), $posted)) {
        http_response_code(400);
        exit('Invalid security token (CSRF). Please reload the page and try again.');
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(?string $type = null): ?string {
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    if ($type !== null && $flash['type'] !== $type) return null;
    unset($_SESSION['flash']);
    return $flash['message'];
}

function get_flash_data(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function status_badge(string $status): string {
    return match ($status) {
        'Verified' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>',
        'Pending Verification' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Pending Verification</span>',
        'Rejected' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>',
        'Resubmitted / Pending Review' => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>Resubmitted / Pending Review</span>',
        default => '<span class="badge bg-secondary-subtle text-secondary px-2 py-1">' . e($status) . '</span>',
    };
}

function category_badge(string $category): string {
    $class = match ($category) {
        'Academic' => 'badge-cat-academic',
        'Technical' => 'badge-cat-technical',
        'Cultural' => 'badge-cat-cultural',
        'Sports' => 'badge-cat-sports',
        'Extracurricular' => 'badge-cat-extra',
        default => 'badge-cat-other',
    };
    return '<span class="badge-cat ' . $class . '">' . e($category) . '</span>';
}

function generate_submission_code(): string {
    $year = date('Y');
    $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    return "SUB-{$year}-{$rand}";
}

function generate_verification_uuid(): string {
    return 'ver-' . bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . substr(bin2hex(random_bytes(2)), 1) . '-a' . substr(bin2hex(random_bytes(2)), 1) . '-' . bin2hex(random_bytes(6));
}

function log_verification_action(PDO $pdo, int $activityId, ?int $reviewerId, string $action, ?string $prevStatus, string $newStatus, ?string $reason): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO verification_logs (activity_id, reviewer_id, action, previous_status, new_status, reason, logged_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$activityId, $reviewerId, $action, $prevStatus, $newStatus, $reason]);
    } catch (Throwable $e) {
        error_log('Failed to write audit log: ' . $e->getMessage());
    }
}

function is_duplicate_submission(PDO $pdo, int $studentId, string $eventName, string $eventDate, ?int $excludeActivityId = null): bool {
    $sql = "SELECT id FROM activities WHERE student_id = ? AND LOWER(TRIM(event_name)) = LOWER(TRIM(?)) AND event_date = ?";
    $params = [$studentId, $eventName, $eventDate];
    if ($excludeActivityId) {
        $sql .= " AND id != ?";
        $params[] = $excludeActivityId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetch();
}

function format_display_date(?string $date): string {
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : $date;
}

function format_display_datetime(?string $dt): string {
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('d M Y, h:i A', $ts) : $dt;
}

function get_student_stats(PDO $pdo, int $studentId): array {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_submissions,
            SUM(status = 'Verified') as verified_count,
            SUM(status = 'Pending Verification' OR status = 'Resubmitted / Pending Review') as pending_count,
            SUM(status = 'Rejected') as rejected_count,
            SUM(status = 'Verified' AND (participation_type = 'Winner' OR participation_type = '1st Place')) as winner_count,
            SUM(status = 'Verified' AND category = 'Technical') as tech_count,
            SUM(status = 'Verified' AND category = 'Academic') as academic_count,
            SUM(status = 'Verified' AND category = 'Cultural') as cultural_count,
            SUM(status = 'Verified' AND category = 'Sports') as sports_count
        FROM activities
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $stats = $stmt->fetch() ?: [];
    return [
        'total' => (int)($stats['total_submissions'] ?? 0),
        'verified' => (int)($stats['verified_count'] ?? 0),
        'pending' => (int)($stats['pending_count'] ?? 0),
        'rejected' => (int)($stats['rejected_count'] ?? 0),
        'winners' => (int)($stats['winner_count'] ?? 0),
        'technical' => (int)($stats['tech_count'] ?? 0),
        'academic' => (int)($stats['academic_count'] ?? 0),
        'cultural' => (int)($stats['cultural_count'] ?? 0),
        'sports' => (int)($stats['sports_count'] ?? 0),
    ];
}

function is_student_bookmarked(PDO $pdo, int $recruiterId, int $studentId): bool {
    $stmt = $pdo->prepare("SELECT id FROM recruiter_bookmarks WHERE recruiter_id = ? AND student_id = ? LIMIT 1");
    $stmt->execute([$recruiterId, $studentId]);
    return (bool)$stmt->fetch();
}

function get_student_inquiries_count(PDO $pdo, int $studentId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_inquiries WHERE student_id = ?");
    $stmt->execute([$studentId]);
    return (int)$stmt->fetchColumn();
}

function get_recruiter_bookmarks_count(PDO $pdo, int $recruiterId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruiter_bookmarks WHERE recruiter_id = ?");
    $stmt->execute([$recruiterId]);
    return (int)$stmt->fetchColumn();
}