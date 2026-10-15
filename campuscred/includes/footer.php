</main>

<!-- Universal Certificate Preview Modal -->
<div class="modal fade" id="certificateViewerModal" tabindex="-1" aria-labelledby="certificateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-primary" id="certificateModalLabel">
          <i class="bi bi-file-earmark-check me-2"></i>Certificate Document Viewer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 text-center bg-light" style="min-height: 480px;">
        <div id="modalLoadingSpinner" class="py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 small">Loading document preview...</p>
        </div>
        <iframe id="certPreviewFrame" src="" style="width: 100%; height: 580px; border: none; display: none;" onload="document.getElementById('modalLoadingSpinner').style.display='none'; this.style.display='block';"></iframe>
      </div>
      <div class="modal-footer border-top py-2 d-flex justify-content-between">
        <span class="text-muted small" id="certModalMeta">Institutional Verified Proof</span>
        <div>
          <a id="certModalDownloadBtn" href="#" class="btn btn-sm btn-outline-primary" target="_blank" download>
            <i class="bi bi-download me-1"></i>Open in New Tab
          </a>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="app-footer">
  <div class="container-fluid px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <div>
      <span class="fw-bold text-dark"><?= e(COLLEGE_NAME) ?></span>
      <span class="text-muted d-none d-sm-inline"> &middot; <?= e(PROJECT_CODE) ?></span>
    </div>
    <div class="text-muted">
      <span><?= e(APP_NAME) ?> &copy; <?= date('Y') ?></span>
      <span class="mx-1">&middot;</span>
      <a href="<?= e(base_url('setup.php')) ?>" class="text-decoration-none text-muted">System Diagnostic</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function viewCertificateModal(certUrl, title) {
  const modal = new bootstrap.Modal(document.getElementById('certificateViewerModal'));
  const frame = document.getElementById('certPreviewFrame');
  const spinner = document.getElementById('modalLoadingSpinner');
  const titleEl = document.getElementById('certificateModalLabel');
  const dlBtn = document.getElementById('certModalDownloadBtn');

  titleEl.innerHTML = '<i class="bi bi-file-earmark-check me-2"></i>' + (title || 'Certificate Preview');
  dlBtn.href = certUrl;
  
  spinner.style.display = 'block';
  frame.style.display = 'none';
  frame.src = certUrl;
  modal.show();
}
</script>
</body>
</html>