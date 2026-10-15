<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = '403 Forbidden · Access Denied';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center" style="max-width: 600px;">
    <div class="card shadow-sm border-danger">
        <div class="card-body p-5">
            <div class="display-3 text-danger mb-3"><i class="bi bi-shield-lock-fill"></i></div>
            <h2 class="fw-bold text-danger">403 — Access Denied</h2>
            <p class="text-muted mt-2">You do not have the required permissions or role to view this administrative resource.</p>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary px-4"><i class="bi bi-house me-1"></i>Return to Dashboard</a>
                <a href="<?= e(base_url('logout.php')) ?>" class="btn btn-outline-secondary px-4"><i class="bi bi-box-arrow-right me-1"></i>Log Out</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
