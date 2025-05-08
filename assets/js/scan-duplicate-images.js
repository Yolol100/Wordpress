/**
 * assets/js/scan-duplicate-images.js
 *
 * Verbergt de introductie bij het starten van de scan
 * en ondersteunt bulk-select (“select all”) voor delete-checkboxes.
 */
document.addEventListener('DOMContentLoaded', () => {
  // Verberg intro zodra de scan gestart wordt
  const scanForm = document.querySelector('.mim-scan-page-form');
  scanForm?.addEventListener('submit', () => {
    const intro = document.getElementById('mim-scan-intro');
    if (intro) {
      intro.hidden = true;
    }
  });

  // Bulk-select all checkboxes
  const selectAll = document.getElementById('select_all');
  selectAll?.addEventListener('change', () => {
    document.querySelectorAll('.delete-checkbox')
      .forEach(cb => {
        cb.checked = selectAll.checked;
      });
  });
});