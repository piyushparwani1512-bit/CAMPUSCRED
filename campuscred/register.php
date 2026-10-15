<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_by_role();
}

$errors = [];
$portalType = $_POST['portal_type'] ?? ($_GET['portal'] ?? 'student');

// Common fields
$name = '';
$email = '';
$phone = '';

// Student specific
$student_id_code = '';
$department = 'Computer Science & Engineering';
$branch = 'CSE';
$academic_year = '1st Year';

// Recruiter specific
$company_name = '';
$company_website = '';
$industry = 'Software & Technology';
$designation = '';

$departments = [
    'Computer Science & Engineering' => ['CSE', 'CS-AI', 'CS-DS', 'CS-IoT'],
    'Information Technology' => ['IT'],
    'Electronics & Communication' => ['ECE'],
    'Electrical Engineering' => ['EE'],
    'Mechanical Engineering' => ['ME'],
    'Civil Engineering' => ['CE'],
    'Management Studies' => ['MBA'],
];

$industries = [
    'Software & Information Technology',
    'Financial Services & FinTech',
    'Core Engineering & Manufacturing',
    'Consulting & Analytics',
    'Robotics & Hardware Systems',
    'Healthcare & BioTech',
    'EdTech & Education',
    'Other / Conglomerate'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $portalType = $_POST['portal_type'] ?? 'student';
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($portalType === 'recruiter') {
        $company_name = trim($_POST['company_name'] ?? '');
        $company_website = trim($_POST['company_website'] ?? '');
        $industry = trim($_POST['industry'] ?? 'Software & Technology');
        $designation = trim($_POST['designation'] ?? '');

        if ($company_name === '') {
            $errors[] = 'Company / Organization name is required.';
        }
        if ($designation === '') {
            $errors[] = 'Your job designation (e.g. University Talent Lead) is required.';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'This corporate email address is already registered. Please sign in instead.';
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, role, department, phone, company_name, company_website, designation, industry, created_at)
                    VALUES (?, ?, ?, 'recruiter', 'Talent Acquisition', ?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute([
                    $name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $phone,
                    $company_name,
                    $company_website,
                    $designation,
                    $industry
                ]);

                set_flash('success', 'Company recruiter account registered successfully! You can now sign in.');
                header('Location: login.php?portal=recruiter');
                exit;
            }
        }
    } else {
        // Student Registration
        $student_id_code = strtoupper(trim($_POST['student_id_code'] ?? ''));
        $department = trim($_POST['department'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');

        if ($student_id_code === '') {
            $errors[] = 'Please enter your Student Roll / Enrollment ID (e.g. SKIT/2024/CSE/050).';
        }
        if ($department === '') {
            $errors[] = 'Please select your academic department.';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id, email, student_id_code FROM users WHERE LOWER(email) = LOWER(?) OR UPPER(student_id_code) = UPPER(?) LIMIT 1');
            $check->execute([$email, $student_id_code]);
            $existing = $check->fetch();

            if ($existing) {
                if (strtolower($existing['email']) === strtolower($email)) {
                    $errors[] = 'This email address is already registered. Please sign in instead.';
                } else {
                    $errors[] = 'This Student ID (' . e($student_id_code) . ') is already registered in the system.';
                }
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO users (student_id_code, name, email, password_hash, role, department, branch, academic_year, admission_year, phone) 
                    VALUES (?, ?, ?, ?, "student", ?, ?, ?, ?, ?)
                ');
                $admYear = (int)date('Y');
                $stmt->execute([
                    $student_id_code,
                    $name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $department,
                    $branch,
                    $academic_year,
                    $admYear,
                    $phone
                ]);

                set_flash('success', 'Student account created successfully! You can now log in.');
                header('Location: login.php?portal=student');
                exit;
            }
        }
    }
}

$page_title = 'Create Account · ' . COLLEGE_SHORT_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-4">
  <div class="col-md-9 col-lg-7 col-xl-6">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden bg-white">
      
      <div class="p-4 text-center bg-white border-bottom">
        <div class="brand-emblem mx-auto mb-2" style="width: 56px; height: 56px; font-size: 1.4rem;">CC</div>
        <div class="text-uppercase fw-bold text-muted small" style="letter-spacing: 1px;"><?= e(COLLEGE_SHORT_NAME) ?> &middot; Portal Registration</div>
        <h4 class="fw-bolder text-primary mb-1 mt-1">Join <?= e(APP_NAME) ?></h4>
        <p class="text-muted small mb-0">Select your account type to register your credentials</p>
      </div>

      <!-- Registration Portal Switcher Tabs -->
      <div class="bg-light px-3 pt-3 border-bottom">
        <ul class="nav nav-pills nav-fill" role="tablist">
          <li class="nav-item">
            <button class="nav-link py-2 <?= $portalType !== 'recruiter' ? 'active' : '' ?>" type="button" onclick="setRegType('student');">
              <i class="bi bi-mortarboard-fill me-1"></i>Register as Student
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link py-2 <?= $portalType === 'recruiter' ? 'active' : '' ?>" type="button" onclick="setRegType('recruiter');">
              <i class="bi bi-briefcase-fill me-1"></i>Register as Company / Recruiter
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body p-4 p-md-5">

        <?php if ($errors): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?php foreach ($errors as $error): ?>
              <div><?= e($error) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="needs-validation">
          <?= csrf_field() ?>
          <input type="hidden" name="portal_type" id="portal_type" value="<?= e($portalType) ?>">

          <!-- Common Fields -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary" id="name_label">
                <?= $portalType === 'recruiter' ? 'Recruiter Contact Name *' : 'Full Student Name *' ?>
              </label>
              <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" value="<?= e($name) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary" id="email_label">
                <?= $portalType === 'recruiter' ? 'Corporate Work Email *' : 'Student Institutional Email *' ?>
              </label>
              <input type="email" name="email" class="form-control" placeholder="name@domain.com" value="<?= e($email) ?>" required>
            </div>
          </div>

          <!-- Student Section -->
          <div id="student_fields" style="<?= $portalType === 'recruiter' ? 'display:none;' : '' ?>">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Student Roll / Enrollment ID *</label>
                <input type="text" name="student_id_code" class="form-control" placeholder="e.g. SKIT/2024/CSE/050" value="<?= e($student_id_code) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Academic Year</label>
                <select name="academic_year" class="form-select">
                  <option value="1st Year" <?= $academic_year === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                  <option value="2nd Year" <?= $academic_year === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                  <option value="3rd Year" <?= $academic_year === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                  <option value="4th Year" <?= $academic_year === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                  <option value="Alumni" <?= $academic_year === 'Alumni' ? 'selected' : '' ?>>Alumni / Graduated</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label fw-bold small text-secondary">Academic Department *</label>
                <select name="department" class="form-select">
                  <?php foreach ($departments as $dept => $branches): ?>
                    <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">Branch Code</label>
                <input type="text" name="branch" class="form-control" placeholder="e.g. CSE" value="<?= e($branch) ?>">
              </div>
            </div>
          </div>

          <!-- Recruiter Section -->
          <div id="recruiter_fields" style="<?= $portalType !== 'recruiter' ? 'display:none;' : '' ?>">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Company / Organization Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. TechCorp Labs, Google, TCS" value="<?= e($company_name) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Your Designation / Role *</label>
                <input type="text" name="designation" class="form-control" placeholder="e.g. Senior University Recruiter" value="<?= e($designation) ?>">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Industry / Sector</label>
                <select name="industry" class="form-select">
                  <?php foreach ($industries as $ind): ?>
                    <option value="<?= e($ind) ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Company Website URL</label>
                <input type="url" name="company_website" class="form-control" placeholder="https://company.com" value="<?= e($company_website) ?>">
              </div>
            </div>
          </div>

          <!-- Contact phone -->
          <div class="mb-3">
            <label class="form-label fw-bold small text-secondary">Contact Phone Number</label>
            <input type="tel" name="phone" class="form-control" placeholder="e.g. 9829012345" value="<?= e($phone) ?>">
          </div>

          <!-- Password Row -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Password (min 8 chars) *</label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Confirm Password *</label>
              <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-3" id="submit_btn">
            <i class="bi bi-person-check-fill me-1"></i>Create <?= $portalType === 'recruiter' ? 'Recruiter' : 'Student' ?> Account
          </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
          <span class="text-muted small">Already have an account?</span>
          <a href="login.php" class="fw-bold text-decoration-none small ms-1">Sign In</a>
        </div>

      </div>

    </div>

  </div>
</div>

<script>
function setRegType(type) {
  document.getElementById('portal_type').value = type;
  const studentFields = document.getElementById('student_fields');
  const recruiterFields = document.getElementById('recruiter_fields');
  const nameLabel = document.getElementById('name_label');
  const emailLabel = document.getElementById('email_label');
  const submitBtn = document.getElementById('submit_btn');

  if (type === 'recruiter') {
    studentFields.style.display = 'none';
    recruiterFields.style.display = 'block';
    nameLabel.textContent = 'Recruiter Contact Name *';
    emailLabel.textContent = 'Corporate Work Email *';
    submitBtn.innerHTML = '<i class="bi bi-briefcase-fill me-1"></i>Create Corporate Recruiter Account';
  } else {
    studentFields.style.display = 'block';
    recruiterFields.style.display = 'none';
    nameLabel.textContent = 'Full Student Name *';
    emailLabel.textContent = 'Student Institutional Email *';
    submitBtn.innerHTML = '<i class="bi bi-person-check-fill me-1"></i>Create Student Account';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>