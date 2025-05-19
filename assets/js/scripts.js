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

  // =========================
  // Drag & Drop + validatie + loader voor import
  // =========================

  const dragDropArea = document.getElementById('drag-drop-area');
  const fileInput = document.getElementById('csv-file-input');
  const form = document.getElementById('import-form');
  const loader = document.getElementById('import-loader');
  const summary = document.getElementById('import-summary');
  const fileError = document.getElementById('file-error');
  const importButton = document.getElementById('import-button');

  if (dragDropArea && fileInput && form) {
    // Drag events
    dragDropArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      dragDropArea.classList.add('drag-over');
    });
    dragDropArea.addEventListener('dragleave', function(e) {
      dragDropArea.classList.remove('drag-over');
    });
    dragDropArea.addEventListener('drop', function(e) {
      e.preventDefault();
      dragDropArea.classList.remove('drag-over');
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        validateFile();
      }
    });
    dragDropArea.addEventListener('click', function() {
      fileInput.click();
    });

    fileInput.addEventListener('change', validateFile);

    function validateFile() {
      fileError.hidden = true;
      fileError.innerText = '';
      const file = fileInput.files[0];
      if (!file) return;
      if (file.type !== "text/csv" && !file.name.endsWith('.csv')) {
        fileError.innerText = "Alleen .csv-bestanden zijn toegestaan.";
        fileError.hidden = false;
        fileInput.value = '';
        fileError.focus();
        return false;
      }
      if (file.size > 2 * 1024 * 1024) { // 2MB limiet
        fileError.innerText = "Bestand is te groot. Maximaal 2MB.";
        fileError.hidden = false;
        fileInput.value = '';
        fileError.focus();
        return false;
      }
      return true;
    }

    form.addEventListener('submit', function(e) {
      if (!validateFile()) {
        e.preventDefault();
        return false;
      }
      loader.hidden = false;
      importButton.disabled = true;
    });

    // Toegankelijkheid: focus naar fouten
    fileError.addEventListener('DOMSubtreeModified', function() {
      if (!fileError.hidden) fileError.focus();
    });

    // Optioneel: samenvatting tonen na server response (kan via AJAX)
    // summary.hidden = false; summary.innerHTML = ...;
  }
});