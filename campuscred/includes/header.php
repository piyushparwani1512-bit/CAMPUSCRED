<?php
$page_title = $page_title ?? APP_NAME;
$user = current_user();
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>

  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom Institutional Theme CSS -->
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">

  <!-- Chart.js for analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <!-- jsPDF & plugins -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  
  <!-- Standalone QRCode generator -->
  <script src="<?= e(base_url('assets/js/qrcode.min.js')) ?>"></script>
  <!-- Application Script -->
  <script defer src="<?= e(base_url('assets/js/script.js')) ?>"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-institutional sticky-top py-2">
  <div class="container-fluid px-lg-4">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="<?= e(base_url('index.php')) ?>">
      <div class="brand-emblem">CC</div>
      <div class="brand-title-wrap">
        <div class="brand-college-name"><?= e(COLLEGE_SHORT_NAME) ?> · <?= e(PROJECT_CODE) ?></div>
        <div class="brand-app-name"><?= e(APP_NAME) ?></div>
      </div>
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <i class="bi bi-list fs-2 text-primary"></i>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <?php if (is_logged_in()): ?>
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
          <?php if (($user['role'] ?? '') === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'admin/dashboard.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/dashboard.php')) ?>">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'admin/verify.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/verify.php')) ?>">
                <i class="bi bi-patch-check me-1"></i>Verification Panel
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'admin/student_records.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/student_records.php')) ?>">
                <i class="bi bi-people me-1"></i>Student Profiles
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'admin/reports.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/reports.php')) ?>">
                <i class="bi bi-bar-chart-line me-1"></i>Analytics
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'talents.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('talents.php')) ?>">
                <i class="bi bi-briefcase me-1"></i>Talent Showcase
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'admin/audit_logs.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/audit_logs.php')) ?>">
                <i class="bi bi-journal-text me-1"></i>Audit Trail
              </a>
            </li>
          <?php elseif (($user['role'] ?? '') === 'recruiter'): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'recruiter/dashboard.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('recruiter/dashboard.php')) ?>">
                <i class="bi bi-briefcase-fill me-1"></i>Talent Hub
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'talents.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('talents.php')) ?>">
                <i class="bi bi-search me-1"></i>Browse Candidates
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'recruiter/shortlisted.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('recruiter/shortlisted.php')) ?>">
                <i class="bi bi-star-fill text-warning me-1"></i>Shortlisted
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'recruiter/inquiries.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('recruiter/inquiries.php')) ?>">
                <i class="bi bi-envelope-paper me-1"></i>Offers &amp; Inquiries
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'verify_credential.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('verify_credential.php')) ?>">
                <i class="bi bi-shield-check me-1"></i>Verify Credential
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/dashboard.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/dashboard.php')) ?>">
                <i class="bi bi-grid-1x2 me-1"></i>Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/submit_activity.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/submit_activity.php')) ?>">
                <i class="bi bi-cloud-arrow-up me-1"></i>Submit Achievement
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/my_activities.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/my_activities.php')) ?>">
                <i class="bi bi-clock-history me-1"></i>My Activities
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/portfolio.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/portfolio.php')) ?>">
                <i class="bi bi-mortarboard me-1"></i>Verified Portfolio
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/profile.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/profile.php')) ?>">
                <i class="bi bi-sliders me-1"></i>Career Profile
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= strpos($currentScript, 'student/inquiries.php') !== false ? 'active' : '' ?>" href="<?= e(base_url('student/inquiries.php')) ?>">
                <i class="bi bi-envelope-paper-fill me-1"></i>Company Offers
              </a>
            </li>
          <?php endif; ?>
        </ul>

        <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
          <div class="text-end d-none d-md-block me-1">
            <div class="fw-bold text-white small mb-0"><?= e($user['name'] ?? 'User') ?></div>
            <div class="text-white-50" style="font-size: 0.72rem;">
              <?php if (($user['role'] ?? '') === 'admin'): ?>
                <span class="badge bg-danger px-2 py-0.5">ADMIN</span>
              <?php elseif (($user['role'] ?? '') === 'recruiter'): ?>
                <span class="badge bg-warning text-dark px-2 py-0.5 fw-bold">RECRUITER</span>
                <?= e($user['company_name'] ?: 'Corporate Partner') ?>
              <?php else: ?>
                <span class="badge bg-primary px-2 py-0.5">STUDENT</span>
                <?= e($user['student_id_code'] ?? $user['department'] ?? '') ?>
              <?php endif; ?>
            </div>
          </div>

          <?php if (($user['role'] ?? '') === 'student'): ?>
            <a href="<?= e(base_url('student/profile.php')) ?>" class="btn btn-sm btn-outline-light" title="Profile &amp; Career Settings">
              <i class="bi bi-person-gear me-1"></i><span class="d-none d-lg-inline">Profile</span>
            </a>
          <?php elseif (($user['role'] ?? '') === 'recruiter'): ?>
            <a href="<?= e(base_url('recruiter/shortlisted.php')) ?>" class="btn btn-sm btn-outline-light" title="Shortlisted Candidates">
              <i class="bi bi-star-fill text-warning me-1"></i><span class="d-none d-lg-inline">Pipeline</span>
            </a>
          <?php else: ?>
            <a href="<?= e(base_url('admin/reports.php')) ?>" class="btn btn-sm btn-outline-light" title="Institutional Reports">
              <i class="bi bi-bar-chart-line me-1"></i><span class="d-none d-lg-inline">Reports</span>
            </a>
          <?php endif; ?>

          <a href="<?= e(base_url('setup.php')) ?>" class="btn btn-sm btn-outline-light" title="System Setup &amp; DB Status">
            <i class="bi bi-gear-fill"></i>
          </a>
          
          <a class="btn btn-sm btn-outline-danger text-white border-danger-subtle bg-danger-subtle d-flex align-items-center gap-1" href="<?= e(base_url('logout.php')) ?>">
            <i class="bi bi-box-arrow-right"></i>
            <span class="d-none d-sm-inline">Log out</span>
          </a>
        </div>
      <?php else: ?>
        <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
          <a href="<?= e(base_url('talents.php')) ?>" class="btn btn-sm btn-outline-light">
            <i class="bi bi-people-fill me-1"></i>Verified Talents
          </a>
          <a href="<?= e(base_url('verify_credential.php')) ?>" class="btn btn-sm btn-outline-light">
            <i class="bi bi-qr-code-scan me-1"></i>Verify Credential
          </a>
          <a href="<?= e(base_url('setup.php')) ?>" class="btn btn-sm btn-outline-light">
            <i class="bi bi-database me-1"></i>Setup / DB
          </a>
          <a href="<?= e(base_url('login.php')) ?>" class="btn btn-sm btn-warning text-dark fw-bold">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container-fluid px-lg-4 py-4 flex-grow-1">

  <?php if ($flash = get_flash_data()): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
      <?php if ($flash['type'] === 'success'): ?>
        <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
      <?php elseif ($flash['type'] === 'error' || $flash['type'] === 'danger'): ?>
        <i class="bi bi-exclamation-octagon-fill me-2 fs-5 align-middle"></i>
      <?php else: ?>
        <i class="bi bi-info-circle-fill me-2 fs-5 align-middle"></i>
      <?php endif; ?>
      <span class="align-middle"><?= e($flash['message']) ?></span>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>