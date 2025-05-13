/**
 * assets/js/scan-duplicate-images.js
 * Verbergt de introductie bij het starten van de scan.
 */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('.mim-scan-page-form')?.addEventListener('submit', () => {
    document.getElementById('mim-scan-intro')?.setAttribute('hidden', true);
  });
});