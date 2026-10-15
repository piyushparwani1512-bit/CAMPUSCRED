<?php
/**
 * CampusCred - Recruiter Shortlisted Talent Pipeline
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('recruiter');

$recruiter = current_user();
$recruiterId = (int)$recruiter['id'];

// CSV Export handler
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $csvStmt = $pdo->prepare("
        SELECT 
            u.student_id_code, u.name, u.email, u.phone, u.department, u.branch, u.academic_year,
            u.cgpa, u.skills, bm.status as pipeline_stage, bm.notes, bm.created_at as saved_date
        FROM recruiter_bookmarks bm
        JOIN users u ON u.id = bm.student_id
        WHERE bm.recruiter_id = ?
        ORDER BY bm.created_at DESC
    ");
    $csvStmt->execute([$recruiterId]);
    $rows = $csvStmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="shortlisted_candidates_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Roll ID', 'Candidate Name', 'Email', 'Phone', 'Department', 'Branch', 'Academic Year', 'CGPA', 'Skills', 'Pipeline Stage', 'Recruiter Notes', 'Saved At']);
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['student_id_code'],
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['department'],
            $row['branch'],
            $row['academic_year'],
            $row['cgpa'],
            $row['skills'],
            $row['pipeline_stage'],
            $row['notes'],
            $row['saved_date'],
        ]);
    }
    fclose($output);
    exit;
}

$stageFilter = trim($_GET['stage'] ?? '');

$sql = "
    SELECT 
        bm.id as bookmark_id, bm.status as pipeline_stage, bm.notes, bm.created_at as bookmarked_at,
        u.id as student_id, u.name, u.student_id_code, u.email, u.phone, u.department, u.branch,
        u.headline, u.skills, u.cgpa,
        COUNT(a.id) as verified_count,
        SUM(a.category = 'Technical') as tech_count
    FROM recruiter_bookmarks bm
    JOIN users u ON u.id = bm.student_id
    LEFT JOIN activities a ON a.student_id = u.id AND a.status = 'Verified'
    WHERE bm.recruiter_id = ?
";
$params = [$recruiterId];

if ($stageFilter !== '') {
    $sql .= " AND bm.status = ?";
    $params[] = $stageFilter;
}

$sql .= " GROUP BY bm.id ORDER BY bm.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shortlist = $stmt->fetchAll();

$page_title = 'Shortlisted Candidates · Recruiter Hub';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <div class="text-uppercase fw-bold text-primary small" style="font-size: 0.72rem; letter-spacing: 1px;">
      <i class="bi bi-star-fill text-warning me-1"></i>Hiring Pipeline
    </div>
    <h3 class="fw-bolder text-dark mb-1">Shortlisted Candidate Pipeline</h3>
    <p class="text-muted small mb-0">Track and manage recruitment stages for student candidates saved to your company watchlist.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="shortlisted.php?action=export_csv" class="btn btn-outline-success fw-bold btn-sm">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
    </a>
    <a href="dashboard.php" class="btn btn-primary fw-bold btn-sm">
      <i class="bi bi-plus-circle me-1"></i>Discover More Talent
    </a>
  </div>
</div>

<!-- Stage Filter Tabs -->
<div class="card shadow-sm border-0 rounded-4 p-3 bg-white mb-4">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small fw-bold me-2">Filter Stage:</span>
    <a href="shortlisted.php" class="btn btn-sm <?= $stageFilter === '' ? 'btn-primary' : 'btn-light border' ?>">All (<?= count($shortlist) ?>)</a>
    <a href="shortlisted.php?stage=Saved" class="btn btn-sm <?= $stageFilter === 'Saved' ? 'btn-primary' : 'btn-light border' ?>">⭐ Saved</a>
    <a href="shortlisted.php?stage=Screening" class="btn btn-sm <?= $stageFilter === 'Screening' ? 'btn-primary' : 'btn-light border' ?>">🔍 Screening</a>
    <a href="shortlisted.php?stage=Interviewing" class="btn btn-sm <?= $stageFilter === 'Interviewing' ? 'btn-primary' : 'btn-light border' ?>">💬 Interviewing</a>
    <a href="shortlisted.php?stage=Offered" class="btn btn-sm <?= $stageFilter === 'Offered' ? 'btn-primary' : 'btn-light border' ?>">🎉 Offered</a>
    <a href="shortlisted.php?stage=Archived" class="btn btn-sm <?= $stageFilter === 'Archived' ? 'btn-primary' : 'btn-light border' ?>">📁 Archived</a>
  </div>
</div>

<?php if (empty($shortlist)): ?>
  <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white">
    <div class="display-4 text-warning mb-3"><i class="bi bi-star"></i></div>
    <h5 class="fw-bold text-dark">No candidates in this pipeline stage</h5>
    <p class="text-muted small">Explore the talent directory and click the star icon or "Shortlist" button to add candidates here.</p>
    <a href="dashboard.php" class="btn btn-primary btn-sm px-4 mx-auto">Browse Candidate Directory</a>
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-secondary small text-uppercase">
          <tr>
            <th style="width: 25%;">Candidate</th>
            <th>Department</th>
            <th>CGPA</th>
            <th>Verified Records</th>
            <th>Pipeline Stage</th>
            <th>Recruiter Notes</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shortlist as $row): ?>
            <tr>
              <td>
                <div class="fw-bold text-dark fs-6">
                  <a href="candidate_profile.php?id=<?= (int)$row['student_id'] ?>" class="text-decoration-none text-dark hover-primary">
                    <?= e($row['name']) ?>
                  </a>
                </div>
                <div class="text-muted small">
                  <span class="badge bg-secondary-subtle text-secondary"><?= e($row['student_id_code']) ?></span> &middot; <?= e($row['email']) ?>
                </div>
              </td>
              <td>
                <div class="small fw-semibold"><?= e($row['department']) ?></div>
                <div class="text-muted small"><?= e($row['branch'] ? '('.$row['branch'].')' : '') ?></div>
              </td>
              <td>
                <?php if (!empty($row['cgpa'])): ?>
                  <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                    ★ <?= number_format((float)$row['cgpa'], 2) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-patch-check-fill me-1"></i><?= (int)$row['verified_count'] ?> Verified
                </span>
              </td>
              <td>
                <!-- Quick Stage Selector -->
                <form method="POST" action="action.php" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_bookmark_status">
                  <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
                  <input type="hidden" name="redirect" value="recruiter/shortlisted.php">
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="Saved" <?= $row['pipeline_stage'] === 'Saved' ? 'selected' : '' ?>>⭐ Saved</option>
                    <option value="Screening" <?= $row['pipeline_stage'] === 'Screening' ? 'selected' : '' ?>>🔍 Screening</option>
                    <option value="Interviewing" <?= $row['pipeline_stage'] === 'Interviewing' ? 'selected' : '' ?>>💬 Interviewing</option>
                    <option value="Offered" <?= $row['pipeline_stage'] === 'Offered' ? 'selected' : '' ?>>🎉 Offered</option>
                    <option value="Archived" <?= $row['pipeline_stage'] === 'Archived' ? 'selected' : '' ?>>📁 Archived</option>
                  </select>
                </form>
              </td>
              <td>
                <div class="text-truncate small text-secondary" style="max-width: 220px;" title="<?= e($row['notes']) ?>">
                  <?= e($row['notes'] ?: 'No notes added yet.') ?>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <a href="candidate_profile.php?id=<?= (int)$row['student_id'] ?>" class="btn btn-sm btn-primary" title="View Full Dossier">
                    <i class="bi bi-file-earmark-person"></i> View
                  </a>
                  <form method="POST" action="action.php" class="d-inline" onsubmit="return confirm('Remove candidate from your shortlist?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_bookmark_status">
                    <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
                    <input type="hidden" name="delete_bookmark" value="1">
                    <input type="hidden" name="redirect" value="recruiter/shortlisted.php">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
