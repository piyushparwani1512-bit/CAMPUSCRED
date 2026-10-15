<?php
/**
 * CampusCred - Public Candidate Verified Achievement Portfolio
 * Designed for Employers, Companies, Recruiters, and Professional Showcasing
 */
require_once __DIR__ . '/includes/bootstrap.php';

$studentId = (int)($_GET['id'] ?? 0);
$studentCode = trim($_GET['code'] ?? '');

if ($studentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student' LIMIT 1");
    $stmt->execute([$studentId]);
} elseif ($studentCode !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UPPER(student_id_code) = UPPER(?) AND role = 'student' LIMIT 1");
    $stmt->execute([$studentCode]);
} else {
    // Default to first student demo if no ID specified
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' ORDER BY id ASC LIMIT 1");
    $stmt->execute();
}

$candidate = $stmt->fetch();

if (!$candidate) {
    http_response_code(404);
    $page_title = 'Candidate Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><div class="alert alert-warning">Candidate profile not found or is inactive.</div><a href="' . e(base_url('talents.php')) . '" class="btn btn-primary">Browse All Candidates</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$candId = (int)$candidate['id'];
$stats = get_student_stats($pdo, $candId);

// Fetch verified activities
$actStmt = $pdo->prepare("
    SELECT a.*, rev.name as reviewer_name
    FROM activities a
    LEFT JOIN users rev ON a.reviewer_id = rev.id
    WHERE a.student_id = ? AND a.status = 'Verified'
    ORDER BY a.event_date DESC
");
$actStmt->execute([$candId]);
$verifiedActivities = $actStmt->fetchAll();

// Handle recruitment inquiry submission from public page
$inquirySent = false;
$inquiryError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_inquiry') {
    verify_csrf();
    $jobTitle = trim($_POST['job_title'] ?? '');
    $jobType = $_POST['job_type'] ?? 'Full-time';
    $location = trim($_POST['location'] ?? '');
    $salary = trim($_POST['salary_range'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($jobTitle === '' || $contactEmail === '' || $message === '') {
        $inquiryError = 'Please fill out all required fields (Job Title, Contact Email, Message).';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $inquiryError = 'Please provide a valid work email address.';
    } else {
        $recruiterId = is_recruiter() ? current_user_id() : 1; // Fallback or linked
        $fullMessage = "From: " . ($companyName ? $companyName . " (" . $contactEmail . ")" : $contactEmail) . "\n\n" . $message;
        $inqStmt = $pdo->prepare("
            INSERT INTO recruitment_inquiries (recruiter_id, student_id, job_title, job_type, location, salary_range, message, contact_email, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sent', NOW())
        ");
        $inqStmt->execute([$recruiterId, $candId, $jobTitle, $jobType, $location, $salary, $fullMessage, $contactEmail]);
        $inquirySent = true;
    }
}

$page_title = 'Verified Portfolio · ' . e($candidate['name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

  <!-- Top Navigation & Print Actions -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 no-print">
    <div class="d-flex align-items-center gap-2">
      <a href="<?= e(base_url('talents.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Talent Directory
      </a>
      <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
        <i class="bi bi-shield-check me-1"></i>Verified Institutional Transcript
      </span>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" onclick="window.print()">
        <i class="bi bi-printer-fill me-1"></i>Print Dossier
      </button>
      <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="generatePortfolioPDF()">
        <i class="bi bi-file-earmark-pdf-fill me-1"></i>Export PDF
      </button>
      <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="copyToClipboard(window.location.href, this)">
        <i class="bi bi-share me-1"></i>Share Profile
      </button>
    </div>
  </div>

  <?php if ($inquirySent): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
      <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
      <strong>Recruitment Message Dispatched!</strong> Your inquiry has been delivered directly to <?= e($candidate['name']) ?>'s student portal.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($inquiryError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
      <?= e($inquiryError) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Official Transcript Card -->
  <div class="card shadow-lg border-0 rounded-4 overflow-hidden bg-white mb-4" id="portfolioDocument">
    
    <!-- Institutional Banner Header -->
    <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); border-bottom: 3px solid #f59e0b;">
      <div class="row align-items-center">
        <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
          <div class="brand-emblem mx-auto mx-md-0" style="width: 76px; height: 76px; font-size: 2rem;">CC</div>
        </div>
        <div class="col-md-7 text-center text-md-start">
          <div class="text-warning fw-bold text-uppercase small" style="letter-spacing: 1.2px;">
            <?= e(COLLEGE_NAME) ?>
          </div>
          <h2 class="fw-bolder mb-1 mt-1 text-white">OFFICIAL VERIFIED TALENT DOSSIER</h2>
          <p class="mb-0 text-light opacity-90 small">
            National Credential Registry &middot; AICTE &middot; Institutional Code: <?= e(PROJECT_CODE) ?>
          </p>
        </div>
        <div class="col-md-3 text-center text-md-end mt-3 mt-md-0">
          <div class="verification-seal bg-light text-dark border-warning shadow-sm mx-auto mx-md-0 d-inline-flex">
            <i class="bi bi-patch-check-fill text-success fs-5"></i>
            <div class="text-start" style="line-height: 1.1;">
              <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">Official Registry</div>
              <div style="font-size: 0.65rem;" class="text-muted"><?= date('d M Y') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Candidate Profile Section -->
    <div class="p-4 p-md-5 border-bottom bg-light">
      <div class="row align-items-center g-4">
        <div class="col-md-8">
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span class="badge bg-primary px-3 py-1"><?= e($candidate['student_id_code']) ?></span>
            <?php if (!empty($candidate['academic_year'])): ?>
              <span class="badge bg-secondary-subtle text-secondary"><?= e($candidate['academic_year']) ?></span>
            <?php endif; ?>
            <?php if (!empty($candidate['is_open_to_work'])): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-briefcase-fill me-1"></i>Open to Hiring / Recruiters
              </span>
            <?php endif; ?>
            <?php if (!empty($candidate['cgpa'])): ?>
              <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                CGPA: <?= number_format((float)$candidate['cgpa'], 2) ?>
              </span>
            <?php endif; ?>
          </div>

          <h3 class="fw-bolder text-dark mb-1"><?= e($candidate['name']) ?></h3>
          <div class="text-secondary fw-semibold mb-2">
            <?= e($candidate['department']) ?> <?= e($candidate['branch'] ? '('.$candidate['branch'].')' : '') ?>
          </div>

          <?php if (!empty($candidate['headline'])): ?>
            <p class="text-dark mb-3 fs-6"><em><?= e($candidate['headline']) ?></em></p>
          <?php endif; ?>

          <!-- Skills tags -->
          <?php if (!empty($candidate['skills'])): ?>
            <div class="mb-3">
              <span class="text-muted small fw-bold text-uppercase d-block mb-1">Demonstrated Technical Competencies:</span>
              <?php foreach (explode(',', $candidate['skills']) as $skill): ?>
                <span class="skill-tag highlight"><?= e(trim($skill)) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Recruiter CTA Buttons -->
          <div class="d-flex flex-wrap gap-2 mt-3 no-print">
            <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#inquiryModal">
              <i class="bi bi-envelope-paper-fill me-1"></i>Contact Candidate / Send Offer
            </button>
            <?php if (is_recruiter()): ?>
              <a href="<?= e(base_url('recruiter/candidate_profile.php?id=' . $candId)) ?>" class="btn btn-outline-primary fw-bold">
                <i class="bi bi-briefcase me-1"></i>Open Recruiter Pipeline
              </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-md-4 text-center">
          <div class="bg-white p-3 rounded-4 shadow-sm border d-inline-block text-center">
            <div id="candidateQrCode" class="mb-2"></div>
            <div class="small fw-bold text-dark">Live Verification Link</div>
            <div class="text-muted" style="font-size: 0.68rem;">Scan to verify on institutional database</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Verified Achievement Metrics -->
    <div class="p-4 bg-white border-bottom">
      <div class="row g-3 text-center">
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="display-6 fw-bold text-primary mb-0"><?= $stats['verified'] ?></div>
            <div class="text-muted small fw-semibold text-uppercase">Verified Achievements</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="display-6 fw-bold text-success mb-0"><?= $stats['winners'] ?></div>
            <div class="text-muted small fw-semibold text-uppercase">First Place / Winners</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="display-6 fw-bold text-info mb-0"><?= $stats['technical'] ?></div>
            <div class="text-muted small fw-semibold text-uppercase">Technical &amp; Coding</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded-3 border">
            <div class="display-6 fw-bold text-warning mb-0"><?= $stats['academic'] + $stats['cultural'] + $stats['sports'] ?></div>
            <div class="text-muted small fw-semibold text-uppercase">Leadership &amp; Co-curricular</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Verified Achievements Table -->
    <div class="p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bolder text-dark mb-0">
            <i class="bi bi-award-fill text-warning me-2"></i>Official Verified Achievements &amp; Proofs
          </h4>
          <p class="text-muted small mb-0">All entries below have undergone cryptographic hashing &amp; institutional review.</p>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold">
          <?= count($verifiedActivities) ?> Verified Records
        </span>
      </div>

      <?php if (empty($verifiedActivities)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-hourglass-split display-4"></i>
          <p class="mt-2">No verified achievements currently certified for this candidate.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle border">
            <thead class="table-light text-secondary small text-uppercase">
              <tr>
                <th style="width: 28%;">Event &amp; Organizer</th>
                <th>Category</th>
                <th>Standing</th>
                <th>Date</th>
                <th>Verification Seal</th>
                <th class="text-end no-print">Certificate Proof</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($verifiedActivities as $act): ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark fs-6"><?= e($act['event_name']) ?></div>
                    <div class="text-muted small"><i class="bi bi-building me-1"></i><?= e($act['organizer']) ?></div>
                    <?php if (!empty($act['achievement_description'])): ?>
                      <div class="text-secondary small mt-1"><?= e(mb_strimwidth($act['achievement_description'], 0, 90, '...')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= category_badge($act['category']) ?></td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                      <?= e($act['participation_type']) ?>
                    </span>
                  </td>
                  <td><?= format_display_date($act['event_date']) ?></td>
                  <td>
                    <div class="verification-seal" style="padding: 3px 8px; font-size: 0.72rem;">
                      <i class="bi bi-patch-check-fill"></i>
                      <span><?= e(substr($act['verification_uuid'], 0, 16)) ?>...</span>
                    </div>
                  </td>
                  <td class="text-end no-print">
                    <div class="btn-group">
                      <a href="<?= e(base_url('certificate.php?uuid=' . urlencode($act['verification_uuid']))) ?>" class="btn btn-sm btn-outline-success" target="_blank" title="View Certificate Document">
                        <i class="bi bi-eye-fill me-1"></i>Proof
                      </a>
                      <a href="<?= e(base_url('verify_credential.php?code=' . urlencode($act['verification_uuid']))) ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Public Verification Portal">
                        <i class="bi bi-shield-check"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Institutional Footer Note -->
      <div class="mt-4 pt-4 border-top text-muted small text-center">
        <p class="mb-1">
          This digital dossier constitutes an official certified transcript of student extracurricular participation and achievements validated by <?= e(COLLEGE_NAME) ?>.
        </p>
        <p class="mb-0 text-secondary" style="font-size: 0.75rem;">
          To cryptographically verify any credential, visit <code><?= full_url('verify_credential.php') ?></code> or scan the individual QR codes.
        </p>
      </div>

    </div>

  </div>

</div>

<!-- Recruiter Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title fw-bold" id="inquiryModalLabel">
          <i class="bi bi-envelope-paper-fill text-gold me-2"></i>Recruit / Contact <?= e($candidate['name']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="public_portfolio.php?id=<?= $candId ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="send_inquiry">
        
        <div class="modal-body p-4">
          <p class="text-muted small mb-3">
            Send an interview invite, job opportunity, or internship inquiry directly to this candidate.
          </p>

          <div class="mb-3">
            <label class="form-label fw-bold small text-secondary">Your Company / Organization Name</label>
            <input type="text" name="company_name" class="form-control" placeholder="e.g. Google, Microsoft, TechCorp Labs" value="<?= e(current_user()['company_name'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-secondary">Your Work Contact Email</label>
            <input type="email" name="contact_email" class="form-control" placeholder="e.g. recruiter@company.com" value="<?= e(current_user()['email'] ?? '') ?>" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label fw-bold small text-secondary">Role / Job Title</label>
              <input type="text" name="job_title" class="form-control" placeholder="e.g. Associate Software Engineer" required>
            </div>
            <div class="col-4">
              <label class="form-label fw-bold small text-secondary">Opportunity Type</label>
              <select name="job_type" class="form-select">
                <option value="Full-time">Full-time</option>
                <option value="Internship">Internship</option>
                <option value="Contract">Contract</option>
                <option value="Part-time">Part-time</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold small text-secondary">Location</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Remote / Bangalore">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small text-secondary">Salary / Stipend Range</label>
              <input type="text" name="salary_range" class="form-control" placeholder="e.g. ₹10 - ₹14 LPA">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-secondary">Message / Next Steps for Candidate</label>
            <textarea name="message" rows="4" class="form-control" placeholder="We were impressed by your verified achievements and would like to invite you for an interview..." required></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
            <i class="bi bi-send-fill me-1"></i>Send Recruitment Message
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof QRCode !== 'undefined') {
      new QRCode(document.getElementById("candidateQrCode"), {
        text: "<?= full_url('public_portfolio.php?id=' . $candId) ?>",
        width: 100,
        height: 100,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
