<?php
/**
 * CampusCred - Comprehensive Candidate Profile & Certificate Proof Dossier for Recruiters
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('recruiter');

$recruiter = current_user();
$recruiterId = (int)$recruiter['id'];

$studentId = (int)($_GET['id'] ?? 0);
if ($studentId <= 0) {
    set_flash('error', 'Invalid candidate requested.');
    header('Location: ' . base_url('recruiter/dashboard.php'));
    exit;
}

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student' LIMIT 1");
$stmt->execute([$studentId]);
$candidate = $stmt->fetch();

if (!$candidate) {
    set_flash('error', 'Candidate record not found.');
    header('Location: ' . base_url('recruiter/dashboard.php'));
    exit;
}

// Fetch existing bookmark / pipeline state
$bmStmt = $pdo->prepare("SELECT * FROM recruiter_bookmarks WHERE recruiter_id = ? AND student_id = ? LIMIT 1");
$bmStmt->execute([$recruiterId, $studentId]);
$bookmark = $bmStmt->fetch();

// Fetch ALL achievements of this student (Verified + other submissions so recruiter gets full picture)
$actStmt = $pdo->prepare("
    SELECT a.*, rev.name as reviewer_name
    FROM activities a
    LEFT JOIN users rev ON a.reviewer_id = rev.id
    WHERE a.student_id = ?
    ORDER BY (a.status = 'Verified') DESC, a.event_date DESC
");
$actStmt->execute([$studentId]);
$activities = $actStmt->fetchAll();

// Candidate stats
$stats = get_student_stats($pdo, $studentId);

// Inquiries sent to this candidate by this recruiter
$inqStmt = $pdo->prepare("
    SELECT * FROM recruitment_inquiries 
    WHERE recruiter_id = ? AND student_id = ? 
    ORDER BY created_at DESC
");
$inqStmt->execute([$recruiterId, $studentId]);
$sentInquiries = $inqStmt->fetchAll();

$page_title = 'Candidate Dossier · ' . e($candidate['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 no-print">
  <div>
    <div class="text-uppercase fw-bold text-primary small" style="font-size: 0.72rem; letter-spacing: 1px;">
      <a href="dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left me-1"></i>Talent Hub</a>
      &middot; Candidate Evaluation Dossier
    </div>
    <h3 class="fw-bolder text-dark mb-0"><?= e($candidate['name']) ?></h3>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" onclick="window.print()">
      <i class="bi bi-printer-fill me-1"></i>Print Dossier
    </button>
    <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="generatePortfolioPDF()">
      <i class="bi bi-file-earmark-pdf-fill me-1"></i>Export PDF
    </button>
    <a href="<?= e(base_url('public_portfolio.php?id=' . $studentId)) ?>" class="btn btn-outline-secondary btn-sm" target="_blank" title="View Public Student Shareable Page">
      <i class="bi bi-box-arrow-up-right me-1"></i>Public Link
    </a>
  </div>
</div>

<div class="row g-4" id="portfolioDocument">

  <!-- Left Column: Candidate Overview & Recruiter Pipeline -->
  <div class="col-lg-4">

    <!-- Candidate Card -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
      <div class="text-center mb-3">
        <div class="brand-emblem mx-auto mb-2" style="width: 70px; height: 70px; font-size: 1.8rem; background: linear-gradient(135deg, #1e3a8a, #2563eb);">
          <?= strtoupper(substr($candidate['name'], 0, 1)) ?>
        </div>
        <h4 class="fw-bolder text-dark mb-1"><?= e($candidate['name']) ?></h4>
        <div class="text-secondary small fw-semibold"><?= e($candidate['department']) ?></div>
        <div class="text-muted small"><?= e($candidate['branch'] ? 'Branch: ' . $candidate['branch'] : '') ?></div>
      </div>

      <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fw-bold">
          <?= e($candidate['student_id_code']) ?>
        </span>
        <?php if (!empty($candidate['cgpa'])): ?>
          <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
            CGPA: <?= number_format((float)$candidate['cgpa'], 2) ?>
          </span>
        <?php endif; ?>
        <?php if (!empty($candidate['academic_year'])): ?>
          <span class="badge bg-light text-muted border"><?= e($candidate['academic_year']) ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($candidate['headline'])): ?>
        <div class="alert alert-light border small text-dark p-2 mb-3 text-center">
          <em>"<?= e($candidate['headline']) ?>"</em>
        </div>
      <?php endif; ?>

      <!-- Contact details -->
      <div class="border-top pt-3 small">
        <div class="d-flex align-items-center mb-2">
          <i class="bi bi-envelope text-muted me-2"></i>
          <span class="text-truncate"><?= e($candidate['email']) ?></span>
        </div>
        <?php if (!empty($candidate['phone'])): ?>
          <div class="d-flex align-items-center mb-2">
            <i class="bi bi-telephone text-muted me-2"></i>
            <span><?= e($candidate['phone']) ?></span>
          </div>
        <?php endif; ?>
        <?php if (!empty($candidate['linkedin_url'])): ?>
          <div class="d-flex align-items-center mb-2">
            <i class="bi bi-linkedin text-primary me-2"></i>
            <a href="<?= e($candidate['linkedin_url']) ?>" target="_blank" class="text-decoration-none">LinkedIn Profile</a>
          </div>
        <?php endif; ?>
        <?php if (!empty($candidate['github_url'])): ?>
          <div class="d-flex align-items-center mb-2">
            <i class="bi bi-github text-dark me-2"></i>
            <a href="<?= e($candidate['github_url']) ?>" target="_blank" class="text-decoration-none">GitHub Portfolio</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Skills tags -->
      <?php if (!empty($candidate['skills'])): ?>
        <div class="border-top pt-3 mt-2">
          <div class="fw-bold small text-secondary text-uppercase mb-2">Competencies &amp; Skills:</div>
          <div>
            <?php foreach (explode(',', $candidate['skills']) as $sk): ?>
              <span class="skill-tag highlight"><?= e(trim($sk)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Recruiter Hiring Pipeline Box (No-print) -->
    <div class="card shadow-sm border-warning border-2 rounded-4 p-4 bg-white mb-4 no-print">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold text-dark mb-0">
          <i class="bi bi-kanban text-warning me-1"></i>Hiring Pipeline
        </h5>
        <span class="badge <?= !empty($bookmark['status']) ? 'bg-warning text-dark' : 'bg-light text-muted border' ?>">
          <?= e($bookmark['status'] ?? 'Not Shortlisted') ?>
        </span>
      </div>

      <form method="POST" action="action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_bookmark_status">
        <input type="hidden" name="student_id" value="<?= $studentId ?>">
        <input type="hidden" name="redirect" value="recruiter/candidate_profile.php?id=<?= $studentId ?>">

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">Recruitment Stage</label>
          <select name="status" class="form-select">
            <option value="Saved" <?= ($bookmark['status'] ?? '') === 'Saved' ? 'selected' : '' ?>>⭐ Saved in Shortlist</option>
            <option value="Screening" <?= ($bookmark['status'] ?? '') === 'Screening' ? 'selected' : '' ?>>🔍 Screening / Resume Review</option>
            <option value="Interviewing" <?= ($bookmark['status'] ?? '') === 'Interviewing' ? 'selected' : '' ?>>💬 Interview Scheduled</option>
            <option value="Offered" <?= ($bookmark['status'] ?? '') === 'Offered' ? 'selected' : '' ?>>🎉 Job / Internship Offered</option>
            <option value="Archived" <?= ($bookmark['status'] ?? '') === 'Archived' ? 'selected' : '' ?>>📁 Archived / Passed</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">Private Recruiter Notes</label>
          <textarea name="notes" rows="3" class="form-control" placeholder="Add confidential recruiter notes on coding interview, communication, or package discussion..."><?= e($bookmark['notes'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-warning text-dark fw-bold flex-grow-1">
            <i class="bi bi-save me-1"></i>Save Pipeline Stage
          </button>
          <?php if (!empty($bookmark)): ?>
            <button type="submit" name="delete_bookmark" value="1" class="btn btn-outline-danger" title="Remove from Shortlist">
              <i class="bi bi-trash"></i>
            </button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Send Offer / Inquiry Box (No-print) -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4 no-print" id="inquirySection">
      <h5 class="fw-bold text-dark mb-2">
        <i class="bi bi-envelope-paper-fill text-primary me-2"></i>Dispatch Interview / Job Offer
      </h5>
      <p class="text-muted small mb-3">Send official recruitment inquiry or interview invite straight to <?= e($candidate['name']) ?>'s student portal.</p>

      <form method="POST" action="action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="send_inquiry">
        <input type="hidden" name="student_id" value="<?= $studentId ?>">
        <input type="hidden" name="redirect" value="recruiter/candidate_profile.php?id=<?= $studentId ?>">

        <div class="mb-2">
          <label class="form-label fw-bold small text-secondary">Job Title / Role</label>
          <input type="text" name="job_title" class="form-control" placeholder="e.g. Associate Software Engineer - Cloud" required>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label fw-bold small text-secondary">Role Type</label>
            <select name="job_type" class="form-select">
              <option value="Full-time">Full-time</option>
              <option value="Internship">Internship</option>
              <option value="Contract">Contract</option>
              <option value="Part-time">Part-time</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-bold small text-secondary">Package / Stipend</label>
            <input type="text" name="salary_range" class="form-control" placeholder="e.g. ₹12 - ₹15 LPA">
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label fw-bold small text-secondary">Location</label>
          <input type="text" name="location" class="form-control" placeholder="e.g. Bangalore / Hybrid / Remote">
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">Message / Technical Round Details</label>
          <textarea name="message" rows="3" class="form-control" placeholder="Hello, we reviewed your verified SIH achievement and would like to invite you for an interview..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary fw-bold w-100">
          <i class="bi bi-send-fill me-1"></i>Dispatch Recruitment Offer
        </button>
      </form>

      <?php if (!empty($sentInquiries)): ?>
        <div class="border-top pt-3 mt-3">
          <div class="fw-bold small text-secondary mb-2">Prior Communications (<?= count($sentInquiries) ?>):</div>
          <?php foreach ($sentInquiries as $inq): ?>
            <div class="p-2 bg-light rounded border mb-2 small">
              <div class="d-flex justify-content-between">
                <strong class="text-dark"><?= e($inq['job_title']) ?></strong>
                <span class="badge bg-secondary-subtle text-secondary"><?= e($inq['status']) ?></span>
              </div>
              <div class="text-muted" style="font-size: 0.72rem;"><?= format_display_datetime($inq['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right Column: All Verified Achievements & Certificate Inspection -->
  <div class="col-lg-8">

    <!-- Metrics Strip -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
      <div class="row g-3 text-center">
        <div class="col-6 col-sm-3">
          <div class="display-6 fw-bold text-success"><?= $stats['verified'] ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Verified Achievements</div>
        </div>
        <div class="col-6 col-sm-3">
          <div class="display-6 fw-bold text-warning"><?= $stats['winners'] ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Winner / 1st Place</div>
        </div>
        <div class="col-6 col-sm-3">
          <div class="display-6 fw-bold text-primary"><?= $stats['technical'] ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Technical / Hackathons</div>
        </div>
        <div class="col-6 col-sm-3">
          <div class="display-6 fw-bold text-info"><?= $stats['academic'] + $stats['sports'] + $stats['cultural'] ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Leadership &amp; Sports</div>
        </div>
      </div>
    </div>

    <!-- Detailed Achievements & Certificates List -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bolder text-dark mb-1">
            <i class="bi bi-shield-check text-success me-2"></i>Authenticated Achievements &amp; Proofs
          </h4>
          <p class="text-muted small mb-0">Inspect uploaded certificates, cryptographic signatures, and institutional verification notes.</p>
        </div>
        <span class="badge bg-success px-3 py-2 fs-6">
          <?= count($activities) ?> Total Records
        </span>
      </div>

      <?php if (empty($activities)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-folder-x display-4"></i>
          <p class="mt-2">No achievements submitted by this candidate yet.</p>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-4">
          <?php foreach ($activities as $act): ?>
            <?php 
              $isVerified = ($act['status'] === 'Verified');
              $certUrl = base_url('certificate.php?id=' . (int)$act['id']);
              $verifyUrl = base_url('verify_credential.php?code=' . urlencode($act['verification_uuid']));
              $isPdf = str_ends_with(strtolower($act['certificate_original_name'] ?: $act['certificate_path']), '.pdf');
            ?>
            <div class="card border <?= $isVerified ? 'border-success-subtle shadow-sm' : 'border-secondary-subtle' ?> rounded-4 p-4">
              
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-2">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <?= status_badge($act['status']) ?>
                    <?= category_badge($act['category']) ?>
                    <span class="badge bg-primary px-3 py-1 fw-bold">
                      <i class="bi bi-trophy-fill me-1"></i><?= e($act['participation_type']) ?>
                    </span>
                  </div>
                  <h5 class="fw-bolder text-dark mb-1"><?= e($act['event_name']) ?></h5>
                  <div class="text-secondary small fw-semibold">
                    <i class="bi bi-building me-1"></i><?= e($act['organizer']) ?> &middot;
                    <i class="bi bi-calendar-event me-1"></i><?= format_display_date($act['event_date']) ?>
                  </div>
                </div>

                <div class="text-sm-end">
                  <span class="badge bg-light text-muted border font-monospace small">
                    <?= e($act['submission_code']) ?>
                  </span>
                </div>
              </div>

              <?php if (!empty($act['achievement_description'])): ?>
                <div class="bg-light p-3 rounded-3 my-2 text-dark small">
                  <strong>Candidate's Submission Notes:</strong><br>
                  <?= nl2br(e($act['achievement_description'])) ?>
                </div>
              <?php endif; ?>

              <!-- Cryptographic Verification Details -->
              <div class="p-3 bg-dark text-light rounded-3 my-2 font-monospace small">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-warning fw-bold"><i class="bi bi-patch-check-fill me-1"></i>Verification UUID:</span>
                  <a href="<?= e($verifyUrl) ?>" target="_blank" class="text-info text-decoration-none small">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Public Verify
                  </a>
                </div>
                <div class="text-truncate"><code><?= e($act['verification_uuid']) ?></code></div>
                <?php if (!empty($act['reviewer_name'])): ?>
                  <div class="mt-2 text-muted" style="font-size: 0.76rem;">
                    Institutional Reviewer: <strong class="text-light"><?= e($act['reviewer_name']) ?></strong> on <?= format_display_datetime($act['reviewed_at']) ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($act['review_notes'])): ?>
                  <div class="mt-1 text-info small">
                    Reviewer Verification Note: <em>"<?= e($act['review_notes']) ?>"</em>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Certificate Access & Modal Viewer -->
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top no-print">
                <div class="small text-muted">
                  <i class="bi bi-paperclip me-1"></i>Original Attachment: 
                  <strong><?= e($act['certificate_original_name'] ?: basename($act['certificate_path'])) ?></strong>
                </div>

                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-success fw-bold" onclick="previewCertificate('<?= e($certUrl) ?>', '<?= e(addslashes($act['event_name'])) ?>', '<?= $isPdf ? 'application/pdf' : 'image/jpeg' ?>')">
                    <i class="bi bi-eye-fill me-1"></i>Inspect Certificate Proof
                  </button>
                  <a href="<?= e($certUrl) ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Open Document in New Tab">
                    <i class="bi bi-box-arrow-up-right"></i>
                  </a>
                  <a href="<?= e($certUrl) ?>" download class="btn btn-sm btn-outline-secondary" title="Download Certificate">
                    <i class="bi bi-download"></i>
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
