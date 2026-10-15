<?php
/**
 * CampusCred - Student Career Profile & Recruiter Visibility Settings
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

// Refresh user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$profile = $stmt->fetch() ?: $user;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $headline = trim($_POST['headline'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $cgpa = trim($_POST['cgpa'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $linkedin = trim($_POST['linkedin_url'] ?? '');
    $github = trim($_POST['github_url'] ?? '');
    $isOpen = !empty($_POST['is_open_to_work']) ? 1 : 0;

    $cgpaVal = null;
    if ($cgpa !== '') {
        $cgpaVal = (float)$cgpa;
        if ($cgpaVal < 0 || $cgpaVal > 10.0) {
            $errors[] = 'CGPA must be between 0.00 and 10.00.';
        }
    }

    if (empty($errors)) {
        $up = $pdo->prepare("
            UPDATE users SET 
                headline = ?,
                skills = ?,
                bio = ?,
                cgpa = ?,
                phone = ?,
                linkedin_url = ?,
                github_url = ?,
                is_open_to_work = ?
            WHERE id = ?
        ");
        $up->execute([$headline, $skills, $bio, $cgpaVal, $phone, $linkedin, $github, $isOpen, $studentId]);
        
        // Refresh session
        $stmt->execute([$studentId]);
        $profile = $stmt->fetch();
        $_SESSION['user'] = $profile;

        set_flash('success', 'Your career profile and recruiter visibility settings were updated successfully!');
        header('Location: ' . base_url('student/profile.php'));
        exit;
    }
}

$shareableUrl = full_url('public_portfolio.php?id=' . $studentId);
$page_title = 'Career Profile & Recruiter Visibility · ' . e($profile['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
  <div>
    <h3 class="fw-bolder text-dark mb-1">
      <i class="bi bi-person-badge text-primary me-2"></i>Career Profile &amp; Recruiter Visibility
    </h3>
    <p class="text-muted small mb-0">Highlight your skills, technical proficiencies, and career preferences to companies recruiting on CampusCred.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('public_portfolio.php?id=' . $studentId)) ?>" class="btn btn-outline-primary btn-sm fw-bold" target="_blank">
      <i class="bi bi-box-arrow-up-right me-1"></i>View Live Public Portfolio
    </a>
    <a href="<?= e(base_url('student/dashboard.php')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
  </div>
</div>

<div class="row g-4">
  
  <!-- Left Column: Form -->
  <div class="col-lg-8">
    
    <?php if ($errors): ?>
      <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
        <?php foreach ($errors as $e): ?>
          <div><i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
      <form method="POST" action="profile.php">
        <?= csrf_field() ?>

        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
          <i class="bi bi-briefcase text-primary me-2"></i>Recruiter Showcase Details
        </h5>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">
            Professional Headline / One-Line Summary
          </label>
          <input type="text" name="headline" class="form-control" placeholder="e.g. Smart India Hackathon Winner | Python, Drone Robotics &amp; Full-Stack Dev" value="<?= e($profile['headline'] ?? '') ?>">
          <div class="form-text small">This headline is shown prominently to recruiters searching the talent directory.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">
            Demonstrated Skills &amp; Technologies (Comma-Separated)
          </label>
          <input type="text" name="skills" class="form-control" placeholder="e.g. Python, React, Docker, Django, AWS, IoT, Machine Learning" value="<?= e($profile['skills'] ?? '') ?>">
          <div class="form-text small">Enter skills separated by commas so recruiters can filter and match you with relevant roles.</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small text-secondary">Cumulative GPA (Scale of 10.0)</label>
            <input type="number" step="0.01" min="0" max="10" name="cgpa" class="form-control" placeholder="e.g. 8.85" value="<?= e($profile['cgpa'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small text-secondary">Contact Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 9829112233" value="<?= e($profile['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small text-secondary">
              <i class="bi bi-linkedin text-primary me-1"></i>LinkedIn Profile URL
            </label>
            <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/yourprofile" value="<?= e($profile['linkedin_url'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small text-secondary">
              <i class="bi bi-github text-dark me-1"></i>GitHub Profile URL
            </label>
            <input type="url" name="github_url" class="form-control" placeholder="https://github.com/yourhandle" value="<?= e($profile['github_url'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold small text-secondary">Bio / Career Objective</label>
          <textarea name="bio" rows="4" class="form-control" placeholder="Briefly describe your career interests, major project highlights, or what kind of engineering or research problems you are passionate about solving..."><?= e($profile['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-check form-switch p-3 bg-light rounded-3 border mb-4">
          <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="openToWorkSwitch" name="is_open_to_work" value="1" <?= !empty($profile['is_open_to_work']) ? 'checked' : '' ?>>
          <label class="form-check-label fw-bold text-dark" for="openToWorkSwitch">
            <i class="bi bi-check-circle-fill text-success me-1"></i>Open to Recruitment Inquiries &amp; Internship Offers
            <div class="text-muted fw-normal small">When enabled, verified recruiters can discover your profile in the talent pool and dispatch interview invites.</div>
          </label>
        </div>

        <button type="submit" class="btn btn-primary fw-bold px-4">
          <i class="bi bi-save me-1"></i>Save Profile Changes
        </button>
      </form>
    </div>

  </div>

  <!-- Right Column: Shareable Link & QR Code Preview -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4 text-center">
      <div class="text-uppercase fw-bold text-primary small mb-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">
        <i class="bi bi-share me-1"></i>Your Verified Public URL
      </div>
      <h5 class="fw-bold text-dark mb-2">Share With Recruiters</h5>
      <p class="text-muted small mb-3">
        Place this URL or QR code on your resume, LinkedIn, or portfolio to let hiring managers inspect your verified achievements.
      </p>

      <div class="bg-light p-3 rounded-4 border d-inline-block mb-3">
        <div id="shareQrCode"></div>
      </div>

      <div class="input-group mb-3">
        <input type="text" class="form-control form-control-sm font-monospace" value="<?= e($shareableUrl) ?>" readonly id="shareUrlInput">
        <button class="btn btn-primary btn-sm" type="button" onclick="copyToClipboard('<?= e($shareableUrl) ?>', this)">
          <i class="bi bi-clipboard"></i>
        </button>
      </div>

      <a href="<?= e($shareableUrl) ?>" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
        <i class="bi bi-box-arrow-up-right me-1"></i>Preview Public Portfolio
      </a>
    </div>

    <!-- Tips Card -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-light">
      <h6 class="fw-bold text-dark mb-2">
        <i class="bi bi-lightbulb-fill text-warning me-1"></i>Pro-Tips for Candidates
      </h6>
      <ul class="text-muted small ps-3 mb-0" style="line-height: 1.6;">
        <li>Keep your technical skills updated with languages and frameworks used in your verified projects.</li>
        <li>Recruiters heavily value verified competition wins (Smart India Hackathon, ICPC, Robotics Expo).</li>
        <li>Make sure your GitHub and LinkedIn links are valid so employers can review your open-source code.</li>
      </ul>
    </div>
  </div>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof QRCode !== 'undefined') {
      new QRCode(document.getElementById("shareQrCode"), {
        text: "<?= $shareableUrl ?>",
        width: 140,
        height: 140,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
