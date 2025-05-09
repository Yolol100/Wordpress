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

  // 2) Select-all checkbox
  const selectAll = document.getElementById('select_all');
  selectAll?.addEventListener('change', () => {
    const checked = selectAll.checked;
    document.querySelectorAll('.delete-checkbox')
      .forEach(cb => {
        cb.checked = checked;
        cb.dispatchEvent(new Event('change'));
      });
  });

  // 3) Enable/disable delete-knop op basis van selectie
  const deleteButton = document.getElementById('dius-delete-selected');
  document.querySelectorAll('.delete-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
      const anyChecked = Array.from(document.querySelectorAll('.delete-checkbox'))
                              .some(c => c.checked);
      deleteButton.disabled = ! anyChecked;
    });
  });
});