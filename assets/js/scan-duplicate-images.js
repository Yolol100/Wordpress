/**
 * assets/js/scan-duplicate-images.js
 *
 * Verbergt de introductie bij het starten van de scan.
 * (Bulk-select functionaliteit verwijderd)
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
});