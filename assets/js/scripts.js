'use strict';

jQuery(($) => {
  // Alles selecteren
  $('#select-all-columns').on('click', function () {
    $('input[name="export_columns[]"]').prop('checked', true);
  });

  // Niets selecteren
  $('#deselect-all-columns').on('click', function () {
    $('input[name="export_columns[]"]').prop('checked', false);
  });

  // Profiel opslaan
  $('#save-export-profile').on('click', function () {
    const cols = $('input[name="export_columns[]"]:checked')
      .map((_, el) => el.value)
      .get();
    localStorage.setItem('export_profile', JSON.stringify(cols));
    alert('Profiel opgeslagen!');
  });

  // Profiel laden
  $('#load-export-profile').on('click', function () {
    const stored = localStorage.getItem('export_profile');
    if (!stored) {
      alert('Geen profiel gevonden.');
      return;
    }
    const cols = JSON.parse(stored);
    $('input[name="export_columns[]"]')
      .prop('checked', false)
      .filter((_, el) => cols.includes(el.value))
      .prop('checked', true);
    alert('Profiel geladen!');
  });

  // Profiel verwijderen
  $('#delete-export-profile').on('click', function () {
    localStorage.removeItem('export_profile');
    alert('Profiel verwijderd.');
  });

  // Spinner tonen bij export
  $('.export-page-form').on('submit', function () {
    $('#export-spinner').show();
  });
});