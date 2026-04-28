'use strict';

jQuery(($) => {
  const $form = $('.export-page-form');
  const $exportBtn = $form.find('.export-button');
  const $checkboxes = $form.find('input[name="export_columns[]"]');

  // Functie voor tonen van custom toast (voeg eventueel je eigen toast systeem toe)
  function showToast(message, type = 'error') {
    const $toast = $('<div class="custom-toast" role="alert" aria-live="assertive"></div>').text(message).appendTo('body');
    $toast.addClass(`toast-${type}`);
    $toast.focus();
    setTimeout(() => {
      $toast.fadeOut(400, () => $toast.remove());
    }, 4000);
  }

  // Form validatie bij submit
  $form.on('submit', (e) => {
    if ($checkboxes.filter(':checked').length === 0) {
      e.preventDefault();

      showToast('Selecteer minstens één kolom om te exporteren.', 'error');

      // Focus op eerste checkbox voor toegankelijkheid
      const $firstCheckbox = $checkboxes.first();
      if ($firstCheckbox.length) {
        $firstCheckbox.focus();
      }
      return false;
    }

    // Bij valide selectie: disable knop en toon progress feedback
    $exportBtn.prop('disabled', true);
    $exportBtn.text('Exporteren...');

    // Eventueel hier loader tonen of andere UI feedback
  });
});