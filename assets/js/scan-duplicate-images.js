/**
 * assets/js/scan-duplicate-images.js
 * Verbergt de introductie bij het starten van de scan.
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.mim-scan-page-form');
  if (form) {
    form.addEventListener('submit', () => {
      const intro = document.getElementById('mim-scan-intro');
      if (intro) intro.setAttribute('hidden', true);
    });
  }

  const searchInput = document.getElementById('mim-search');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      const rows = document.querySelectorAll('.scan-wrapper tr');
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  }
});

function filterTableByType(type) {
  const rows = document.querySelectorAll('.dius-results-table tbody tr');
  rows.forEach(row => {
    const context = row.children[2]?.innerText.toLowerCase();
    const show = (
      type === 'all' ||
      (type === 'post_content' && context.includes('in content')) ||
      (type === 'featured_image' && context.includes('uitgelichte')) ||
      (type === 'acf_field' && context.includes('acf veld')) ||
      (type === 'elementor_template' && context.includes('elementor'))
    );
    row.style.display = show ? '' : 'none';
  });
}