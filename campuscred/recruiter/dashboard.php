<?php
/**
 * CampusCred - Recruiter Talent Acquisition Dashboard
 * Allows verified employers to discover, evaluate, and recruit collegiate achievers
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('recruiter');

$recruiter = current_user();
$recruiterId = (int)$recruiter['id'];

// Get recruiter KPI stats
$totalCandidates = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$verifiedCredentials = (int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status = 'Verified'")->fetchColumn();
$techAchievers = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM activities WHERE status = 'Verified' AND category = 'Technical'")->fetchColumn();
$myBookmarks = (int)$pdo->query("SELECT COUNT(*) FROM recruiter_bookmarks WHERE recruiter_id = {$recruiterId}")->fetchColumn();
$myInquiries = (int)$pdo->query("SELECT COUNT(*) FROM recruitment_inquiries WHERE recruiter_id = {$recruiterId}")->fetchColumn();

// Search and Filter parameters
$search = trim($_GET['q'] ?? '');
$deptFilter = trim($_GET['dept'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');
$minVerified = (int)($_GET['min_verified'] ?? 0);

$sql = "
    SELECT 
        u.id, u.name, u.student_id_code, u.department, u.branch, u.academic_year,
        u.headline, u.skills, u.cgpa, u.is_open_to_work, u.phone, u.email,
        COUNT(a.id) as verified_count,
        SUM(a.category = 'Technical') as tech_count,
        SUM(a.category = 'Academic') as academic_count,
        SUM(a.category = 'Cultural') as cultural_count,
        SUM(a.category = 'Sports') as sports_count,
        SUM(a.participation_type = 'Winner' OR a.participation_type = '1st Place') as winner_count,
        bm.status as bookmark_status
    FROM users u
    LEFT JOIN activities a ON a.student_id = u.id AND a.status = 'Verified'
    LEFT JOIN recruiter_bookmarks bm ON bm.student_id = u.id AND bm.recruiter_id = ?
    WHERE u.role = 'student'
";
$params = [$recruiterId];

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

$sql .= " GROUP BY u.id";

if ($minVerified > 0) {
    $sql .= " HAVING verified_count >= " . $minVerified;
}

$sql .= " ORDER BY verified_count DESC, winner_count DESC, u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

$departments = [
    'Computer Science & Engineering',
    'Information Technology',
    'Electronics & Communication',
    'Electrical Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Management Studies',
];

$page_title = 'Recruiter Dashboard · ' . e($recruiter['company_name'] ?: 'Talent Hub');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <div class="text-uppercase fw-bold text-primary small" style="font-size: 0.72rem; letter-spacing: 1px;">
      <i class="bi bi-briefcase-fill me-1"></i><?= e($recruiter['company_name'] ?: 'Corporate Recruitment Center') ?>
    </div>
    <h3 class="fw-bolder text-dark mb-1">Collegiate Talent Discovery &amp; Verification Hub</h3>
    <p class="text-muted small mb-0">Browse authenticated candidate portfolios, evaluate verified certificate proofs, and manage hiring pipelines.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('recruiter/shortlisted.php')) ?>" class="btn btn-warning text-dark fw-bold shadow-sm">
      <i class="bi bi-star-fill me-1"></i>Saved Candidates (<?= $myBookmarks ?>)
    </a>
    <a href="<?= e(base_url('recruiter/inquiries.php')) ?>" class="btn btn-outline-primary fw-bold">
      <i class="bi bi-envelope-paper me-1"></i>Inquiries Sent (<?= $myInquiries ?>)
    </a>
    <a href="<?= e(base_url('verify_credential.php')) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-qr-code me-1"></i>Verify Code
    </a>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-3">
          <i class="bi bi-people-fill"></i>
        </div>
        <div>
          <div class="display-6 fw-bold text-dark mb-0"><?= $totalCandidates ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Total Talent Pool</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 bg-success-subtle text-success rounded-3 fs-3">
          <i class="bi bi-patch-check-fill"></i>
        </div>
        <div>
          <div class="display-6 fw-bold text-dark mb-0"><?= $verifiedCredentials ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Verified Credentials</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 bg-info-subtle text-info rounded-3 fs-3">
          <i class="bi bi-code-slash"></i>
        </div>
        <div>
          <div class="display-6 fw-bold text-dark mb-0"><?= $techAchievers ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Tech / Hackathon Achievers</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 fs-3">
          <i class="bi bi-star-fill"></i>
        </div>
        <div>
          <div class="display-6 fw-bold text-dark mb-0"><?= $myBookmarks ?></div>
          <div class="text-muted small fw-semibold text-uppercase">Shortlisted Candidates</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Search & Filtering Bar -->
<div class="card shadow-sm border-0 rounded-4 p-4 mb-4 bg-white">
  <form method="GET" action="dashboard.php" class="row g-3 align-items-end">
    
    <div class="col-md-4">
      <label class="form-label fw-bold small text-secondary">
        <i class="bi bi-search me-1"></i>Candidate Name, Skills, or Roll Number
      </label>
      <input type="text" name="q" class="form-control" placeholder="Search Python, Hackathon, ICPC, Name..." value="<?= e($search) ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label fw-bold small text-secondary">
        <i class="bi bi-diagram-3 me-1"></i>Academic Department
      </label>
      <select name="dept" class="form-select">
        <option value="">All Academic Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= e($d) ?>" <?= $deptFilter === $d ? 'selected' : '' ?>><?= e($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label fw-bold small text-secondary">
        <i class="bi bi-shield-check me-1"></i>Minimum Verified Achievements
      </label>
      <select name="min_verified" class="form-select">
        <option value="0" <?= $minVerified === 0 ? 'selected' : '' ?>>Any Verified Count</option>
        <option value="1" <?= $minVerified === 1 ? 'selected' : '' ?>>At least 1 Verified (1+)</option>
        <option value="2" <?= $minVerified === 2 ? 'selected' : '' ?>>At least 2 Verified (2+)</option>
        <option value="3" <?= $minVerified === 3 ? 'selected' : '' ?>>At least 3 Verified (3+)</option>
      </select>
    </div>

    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
        <i class="bi bi-funnel-fill me-1"></i>Filter
      </button>
      <?php if ($search !== '' || $deptFilter !== '' || $skillFilter !== '' || $minVerified > 0): ?>
        <a href="<?= e(base_url('recruiter/dashboard.php')) ?>" class="btn btn-outline-secondary" title="Reset Filters">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </div>

  </form>

  <!-- Popular Skills Quick Filter Pills -->
  <div class="d-flex flex-wrap gap-1 align-items-center mt-3 pt-3 border-top">
    <span class="text-muted small fw-bold me-2">Quick Skill Filters:</span>
    <?php 
    $popularSkills = ['Python', 'React', 'Java', 'Algorithms', 'Drone Tech', 'IoT', 'Embedded Systems', 'AWS Cloud', 'Docker'];
    foreach ($popularSkills as $ps): 
    ?>
      <a href="dashboard.php?skill=<?= urlencode($ps) ?>" class="badge rounded-pill text-decoration-none <?= $skillFilter === $ps ? 'bg-primary text-white' : 'bg-light text-dark border' ?> px-3 py-1">
        <?= e($ps) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Candidate Cards Grid -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold text-dark mb-0">
    Candidate Talent Pool <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($candidates) ?> Found</span>
  </h5>
</div>

<?php if (empty($candidates)): ?>
  <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
    <div class="display-4 text-muted mb-3"><i class="bi bi-search"></i></div>
    <h5 class="fw-bold text-dark">No candidates match your search filters</h5>
    <p class="text-muted small">Try removing some filter conditions or searching by a broader keyword.</p>
    <a href="<?= e(base_url('recruiter/dashboard.php')) ?>" class="btn btn-primary btn-sm px-4 mx-auto">Reset All Filters</a>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($candidates as $cand): ?>
      <?php 
        $verifiedCount = (int)$cand['verified_count'];
        $winnerCount = (int)$cand['winner_count'];
        $skillsList = !empty($cand['skills']) ? array_slice(array_map('trim', explode(',', $cand['skills'])), 0, 5) : [];
        $isBookmarked = !empty($cand['bookmark_status']);
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="talent-card card h-100 p-4 shadow-sm position-relative">
          
          <!-- Top Row: Student Code & Bookmark Star -->
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                <?= e($cand['student_id_code']) ?>
              </span>
              <?php if (!empty($cand['academic_year'])): ?>
                <span class="badge bg-light text-muted border"><?= e($cand['academic_year']) ?></span>
              <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-1">
              <?php if (!empty($cand['cgpa'])): ?>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold" title="Cumulative GPA">
                  ★ <?= number_format((float)$cand['cgpa'], 2) ?>
                </span>
              <?php endif; ?>

              <!-- Quick Bookmark Form -->
              <form method="POST" action="action.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_bookmark">
                <input type="hidden" name="student_id" value="<?= (int)$cand['id'] ?>">
                <input type="hidden" name="redirect" value="recruiter/dashboard.php">
                <button type="submit" class="btn btn-sm <?= $isBookmarked ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>" title="<?= $isBookmarked ? 'Bookmarked (' . e($cand['bookmark_status']) . ')' : 'Add to Shortlist' ?>" style="padding: 2px 7px;">
                  <i class="bi <?= $isBookmarked ? 'bi-star-fill' : 'bi-star' ?>"></i>
                </button>
              </form>
            </div>
          </div>

          <h5 class="fw-bolder text-dark mb-1">
            <a href="candidate_profile.php?id=<?= (int)$cand['id'] ?>" class="text-decoration-none text-dark hover-primary">
              <?= e($cand['name']) ?>
            </a>
          </h5>

          <div class="text-secondary small fw-semibold mb-2">
            <?= e($cand['department']) ?> <?= e($cand['branch'] ? '('.$cand['branch'].')' : '') ?>
          </div>

          <?php if (!empty($cand['headline'])): ?>
            <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.35;">
              <?= e(mb_strimwidth($cand['headline'], 0, 85, '...')) ?>
            </p>
          <?php else: ?>
            <div class="mb-3 flex-grow-1"></div>
          <?php endif; ?>

          <!-- Skills tags -->
          <div class="mb-3">
            <?php if ($skillsList): ?>
              <?php foreach ($skillsList as $sk): ?>
                <span class="skill-tag"><?= e($sk) ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Verified Achievement Badges -->
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

          <!-- Action Buttons -->
          <div class="d-flex gap-2">
            <a href="candidate_profile.php?id=<?= (int)$cand['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
              <i class="bi bi-file-earmark-person me-1"></i>View Profile &amp; Proofs
            </a>
            <a href="candidate_profile.php?id=<?= (int)$cand['id'] ?>#inquirySection" class="btn btn-outline-warning text-dark btn-sm fw-bold" title="Send Offer / Interview Invite">
              <i class="bi bi-envelope-paper-fill"></i>
            </a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
