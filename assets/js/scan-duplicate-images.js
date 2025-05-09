/**
 * assets/js/scan-duplicate-images.js
 * Verbergt introductie na scan & ondersteunt bulk-select/enabling delete knop.
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1) Intro verbergen zodra de scan gestart wordt
  const scanForm = document.querySelector('.mim-scan-page-form');
  scanForm?.addEventListener('submit', () => {
    document.getElementById('mim-scan-intro')?.setAttribute('hidden', 'hidden');
  });

  // Gebruik event delegation zodat alles blijft werken, ook als de tabel vernieuwd wordt
  document.addEventListener('change', (e) => {
    const deleteButton = document.getElementById('dius-delete-selected');

    // 2) Select-all checkbox
    if (e.target && e.target.id === 'select_all') {
      const checked = e.target.checked;
      document.querySelectorAll('.delete-checkbox')
        .forEach(cb => {
          cb.checked = checked;
        });
      // Trigger change event voor accessibility en consistent gedrag
      document.querySelectorAll('.delete-checkbox').forEach(cb => {
        cb.dispatchEvent(new Event('change'));
      });
    }

    // 3) Enable/disable delete-knop op basis van selectie
    if (
      (e.target && e.target.classList.contains('delete-checkbox')) ||
      (e.target && e.target.id === 'select_all')
    ) {
      const anyChecked = Array.from(document.querySelectorAll('.delete-checkbox'))
        .some(c => c.checked);
      if (deleteButton) deleteButton.disabled = !anyChecked;
    }
  });
});