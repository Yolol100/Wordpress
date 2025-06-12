'use strict';

jQuery(($) => {
  const $form = $('.export-page-form');
  const $exportBtn = $form.find('.export-button');
  const $checkboxes = $form.find('input[name="export_columns[]"]');

  // Form validatie bij submit
  $form.on('submit', (e) => {
    if ($checkboxes.filter(':checked').length === 0) {
      e.preventDefault();
      alert('Selecteer minstens één kolom om te exporteren.');
      // Focus op eerste checkbox voor toegankelijkheid
      $checkboxes.first().focus();
    } else {
      $exportBtn.prop('disabled', true);
      // Optioneel: toon loader of visuele feedback hier
    }
  });
});