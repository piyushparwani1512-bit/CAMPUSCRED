<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$user = current_user();
$studentId = (int)$user['id'];

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$studentId]);
$student = $userStmt->fetch() ?: $user;

// Fetch ONLY Verified achievements
$verifiedStmt = $pdo->prepare("
    SELECT 
        a.*, u.name as reviewer_name
    FROM activities a
    LEFT JOIN users u ON u.id = a.reviewer_id
    WHERE a.student_id = ? AND a.status = 'Verified'
    ORDER BY a.event_date DESC
");
$verifiedStmt->execute([$studentId]);
$verifiedRecords = $verifiedStmt->fetchAll();

// Category counts
$catCountsStmt = $pdo->prepare("
    SELECT category, COUNT(*) as count 
    FROM activities 
    WHERE student_id = ? AND status = 'Verified' 
    GROUP BY category
");
$catCountsStmt->execute([$studentId]);
$catCounts = $catCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// All submissions
$allStmt = $pdo->prepare("
    SELECT a.*, u.name as reviewer_name
    FROM activities a
    LEFT JOIN users u ON u.id = a.reviewer_id
    WHERE a.student_id = ?
    ORDER BY a.created_at DESC
");
$allStmt->execute([$studentId]);
$allSubmissions = $allStmt->fetchAll();

$page_title = 'Verified Achievement Portfolio · ' . e($student['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4 no-print">
  <div>
    <h3 class="fw-bolder text-primary mb-1">
      <i class="bi bi-mortarboard-fill me-2"></i>Official Student Achievement Portfolio
    </h3>
    <p class="text-muted small mb-0">Consolidated digital record of verified extracurricular and co-curricular achievements.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="window.print()">
      <i class="bi bi-printer-fill me-1"></i>Print Transcript
    </button>
    <button type="button" class="btn btn-outline-danger fw-bold" onclick="generatePortfolioPDF()">
      <i class="bi bi-file-earmark-pdf-fill me-1"></i>Download PDF
    </button>
    <a href="<?= e(base_url('public_portfolio.php?id=' . $studentId)) ?>" target="_blank" class="btn btn-outline-primary fw-bold" title="Open Public Portfolio for Employers">
      <i class="bi bi-share-fill me-1"></i>Recruiter Link
    </a>
    <a href="<?= e(base_url('student/profile.php')) ?>" class="btn btn-outline-secondary fw-bold" title="Edit Skills and Career Headline">
      <i class="bi bi-sliders me-1"></i>Career Profile
    </a>
    <a href="<?= e(base_url('student/dashboard.php')) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<div class="card shadow-sm border rounded-4 p-4 p-md-5 bg-white mb-4" id="portfolioDocument">
  
  <div class="border-bottom pb-4 mb-4 text-center text-md-start">
    <div class="row align-items-center">
      <div class="col-md-2 text-center mb-3 mb-md-0">
        <div class="brand-emblem mx-auto" style="width: 72px; height: 72px; font-size: 1.8rem;">CC</div>
      </div>
      <div class="col-md-7">
        <h6 class="text-uppercase fw-bold text-secondary mb-1" style="letter-spacing: 1.2px; font-size: 0.8rem;">
          <?= e(COLLEGE_NAME) ?>
        </h6>
        <h4 class="fw-bolder text-primary mb-1">OFFICIAL STUDENT PARTICIPATION &amp; ACHIEVEMENT TRANSCRIPT</h4>
        <div class="text-muted small">
          Affiliated with Rajasthan Technical University &middot; Approved by AICTE &middot; Institutional Code: <?= e(PROJECT_CODE) ?>
        </div>
      </div>
      <div class="col-md-3 text-md-end text-center mt-3 mt-md-0">
        <div class="verification-seal">
          <i class="bi bi-patch-check-fill fs-5"></i>
          <div class="text-start" style="line-height: 1.1;">
            <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">Official Registry</div>
            <div style="font-size: 0.65rem;">Tamper-Evident QR</div>
          </div>
        </div>
        <div class="text-muted small mt-2">Issued: <?= date('d M Y') ?></div>
      </div>
    </div>
  </div>

  <div class="bg-light p-4 rounded-3 border mb-4">
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Student Full Name</small>
        <div class="fw-bold text-dark fs-6"><?= e($student['name']) ?></div>
      </div>
      <div class="col-6 col-md-3">
        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">College ID / Roll No</small>
        <div class="fw-bold text-primary fs-6"><?= e($student['student_id_code'] ?: 'SKIT/STU/' . $student['id']) ?></div>
      </div>
      <div class="col-6 col-md-3">
        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Department</small>
        <div class="fw-bold text-dark"><?= e($student['department']) ?></div>
      </div>
      <div class="col-6 col-md-3">
        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Branch &amp; Year</small>
        <div class="fw-bold text-dark"><?= e($student['branch'] ?: 'General') ?> &middot; <?= e($student['academic_year'] ?: 'N/A') ?></div>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
    <span class="text-secondary small fw-bold text-uppercase me-2" style="font-size: 0.75rem;">Verified Participation Summary:</span>
    <span class="badge bg-primary-subtle text-primary border px-3 py-1">
      Technical: <strong><?= (int)($catCounts['Technical'] ?? 0) ?></strong>
    </span>
    <span class="badge bg-success-subtle text-success border px-3 py-1">
      Academic: <strong><?= (int)($catCounts['Academic'] ?? 0) ?></strong>
    </span>
    <span class="badge bg-purple-subtle text-purple border px-3 py-1" style="background:#faf5ff; color:#7e22ce;">
      Cultural: <strong><?= (int)($catCounts['Cultural'] ?? 0) ?></strong>
    </span>
    <span class="badge bg-warning-subtle text-warning-emphasis border px-3 py-1">
      Sports: <strong><?= (int)($catCounts['Sports'] ?? 0) ?></strong>
    </span>
    <span class="badge bg-info-subtle text-info-emphasis border px-3 py-1">
      Extracurricular: <strong><?= (int)($catCounts['Extracurricular'] ?? 0) ?></strong>
    </span>
  </div>

  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="fw-bolder text-dark mb-0">
        <i class="bi bi-award-fill text-warning me-2"></i>Verified Records (<?= count($verifiedRecords) ?>)
      </h5>
      <span class="text-muted small">Only faculty-approved credentials appear on this official record</span>
    </div>

    <?php if (empty($verifiedRecords)): ?>
      <div class="p-4 border rounded-3 text-center bg-light">
        <i class="bi bi-info-circle display-6 text-muted"></i>
        <h6 class="fw-bold text-dark mt-2">No Verified Achievements Recorded Yet</h6>
        <p class="text-muted small mb-0">
          This student has not yet accrued verified credentials in the central database.
        </p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mb-0" id="verifiedTranscriptTable">
          <thead class="table-light">
            <tr>
              <th style="width: 5%;">#</th>
              <th style="width: 14%;">Tracking ID</th>
              <th style="width: 28%;">Event &amp; Organizer</th>
              <th style="width: 13%;">Category</th>
              <th style="width: 12%;">Role / Rank</th>
              <th style="width: 12%;">Event Date</th>
              <th style="width: 16%;" class="text-center">Verification QR</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($verifiedRecords as $idx => $rec): ?>
              <?php $verifyUrl = full_url('verify_credential.php?uuid=' . urlencode($rec['verification_uuid'])); ?>
              <tr>
                <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                <td>
                  <code class="fw-bold text-primary"><?= e($rec['submission_code']) ?></code>
                  <div class="text-muted small" style="font-size: 0.7rem;">Verified on <?= date('d M Y', strtotime($rec['reviewed_at'])) ?></div>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e($rec['event_name']) ?></div>
                  <div class="text-muted small"><?= e($rec['organizer']) ?></div>
                  <?php if (!empty($rec['achievement_description'])): ?>
                    <div class="text-secondary small fst-italic mt-1" style="font-size: 0.78rem;">
                      &ldquo;<?= e($rec['achievement_description']) ?>&rdquo;
                    </div>
                  <?php endif; ?>
                </td>
                <td><?= category_badge($rec['category']) ?></td>
                <td>
                  <span class="badge bg-success text-white fw-bold">
                    <?= e($rec['participation_type']) ?>
                  </span>
                </td>
                <td class="small text-nowrap"><?= format_display_date($rec['event_date']) ?></td>
                <td class="text-center">
                  <div class="qr-preview-box p-1 mx-auto" style="width: 90px; height: 90px;">
                    <div class="render-qrcode" data-qr-text="<?= e($verifyUrl) ?>" data-qr-size="80"></div>
                  </div>
                  <div class="mt-1" style="font-size: 0.65rem;">
                    <a href="<?= e($verifyUrl) ?>" target="_blank" class="text-decoration-none fw-bold">
                      Scan / Verify &rarr;
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="pt-4 border-top mt-5">
    <div class="row align-items-end text-center">
      <div class="col-4">
        <div class="border-bottom pb-2 mb-2 mx-auto" style="max-width: 180px;">
          <span class="text-muted small">Student Signature</span>
        </div>
        <div class="fw-bold small"><?= e($student['name']) ?></div>
      </div>
      <div class="col-4">
        <div class="border-bottom pb-2 mb-2 mx-auto" style="max-width: 180px;">
          <span class="text-muted small">Head of Department</span>
        </div>
        <div class="fw-bold small"><?= e($student['department']) ?></div>
      </div>
      <div class="col-4">
        <div class="border-bottom pb-2 mb-2 mx-auto" style="max-width: 180px;">
          <span class="text-muted small">Dean / Principal Seal</span>
        </div>
        <div class="fw-bold small"><?= e(COLLEGE_SHORT_NAME) ?> Registrar</div>
      </div>
    </div>
  </div>

</div>

<script>
function generatePortfolioPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(30, 58, 138);
  doc.text("<?= addslashes(COLLEGE_NAME) ?>", 105, 18, { align: 'center' });

  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(71, 85, 105);
  doc.text("OFFICIAL STUDENT PARTICIPATION & ACHIEVEMENT TRANSCRIPT", 105, 25, { align: 'center' });
  doc.text("Institutional Code: <?= addslashes(PROJECT_CODE) ?>  |  Verified Digital Record", 105, 30, { align: 'center' });

  doc.setDrawColor(226, 232, 240);
  doc.line(14, 34, 196, 34);

  doc.setFontSize(10);
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(15, 23, 42);
  doc.text("Student Name: <?= addslashes($student['name']) ?>", 14, 42);
  doc.text("College ID: <?= addslashes($student['student_id_code'] ?: 'SKIT/STU/' . $student['id']) ?>", 120, 42);
  doc.setFont('helvetica', 'normal');
  doc.text("Department: <?= addslashes($student['department']) ?>", 14, 48);
  doc.text("Branch & Year: <?= addslashes($student['branch'] ?: 'General') ?> (<?= addslashes($student['academic_year'] ?: '') ?>)", 120, 48);

  const rows = [];
  <?php foreach ($verifiedRecords as $idx => $r): ?>
    rows.push([
      "<?= $idx + 1 ?>",
      "<?= addslashes($r['submission_code']) ?>",
      "<?= addslashes($r['event_name']) ?>\n(<?= addslashes($r['organizer']) ?>)",
      "<?= addslashes($r['category']) ?>",
      "<?= addslashes($r['participation_type']) ?>",
      "<?= addslashes($r['event_date']) ?>",
      "Verified (QR Valid)"
    ]);
  <?php endforeach; ?>

  if (rows.length === 0) {
    doc.text("No verified achievements recorded to date.", 14, 60);
  } else {
    doc.autoTable({
      startY: 55,
      head: [['#', 'Code', 'Event & Organizer', 'Category', 'Role', 'Date', 'Status']],
      body: rows,
      theme: 'grid',
      headStyles: { fillColor: [30, 58, 138], textColor: 255, fontSize: 9 },
      bodyStyles: { fontSize: 8.5 },
      margin: { left: 14, right: 14 }
    });
  }

  const finalY = (doc.lastAutoTable && doc.lastAutoTable.finalY) ? doc.lastAutoTable.finalY + 15 : 80;
  doc.setFontSize(8);
  doc.setTextColor(100, 116, 139);
  doc.text("This official document is generated from the SKIT Centralized Student Participation Management System.", 14, finalY);
  doc.text("Tamper verification available at: <?= addslashes(full_url('verify_credential.php')) ?>", 14, finalY + 5);

  doc.save("<?= addslashes($student['student_id_code'] ?: 'Student') ?>_Verified_Achievements.pdf");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
