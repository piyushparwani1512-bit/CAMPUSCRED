/**
 * CampusCred - Client-Side Interactive Scripts
 */

// 1. Copy to Clipboard Utility
function copyToClipboard(text, triggerBtn) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(() => {
      showCopiedFeedback(triggerBtn);
    }).catch(() => {
      fallbackCopy(text, triggerBtn);
    });
  } else {
    fallbackCopy(text, triggerBtn);
  }
}

function fallbackCopy(text, triggerBtn) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.focus();
  ta.select();
  try {
    document.execCommand('copy');
    showCopiedFeedback(triggerBtn);
  } catch (err) {
    alert('Copy manually: ' + text);
  }
  document.body.removeChild(ta);
}

function showCopiedFeedback(btn) {
  if (!btn) return;
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-check2 me-1 text-success"></i>Copied!';
  btn.classList.add('btn-success-subtle');
  setTimeout(() => {
    btn.innerHTML = originalHtml;
    btn.classList.remove('btn-success-subtle');
  }, 2000);
}

// 2. Certificate Viewer in Modal
function previewCertificate(url, title, mimeType) {
  let modalEl = document.getElementById('certPreviewModal');
  if (!modalEl) {
    const modalHtml = `
      <div class="modal fade" id="certPreviewModal" tabindex="-1" aria-labelledby="certModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-navy text-white py-3">
              <h5 class="modal-title fw-bold" id="certModalLabel">
                <i class="bi bi-patch-check-fill text-gold me-2"></i>Official Certificate Document
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="certModalBody" style="min-height: 520px; background: #0f172a;">
              <!-- Dynamic Content -->
            </div>
            <div class="modal-footer bg-light">
              <span class="small text-muted me-auto" id="certModalSubtitle">Verified Institutional Record</span>
              <a href="#" id="certModalDownload" class="btn btn-primary btn-sm" target="_blank" download>
                <i class="bi bi-download me-1"></i>Download Proof
              </a>
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    modalEl = document.getElementById('certPreviewModal');
  }

  const titleEl = document.getElementById('certModalLabel');
  const bodyEl = document.getElementById('certModalBody');
  const subEl = document.getElementById('certModalSubtitle');
  const dlBtn = document.getElementById('certModalDownload');

  if (title) titleEl.textContent = title;
  if (subEl) subEl.textContent = 'Document URL: ' + url;
  if (dlBtn) dlBtn.href = url;

  if (url.toLowerCase().endsWith('.pdf') || mimeType === 'application/pdf') {
    bodyEl.innerHTML = `<iframe src="${url}#toolbar=0" style="width: 100%; height: 600px; border: none;"></iframe>`;
  } else {
    bodyEl.innerHTML = `<div class="d-flex align-items-center justify-content-center p-3" style="min-height: 500px;">
      <img src="${url}" class="img-fluid rounded shadow" style="max-height: 560px; object-fit: contain;" alt="Certificate Proof">
    </div>`;
  }

  const bsModal = new bootstrap.Modal(modalEl);
  bsModal.show();
}

// 3. Client-side Filter helper
function filterTalentCards() {
  const query = (document.getElementById('talentSearchInput')?.value || '').toLowerCase();
  const deptFilter = (document.getElementById('deptSelectFilter')?.value || '').toLowerCase();
  const catFilter = (document.getElementById('catSelectFilter')?.value || '').toLowerCase();
  
  const cards = document.querySelectorAll('.candidate-filter-item');
  let matchCount = 0;

  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    const dept = (card.getAttribute('data-department') || '').toLowerCase();
    const categories = (card.getAttribute('data-categories') || '').toLowerCase();

    const matchesQuery = !query || text.includes(query);
    const matchesDept = !deptFilter || dept === deptFilter;
    const matchesCat = !catFilter || categories.includes(catFilter);

    if (matchesQuery && matchesDept && matchesCat) {
      card.style.display = '';
      matchCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const countDisplay = document.getElementById('talentMatchCount');
  if (countDisplay) {
    countDisplay.textContent = matchCount;
  }
}

// 4. Fallback PDF Generator for Official Portfolio
function generatePortfolioPDF() {
  if (window.jspdf && window.jspdf.jsPDF) {
    const doc = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
    const content = document.getElementById('portfolioDocument');
    if (content) {
      doc.html(content, {
        callback: function (pdf) {
          pdf.save('Verified_Achievement_Portfolio.pdf');
        },
        x: 15,
        y: 15,
        width: 565,
        windowWidth: 1000
      });
      return;
    }
  }
  // Fallback to browser print which prints to PDF cleanly
  window.print();
}

// 5. Initialize tooltips
document.addEventListener('DOMContentLoaded', () => {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
});
