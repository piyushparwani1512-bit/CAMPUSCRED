<?php
/**
 * CampusCred - Public Credential Verification Portal
 * Tamper-Evident QR Code Verification & Institutional Registry Lookup
 */
require_once __DIR__ . '/includes/bootstrap.php';

$code = trim($_GET['code'] ?? $_GET['id'] ?? $_GET['uuid'] ?? '');

$record = null;
$isValid = false;
$statusMessage = '';

if ($code !== '') {
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.submission_code, a.event_name, a.category, a.event_date, a.organizer,
            a.participation_type, a.status, a.reviewed_at, a.verification_uuid, a.certificate_hash,
            a.certificate_path, a.certificate_original_name, a.achievement_description,
            u.id AS student_id, u.name AS student_name, u.student_id_code, u.department, u.branch,
            u.headline, u.skills, rev.name AS reviewer_name
        FROM activities a
        JOIN users u ON a.student_id = u.id
        LEFT JOIN users rev ON a.reviewer_id = rev.id
        WHERE a.verification_uuid = :code OR a.submission_code = :code2
        LIMIT 1
    ");
    $stmt->execute([':code' => $code, ':code2' => $code]);
    $record = $stmt->fetch();

    if ($record) {
        if ($record['status'] === 'Verified') {
            $isValid = true;
        } else {
            $statusMessage = "This activity record is currently in '" . e($record['status']) . "' status and is NOT certified as a verified institutional credential.";
        }
    } else {
        $statusMessage = "No achievement or credential found matching code: '" . e($code) . "'. The code may be invalid or forged.";
    }
}

$page_title = 'Credential Verification Portal · ' . COLLEGE_SHORT_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 900px;">

  <!-- Header Banner -->
  <div class="text-center mb-4">
    <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-bold text-uppercase">
      <i class="bi bi-shield-check me-1"></i>Official Verification Registry
    </div>
    <h3 class="fw-bolder text-dark mb-1"><?= e(COLLEGE_NAME) ?></h3>
    <p class="text-muted small mb-0">Cryptographic Validation of Collegiate Participation &amp; Extracurricular Achievements</p>
  </div>

  <?php if (empty($code)): ?>
    <!-- Verification Code Search Form -->
    <div class="card shadow-sm border-0 rounded-4 text-center p-4 p-md-5 bg-white mb-4">
      <div class="card-body">
        <div class="display-3 text-primary mb-3">
          <i class="bi bi-qr-code-scan"></i>
        </div>
        <h4 class="fw-bold text-dark">Verify Student Achievement Credential</h4>
        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
          Enter the unique <strong>Verification UUID</strong> or <strong>Submission Code</strong> printed on the student's certificate or official achievement transcript.
        </p>

        <form method="GET" action="verify_credential.php" class="mx-auto" style="max-width: 540px;">
          <div class="input-group input-group-lg shadow-sm mb-3">
            <input type="text" name="code" class="form-control" placeholder="e.g. ver-c9b8f2a1-... or SUB-2026-CSE001" required autofocus>
            <button class="btn btn-primary fw-bold px-4" type="submit">
              <i class="bi bi-search me-1"></i>Verify Now
            </button>
          </div>
        </form>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4 text-muted small">
          <span class="badge bg-light text-dark border">
            <i class="bi bi-check-circle-fill text-success me-1"></i>Tamper-Evident SHA-256 Hashes
          </span>
          <span class="badge bg-light text-dark border">
            <i class="bi bi-building-check text-primary me-1"></i>Institutional Officer Verified
          </span>
          <span class="badge bg-light text-dark border">
            <i class="bi bi-briefcase-fill text-warning me-1"></i>Recruiter Talent Registry
          </span>
        </div>
      </div>
    </div>

  <?php elseif ($isValid && $record): ?>
    <!-- Verified Credential Result Card -->
    <div class="card shadow-lg border-success border-2 rounded-4 overflow-hidden mb-4 bg-white">
      
      <!-- Verified Badge Header -->
      <div class="bg-success text-white px-4 py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-patch-check-fill fs-3"></i>
          <div>
            <div class="fw-bolder fs-5 text-uppercase" style="letter-spacing: 0.5px;">Authentic Verified Credential</div>
            <div class="small opacity-90">Validated against <?= e(COLLEGE_SHORT_NAME) ?> Official Registry</div>
          </div>
        </div>
        <div class="text-sm-end">
          <span class="badge bg-white text-success fw-bold px-3 py-2 fs-6">STATUS: VERIFIED</span>
        </div>
      </div>

      <div class="card-body p-4 p-md-5">
        
        <!-- Student Info Row -->
        <div class="row align-items-center bg-light p-3 p-md-4 rounded-3 border mb-4">
          <div class="col-md-8">
            <div class="text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.72rem; letter-spacing: 0.8px;">Credential Holder (Candidate)</div>
            <h4 class="fw-bolder text-primary mb-1"><?= e($record['student_name']) ?></h4>
            <div class="text-dark small mb-1">
              <strong>College ID:</strong> <span class="badge bg-secondary-subtle text-dark"><?= e($record['student_id_code']) ?></span> &middot; 
              <strong>Department:</strong> <?= e($record['department']) ?> <?= e($record['branch'] ? '('.$record['branch'].')' : '') ?>
            </div>
            <?php if (!empty($record['headline'])): ?>
              <div class="text-muted small mt-1"><em><?= e($record['headline']) ?></em></div>
            <?php endif; ?>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div id="qrcodeVerification" class="d-inline-block bg-white p-2 border rounded-3 shadow-sm"></div>
          </div>
        </div>

        <!-- Achievement Details -->
        <div class="mb-4">
          <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-trophy-fill text-warning me-2"></i>Achievement &amp; Participation Details
          </h5>

          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
              <tbody>
                <tr>
                  <th class="bg-light text-secondary" style="width: 30%;">Event / Activity Name</th>
                  <td class="fw-bold text-dark fs-5"><?= e($record['event_name']) ?></td>
                </tr>
                <tr>
                  <th class="bg-light text-secondary">Participation Category</th>
                  <td><?= category_badge($record['category']) ?></td>
                </tr>
                <tr>
                  <th class="bg-light text-secondary">Award / Standing</th>
                  <td>
                    <span class="badge bg-primary px-3 py-1 fw-bold fs-6">
                      <i class="bi bi-award-fill me-1"></i><?= e($record['participation_type']) ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-secondary">Organizing Body</th>
                  <td class="fw-semibold text-dark"><?= e($record['organizer']) ?></td>
                </tr>
                <tr>
                  <th class="bg-light text-secondary">Event Date</th>
                  <td><?= format_display_date($record['event_date']) ?></td>
                </tr>
                <?php if (!empty($record['achievement_description'])): ?>
                  <tr>
                    <th class="bg-light text-secondary">Achievement Scope / Description</th>
                    <td><?= nl2br(e($record['achievement_description'])) ?></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Cryptographic Proof Box -->
        <div class="card bg-dark text-light border-0 rounded-3 mb-4">
          <div class="card-body p-3 p-md-4 font-monospace small">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-warning fw-bold"><i class="bi bi-shield-lock-fill me-1"></i>Cryptographic Verification Record</span>
              <span class="badge bg-secondary"><?= e(PROJECT_CODE) ?></span>
            </div>
            <div class="text-truncate mb-1">
              <span class="text-muted">Verification UUID:</span> <code><?= e($record['verification_uuid']) ?></code>
            </div>
            <div class="text-truncate mb-1">
              <span class="text-muted">Submission Code:</span> <code><?= e($record['submission_code']) ?></code>
            </div>
            <?php if (!empty($record['certificate_hash'])): ?>
              <div class="text-truncate mb-1">
                <span class="text-muted">SHA-256 Certificate Hash:</span> <code><?= e($record['certificate_hash']) ?></code>
              </div>
            <?php endif; ?>
            <div class="mt-2 text-muted" style="font-size: 0.8rem;">
              <i class="bi bi-person-check me-1"></i>Verified by: <strong class="text-light"><?= e($record['reviewer_name'] ?: 'Institutional Verification Committee') ?></strong> on <?= format_display_datetime($record['reviewed_at']) ?>
            </div>
          </div>
        </div>

        <!-- Action Buttons for Recruiters and Visitors -->
        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
          <a href="<?= e(base_url('certificate.php?uuid=' . urlencode($record['verification_uuid']))) ?>" class="btn btn-success fw-bold px-4 py-2" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>View Official Certificate Proof
          </a>

          <a href="<?= e(base_url('public_portfolio.php?id=' . (int)$record['student_id'])) ?>" class="btn btn-outline-primary fw-bold px-4 py-2">
            <i class="bi bi-mortarboard-fill me-1"></i>View Candidate's Full Verified Dossier
          </a>

          <?php if (is_recruiter()): ?>
            <a href="<?= e(base_url('recruiter/candidate_profile.php?id=' . (int)$record['student_id'])) ?>" class="btn btn-warning fw-bold px-4 py-2 text-dark">
              <i class="bi bi-briefcase-fill me-1"></i>Recruit / Shortlist Candidate
            </a>
          <?php else: ?>
            <a href="<?= e(base_url('register.php?portal=recruiter')) ?>" class="btn btn-outline-secondary fw-bold px-3 py-2">
              <i class="bi bi-building me-1"></i>Recruiter Access
            </a>
          <?php endif; ?>

          <a href="<?= e(base_url('verify_credential.php')) ?>" class="btn btn-light border px-3 py-2">
            <i class="bi bi-search me-1"></i>Verify Another
          </a>
        </div>

      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (typeof QRCode !== 'undefined') {
          new QRCode(document.getElementById("qrcodeVerification"), {
            text: "<?= full_url('verify_credential.php?code=' . urlencode($record['verification_uuid'])) ?>",
            width: 90,
            height: 90,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
          });
        }
      });
    </script>

  <?php else: ?>
    <!-- Invalid / Not Verified Warning Card -->
    <div class="card shadow-sm border-danger border-2 rounded-4 p-4 p-md-5 text-center bg-white mb-4">
      <div class="card-body">
        <div class="display-3 text-danger mb-3">
          <i class="bi bi-exclamation-octagon-fill"></i>
        </div>
        <h4 class="fw-bold text-danger">Verification Failed</h4>
        <div class="alert alert-danger mx-auto text-start my-4" style="max-width: 600px;">
          <i class="bi bi-info-circle-fill me-1"></i><?= e($statusMessage) ?>
        </div>
        <div class="text-muted small mb-4">
          Searched code: <code><?= e($code) ?></code>
        </div>
        <a href="<?= e(base_url('verify_credential.php')) ?>" class="btn btn-primary px-4 fw-bold">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Try Another Verification Code
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
