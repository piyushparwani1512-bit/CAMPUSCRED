<?php
/**
 * CampusCred - Public Talent Showcase & Recruitment Discovery Directory
 * Allows companies and recruiters to discover verified student achievements
 */
require_once __DIR__ . '/includes/bootstrap.php';

$search = trim($_GET['q'] ?? '');
$deptFilter = trim($_GET['dept'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');
$catFilter = trim($_GET['cat'] ?? '');

$sql = "
    SELECT 
        u.id, u.name, u.student_id_code, u.department, u.branch, u.academic_year,
        u.headline, u.skills, u.cgpa, u.is_open_to_work,
        COUNT(a.id) as verified_count,
        SUM(a.category = 'Technical') as tech_count,
        SUM(a.category = 'Academic') as academic_count,
        SUM(a.category = 'Cultural') as cultural_count,
        SUM(a.category = 'Sports') as sports_count,
        SUM(a.participation_type = 'Winner' OR a.participation_type = '1st Place') as winner_count
    FROM users u
    LEFT JOIN activities a ON a.student_id = u.id AND a.status = 'Verified'
    WHERE u.role = 'student'
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.student_id_code LIKE ? OR u.skills LIKE ? OR u.headline LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($deptFilter !== '') {
    $sql .= " AND u.department = ?";
    $params[] = $deptFilter;
}

if ($skillFilter !== '') {
    $sql .= " AND u.skills LIKE ?";
    $params[] = "%{$skillFilter}%";
}

$sql .= " GROUP BY u.id ORDER BY verified_count DESC, u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Available departments for filter
$departments = [
    'Computer Science & Engineering',
    'Information Technology',
    'Electronics & Communication',
    'Electrical Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Management Studies',
];

$page_title = 'Verified Talent Showcase · ' . COLLEGE_SHORT_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

  <!-- Hero Header -->
  <div class="card border-0 rounded-4 shadow-sm text-white p-4 p-md-5 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%);">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge bg-warning text-dark px-3 py-1 fw-bold text-uppercase mb-2">
          <i class="bi bi-briefcase-fill me-1"></i>Verified Collegiate Talent Directory
        </span>
        <h2 class="fw-bolder mb-2 text-white">Discover &amp; Recruit Exceptional Student Achievers</h2>
        <p class="lead opacity-90 mb-3 fs-6">
          Access authenticated profiles of university winners, hackathon finalists, researchers, and collegiate leaders. All achievements and certificates are cryptographically verified by institutional faculty.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <?php if (!is_recruiter()): ?>
            <a href="<?= e(base_url('register.php?portal=recruiter')) ?>" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
              <i class="bi bi-building-add me-1"></i>Register as Recruiter
            </a>
            <a href="<?= e(base_url('login.php?portal=recruiter')) ?>" class="btn btn-outline-light px-3">
              <i class="bi bi-box-arrow-in-right me-1"></i>Recruiter Login
            </a>
          <?php else: ?>
            <a href="<?= e(base_url('recruiter/dashboard.php')) ?>" class="btn btn-warning text-dark fw-bold px-4">
              <i class="bi bi-speedometer2 me-1"></i>My Recruiter Hub
            </a>
            <a href="<?= e(base_url('recruiter/shortlisted.php')) ?>" class="btn btn-outline-light px-3">
              <i class="bi bi-star-fill me-1 text-warning"></i>Shortlisted Candidates
            </a>
          <?php endif; ?>
          <a href="<?= e(base_url('verify_credential.php')) ?>" class="btn btn-outline-light px-3">
            <i class="bi bi-qr-code me-1"></i>Instant Credential Verifier
          </a>
        </div>
      </div>
      <div class="col-lg-4 text-center d-none d-lg-block">
        <div class="display-1 text-warning opacity-75">
          <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="fw-bold fs-5 text-white"><?= count($candidates) ?> Verified Candidates</div>
        <div class="text-light opacity-75 small">Ready for Campus Hiring &amp; Internships</div>
      </div>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="card shadow-sm border-0 rounded-4 p-4 mb-4 bg-white">
    <form method="GET" action="talents.php" class="row g-3 align-items-end">
      
      <div class="col-md-5">
        <label class="form-label fw-bold small text-secondary">
          <i class="bi bi-search me-1"></i>Search Candidates or Skills
        </label>
        <input type="text" name="q" class="form-control" placeholder="Search by name, skill (e.g. Python, IoT), or Roll ID..." value="<?= e($search) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label fw-bold small text-secondary">
          <i class="bi bi-diagram-3 me-1"></i>Department / Stream
        </label>
        <select name="dept" class="form-select">
          <option value="">All Departments</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= e($d) ?>" <?= $deptFilter === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
          <i class="bi bi-funnel-fill me-1"></i>Filter
        </button>
        <?php if ($search !== '' || $deptFilter !== '' || $skillFilter !== ''): ?>
          <a href="<?= e(base_url('talents.php')) ?>" class="btn btn-outline-secondary" title="Reset Filters">
            <i class="bi bi-x-lg"></i>
          </a>
        <?php endif; ?>
      </div>

    </form>

    <!-- Popular Skill Quick Filter Pills -->
    <div class="d-flex flex-wrap gap-1 align-items-center mt-3 pt-3 border-top">
      <span class="text-muted small fw-bold me-2">Trending Skills:</span>
      <?php 
      $popularSkills = ['Python', 'React', 'Java', 'Algorithms', 'Drone Tech', 'IoT', 'Embedded Systems', 'AWS Cloud', 'Docker'];
      foreach ($popularSkills as $ps): 
      ?>
        <a href="talents.php?skill=<?= urlencode($ps) ?>" class="badge rounded-pill text-decoration-none <?= $skillFilter === $ps ? 'bg-primary text-white' : 'bg-light text-dark border' ?> px-3 py-1">
          <?= e($ps) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Candidates Directory Grid -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-dark mb-0">
      Available Candidates <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($candidates) ?> Found</span>
    </h5>
  </div>

  <?php if (empty($candidates)): ?>
    <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
      <div class="display-4 text-muted mb-3"><i class="bi bi-search"></i></div>
      <h5 class="fw-bold text-dark">No matching candidates found</h5>
      <p class="text-muted small">Try broadening your search criteria or resetting filters.</p>
      <a href="<?= e(base_url('talents.php')) ?>" class="btn btn-primary btn-sm px-4 mx-auto">Reset All Filters</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($candidates as $cand): ?>
        <?php 
          $verifiedCount = (int)$cand['verified_count'];
          $winnerCount = (int)$cand['winner_count'];
          $skillsList = !empty($cand['skills']) ? array_slice(array_map('trim', explode(',', $cand['skills'])), 0, 5) : [];
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="talent-card card h-100 p-4 shadow-sm">
            
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                  <?= e($cand['student_id_code']) ?>
                </span>
                <?php if (!empty($cand['academic_year'])): ?>
                  <span class="badge bg-light text-muted border"><?= e($cand['academic_year']) ?></span>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($cand['cgpa'])): ?>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold" title="Cumulative GPA">
                  ★ <?= number_format((float)$cand['cgpa'], 2) ?>
                </span>
              <?php endif; ?>
            </div>

            <h5 class="fw-bolder text-dark mb-1">
              <a href="<?= e(base_url('public_portfolio.php?id=' . (int)$cand['id'])) ?>" class="text-decoration-none text-dark hover-primary">
                <?= e($cand['name']) ?>
              </a>
            </h5>

            <div class="text-secondary small fw-semibold mb-2">
              <?= e($cand['department']) ?>
            </div>

            <?php if (!empty($cand['headline'])): ?>
              <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.35;">
                <?= e(mb_strimwidth($cand['headline'], 0, 85, '...')) ?>
              </p>
            <?php else: ?>
              <div class="mb-3 flex-grow-1"></div>
            <?php endif; ?>

            <!-- Skills Pills -->
            <div class="mb-3">
              <?php if ($skillsList): ?>
                <?php foreach ($skillsList as $sk): ?>
                  <span class="skill-tag"><?= e($sk) ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Achievement Counters -->
            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 border mb-3 small">
              <div>
                <i class="bi bi-patch-check-fill text-success me-1"></i>
                <strong><?= $verifiedCount ?></strong> Verified
              </div>
              <?php if ($winnerCount > 0): ?>
                <div class="text-warning-emphasis fw-bold">
                  <i class="bi bi-trophy-fill text-warning me-1"></i>
                  <?= $winnerCount ?> 1st Place
                </div>
              <?php endif; ?>
              <div>
                <i class="bi bi-code-slash text-primary me-1"></i>
                <?= (int)$cand['tech_count'] ?> Tech
              </div>
            </div>

            <!-- Card Action Footer -->
            <div class="d-flex gap-2">
              <a href="<?= e(base_url('public_portfolio.php?id=' . (int)$cand['id'])) ?>" class="btn btn-outline-primary btn-sm flex-grow-1 fw-bold">
                <i class="bi bi-file-earmark-person me-1"></i>View Portfolio
              </a>
              <?php if (is_recruiter()): ?>
                <a href="<?= e(base_url('recruiter/candidate_profile.php?id=' . (int)$cand['id'])) ?>" class="btn btn-warning btn-sm text-dark fw-bold" title="Open Hiring Dossier">
                  <i class="bi bi-briefcase-fill"></i>
                </a>
              <?php else: ?>
                <a href="<?= e(base_url('public_portfolio.php?id=' . (int)$cand['id'])) ?>#inquiryModal" class="btn btn-light btn-sm border" title="Contact Candidate">
                  <i class="bi bi-envelope"></i>
                </a>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
