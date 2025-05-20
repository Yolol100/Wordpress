// assets/js/export-acf-csv-admin.js
'use strict';

jQuery(($) => {
  const $form        = $('.export-page-form');
  const $exportBtn   = $form.find('.export-button');
  const $checkboxes  = $form.find('input[name="export_columns[]"]');

  // Knoppen voor alles-/niets-selectie
  $('#select-all-columns').on('click', (e) => {
    e.preventDefault();
    $checkboxes.prop('checked', true);
  });
  $('#deselect-all-columns').on('click', (e) => {
    e.preventDefault();
    $checkboxes.prop('checked', false);
  });

  // Form validatie bij submit
  $form.on('submit', (e) => {
    if ($checkboxes.filter(':checked').length === 0) {
      e.preventDefault();
      alert('Selecteer minstens één kolom om te exporteren.');
    } else {
      $exportBtn.prop('disabled', true);
    }
  });
});