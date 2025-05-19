/**
 * assets/js/import-media.js
 * Bevat drag-drop, validatie, samenvatting en loader voor importeren.
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('import-form');
  const fileInput = document.getElementById('csv-file-input');
  const summary = document.getElementById('import-summary');
  const loader = document.getElementById('import-loader');
  const dragArea = document.getElementById('drag-drop-area');
  const fileError = document.getElementById('file-error');
  const importButton = document.getElementById('import-button');
  const fileSelectedName = document.getElementById('file-selected-name');

  // Drag & drop events
  ['dragenter', 'dragover'].forEach(evt =>
    dragArea.addEventListener(evt, e => {
      e.preventDefault();
      dragArea.classList.add('drag-over');
    })
  );
  ['dragleave', 'drop'].forEach(evt =>
    dragArea.addEventListener(evt, e => {
      e.preventDefault();
      dragArea.classList.remove('drag-over');
    })
  );
  dragArea.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      validateFile();
    }
  });
  dragArea.addEventListener('click', function() {
    fileInput.click();
  });

  fileInput.addEventListener('change', validateFile);

  function validateFile() {
    fileError.hidden = true;
    fileError.innerText = '';
    fileSelectedName.innerText = '';
    summary.hidden = true;

    const file = fileInput.files[0];
    if (!file) {
      fileError.innerText = "Geen bestand gekozen";
      fileError.hidden = false;
      fileError.focus();
      return false;
    }
    if (file.type !== "text/csv" && !file.name.endsWith('.csv')) {
      fileError.innerText = "Alleen .csv-bestanden zijn toegestaan.";
      fileError.hidden = false;
      fileInput.value = '';
      fileError.focus();
      return false;
    }
    if (file.size > 2 * 1024 * 1024) { // 2MB limit
      fileError.innerText = "Bestand is te groot. Maximaal 2MB.";
      fileError.hidden = false;
      fileInput.value = '';
      fileError.focus();
      return false;
    }
    // Alleen bij een geldig bestand:
    fileSelectedName.innerText = file.name;
    summary.hidden = false;
    summary.textContent = `Bestand: ${file.name}, Grootte: ${(file.size / 1024).toFixed(2)} KB`;
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
});