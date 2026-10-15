<?php
/**
 * CampusCred - Recruiter Action Dispatcher
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('recruiter');

$recruiter = current_user();
$recruiterId = (int)$recruiter['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

verify_csrf();

$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'recruiter/dashboard.php';
$targetUrl = base_url($redirect);

switch ($action) {
    case 'toggle_bookmark':
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            $check = $pdo->prepare("SELECT id FROM recruiter_bookmarks WHERE recruiter_id = ? AND student_id = ?");
            $check->execute([$recruiterId, $studentId]);
            $exists = $check->fetch();

            if ($exists) {
                $del = $pdo->prepare("DELETE FROM recruiter_bookmarks WHERE id = ?");
                $del->execute([$exists['id']]);
                set_flash('info', 'Candidate removed from your hiring shortlist.');
            } else {
                $ins = $pdo->prepare("INSERT INTO recruiter_bookmarks (recruiter_id, student_id, status, notes) VALUES (?, ?, 'Saved', '')");
                $ins->execute([$recruiterId, $studentId]);
                set_flash('success', 'Candidate added to your hiring shortlist.');
            }
        }
        break;

    case 'update_bookmark_status':
        $studentId = (int)($_POST['student_id'] ?? 0);
        $delete = !empty($_POST['delete_bookmark']);
        if ($studentId > 0) {
            if ($delete) {
                $del = $pdo->prepare("DELETE FROM recruiter_bookmarks WHERE recruiter_id = ? AND student_id = ?");
                $del->execute([$recruiterId, $studentId]);
                set_flash('info', 'Candidate removed from shortlist.');
            } else {
                $status = $_POST['status'] ?? 'Saved';
                $allowed = ['Saved', 'Screening', 'Interviewing', 'Offered', 'Archived'];
                if (!in_array($status, $allowed, true)) {
                    $status = 'Saved';
                }
                $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

                if ($notes !== null) {
                    $stmt = $pdo->prepare("
                        INSERT INTO recruiter_bookmarks (recruiter_id, student_id, status, notes)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)
                    ");
                    $stmt->execute([$recruiterId, $studentId, $status, $notes]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO recruiter_bookmarks (recruiter_id, student_id, status)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE status = VALUES(status)
                    ");
                    $stmt->execute([$recruiterId, $studentId, $status]);
                }
                set_flash('success', "Candidate recruitment stage updated to '{$status}'.");
            }
        }
        break;

    case 'send_inquiry':
        $studentId = (int)($_POST['student_id'] ?? 0);
        $jobTitle = trim($_POST['job_title'] ?? '');
        $jobType = $_POST['job_type'] ?? 'Full-time';
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary_range'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $contactEmail = trim($recruiter['email'] ?? '');

        if ($studentId <= 0 || $jobTitle === '' || $message === '') {
            set_flash('error', 'Please provide job title and message content.');
        } else {
            $inq = $pdo->prepare("
                INSERT INTO recruitment_inquiries (recruiter_id, student_id, job_title, job_type, location, salary_range, message, contact_email, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sent', NOW())
            ");
            $inq->execute([$recruiterId, $studentId, $jobTitle, $jobType, $location, $salary, $message, $contactEmail]);

            // Automatically bookmark candidate if not already bookmarked
            $pdo->exec("
                INSERT IGNORE INTO recruiter_bookmarks (recruiter_id, student_id, status, notes)
                VALUES ({$recruiterId}, {$studentId}, 'Interviewing', 'Interview invitation dispatched')
            ");

            set_flash('success', 'Recruitment inquiry successfully delivered to candidate!');
        }
        break;

    default:
        set_flash('error', 'Unrecognized action.');
        break;
}

header('Location: ' . $targetUrl);
exit;
