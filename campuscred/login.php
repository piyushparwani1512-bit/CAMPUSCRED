<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_by_role();
}

$errors = [];
$identifier = '';
$selectedTab = $_POST['portal_type'] ?? ($_GET['portal'] ?? 'student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $portalType = $_POST['portal_type'] ?? 'student';

    if ($identifier === '') {
        $errors[] = 'Please enter your ID or registered email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your account password.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('
            SELECT id, student_id_code, name, email, password_hash, role, department, branch, academic_year, phone, company_name, designation 
            FROM users 
            WHERE LOWER(email) = LOWER(?) OR UPPER(student_id_code) = UPPER(?) 
            LIMIT 1
        ');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($portalType === 'admin' && $user['role'] !== 'admin') {
                $errors[] = 'Unauthorized Access: This account does not possess Administrator privileges.';
            } elseif ($portalType === 'recruiter' && $user['role'] !== 'recruiter' && $user['role'] !== 'admin') {
                $errors[] = 'Unauthorized Access: This account is not registered as a Corporate Recruiter.';
            } else {
                session_regenerate_id(true);
                unset($user['password_hash']);
                $_SESSION['user'] = $user;

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect_by_role();
            }
        } else {
            $errors[] = 'Invalid credentials. Please verify your Email/ID and password.';
        }
    }
}

$page_title = 'Sign In · ' . COLLEGE_SHORT_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-4 my-auto">
  <div class="col-md-8 col-lg-6 col-xl-5">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
      
      <div class="p-4 text-center bg-white border-bottom">
        <div class="brand-emblem mx-auto mb-2" style="width: 56px; height: 56px; font-size: 1.4rem;">CC</div>
        <div class="text-uppercase fw-bold text-muted small" style="letter-spacing: 1px;"><?= e(COLLEGE_SHORT_NAME) ?> · <?= e(PROJECT_CODE) ?></div>
        <h4 class="fw-bolder text-primary mb-1 mt-1">Student &amp; Recruiter Portal</h4>
        <p class="text-muted small mb-0">Centralized Digital Record, Talent Discovery &amp; Verification Portal</p>
      </div>

      <!-- 3 Portal Tabs: Student, Recruiter, Admin -->
      <div class="bg-light px-3 pt-3 border-bottom">
        <ul class="nav nav-pills nav-fill" id="loginTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 <?= $selectedTab === 'student' ? 'active' : '' ?>" id="student-tab" data-bs-toggle="pill" data-bs-target="#tab-content" type="button" role="tab" onclick="selectPortal('student');">
              <i class="bi bi-mortarboard-fill me-1"></i>Student
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 <?= $selectedTab === 'recruiter' ? 'active' : '' ?>" id="recruiter-tab" data-bs-toggle="pill" data-bs-target="#tab-content" type="button" role="tab" onclick="selectPortal('recruiter');">
              <i class="bi bi-briefcase-fill me-1"></i>Company / Recruiter
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 <?= $selectedTab === 'admin' ? 'active' : '' ?>" id="admin-tab" data-bs-toggle="pill" data-bs-target="#tab-content" type="button" role="tab" onclick="selectPortal('admin');">
              <i class="bi bi-shield-lock-fill me-1"></i>Admin
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body p-4 bg-white">
        
        <?php if ($errors): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?php foreach ($errors as $error): ?>
              <div><?= e($error) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php" class="needs-validation">
          <?= csrf_field() ?>
          <input type="hidden" name="portal_type" id="portal_type" value="<?= e($selectedTab) ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary" id="identifier_label">
              <?= $selectedTab === 'recruiter' ? 'Work Email Address' : ($selectedTab === 'admin' ? 'Admin Institutional Email' : 'Student Roll/College ID or Email') ?>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person-badge"></i></span>
              <input type="text" name="identifier" id="login_identifier" class="form-control border-start-0 ps-0" placeholder="Enter your ID or Email" value="<?= e($identifier) ?>" required autofocus autocomplete="username">
            </div>
            <div class="form-text small" id="identifier_hint">
              <?= $selectedTab === 'recruiter' ? 'Companies: Log in with your registered corporate work email.' : 'Students: Enter Roll/College ID or Email. Admins: Enter admin email.' ?>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
              <input type="password" name="password" id="login_password" class="form-control border-start-0 ps-0" placeholder="••••••••" required autocomplete="current-password">
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-3" id="login_submit_btn">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In to Portal
          </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
          <div class="small">
            <span class="text-muted">Need an account?</span>
            <a href="register.php?portal=student" class="fw-bold text-decoration-none ms-1">Register Student</a>
            <span class="text-muted mx-1">&middot;</span>
            <a href="register.php?portal=recruiter" class="fw-bold text-decoration-none text-warning-emphasis">Register as Company</a>
          </div>
        </div>

      </div>

      <!-- Quick Evaluator 1-Click Credentials -->
      <div class="card-footer bg-light p-3 border-top">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <small class="fw-bold text-secondary text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Evaluator 1-Click Demo Logins
          </small>
        </div>
        <div class="d-grid gap-1">
          <!-- Recruiter button -->
          <button type="button" class="btn btn-sm btn-outline-warning text-dark text-start d-flex justify-content-between align-items-center" onclick="fillLogin('recruiter@techcorp.com', 'Recruiter@123', 'recruiter');">
            <span><i class="bi bi-briefcase-fill text-warning me-1"></i><strong>Company Recruiter:</strong> recruiter@techcorp.com</span>
            <span class="badge bg-warning-subtle text-dark border">Recruiter@123</span>
          </button>
          <!-- Student button -->
          <button type="button" class="btn btn-sm btn-outline-primary text-start d-flex justify-content-between align-items-center" onclick="fillLogin('aravind@skit.ac.in', 'Student@123', 'student');">
            <span><i class="bi bi-mortarboard-fill me-1"></i><strong>Student (CSE):</strong> aravind@skit.ac.in</span>
            <span class="badge bg-primary-subtle text-primary border">Student@123</span>
          </button>
          <!-- Admin button -->
          <button type="button" class="btn btn-sm btn-outline-danger text-start d-flex justify-content-between align-items-center" onclick="fillLogin('admin@skit.ac.in', 'Admin@123', 'admin');">
            <span><i class="bi bi-shield-lock-fill me-1"></i><strong>Admin:</strong> admin@skit.ac.in</span>
            <span class="badge bg-danger-subtle text-danger border">Admin@123</span>
          </button>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
function selectPortal(type) {
  document.getElementById('portal_type').value = type;
  const label = document.getElementById('identifier_label');
  const hint = document.getElementById('identifier_hint');
  const btn = document.getElementById('login_submit_btn');

  if (type === 'recruiter') {
    label.textContent = 'Company Work Email';
    hint.textContent = 'Recruiters: Enter your corporate email address.';
    btn.innerHTML = '<i class="bi bi-briefcase-fill me-1"></i>Sign In as Recruiter';
  } else if (type === 'admin') {
    label.textContent = 'Administrator Institutional Email';
    hint.textContent = 'Institutional Administrators only.';
    btn.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i>Sign In as Admin';
  } else {
    label.textContent = 'Student Roll/College ID or Email';
    hint.textContent = 'Students: Enter your Roll ID or registered student email.';
    btn.innerHTML = '<i class="bi bi-mortarboard-fill me-1"></i>Sign In as Student';
  }
}

function fillLogin(email, password, type) {
  document.getElementById('login_identifier').value = email;
  document.getElementById('login_password').value = password;
  if (type === 'recruiter') {
    document.getElementById('recruiter-tab').click();
  } else if (type === 'admin') {
    document.getElementById('admin-tab').click();
  } else {
    document.getElementById('student-tab').click();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
