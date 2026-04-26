'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('import-form');
  const fileInput = document.getElementById('csv-file-input');
  const summary = document.getElementById('import-summary');
  const loader = document.getElementById('import-loader');
  const dragArea = document.getElementById('drag-drop-area');
  const fileError = document.getElementById('file-error');
  const importButton = document.getElementById('import-button');
  const fileSelectedName = document.getElementById('file-selected-name');

  if (!form || !fileInput || !summary || !loader || !dragArea || !fileError || !importButton || !fileSelectedName) {
    return;
  }

  // Add ARIA attributes for accessibility
  fileError.setAttribute('role', 'alert');
  fileError.setAttribute('aria-live', 'assertive');
  dragArea.setAttribute('tabindex', '0');
  dragArea.setAttribute('aria-label', 'Sleep hier je CSV-bestand naartoe of klik om te selecteren');

  // Drag & drop events – visuele feedback bij drag-over
  ['dragenter', 'dragover'].forEach(evt =>
    dragArea.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dragArea.classList.add('drag-over');
    })
  );
  ['dragleave', 'drop'].forEach(evt =>
    dragArea.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dragArea.classList.remove('drag-over');
    })
  );

  // Drop event – bestanden plaatsen in file input
  dragArea.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      showFileInfo();
    }
  });

  // Klik op drag area opent file picker
  dragArea.addEventListener('click', () => fileInput.click());

  // Ook keyboard toegankelijk maken: Enter en Space openen file picker
  dragArea.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      fileInput.click();
    }
  });

  // Update UI met bestandsnaam en grootte (en validatie)
  fileInput.addEventListener('change', showFileInfo);

  // Toegankelijkheid: focus op foutmelding als deze zichtbaar wordt
  const fileErrorObserver = new MutationObserver(() => {
    if (!fileError.hidden) fileError.focus();
  });
  fileErrorObserver.observe(fileError, { attributes: true, childList: true, characterData: true });

  /**
   * Toon info over geselecteerd bestand en valideer
   * @returns {boolean} true als bestand geldig is, anders false
   */
  function showFileInfo() {
    fileError.hidden = true;
    fileError.innerText = '';
    fileSelectedName.innerText = '';
    summary.hidden = true;
    importButton.disabled = false;
    importButton.textContent = 'Importeren';

    const file = fileInput.files[0];
    if (!file) {
      fileError.innerText = 'Geen bestand gekozen.';
      fileError.hidden = false;
      fileError.focus();
      return false;
    }

    // Validatie: alleen CSV bestanden toegestaan
    if (!file.name.toLowerCase().endsWith('.csv')) {
      fileError.innerText = 'Alleen CSV-bestanden zijn toegestaan.';
      fileError.hidden = false;
      fileError.focus();
      return false;
    }

    fileSelectedName.innerText = file.name;
    summary.hidden = false;
    summary.textContent = `Bestand: ${file.name}, Grootte: ${(file.size / 1024).toFixed(2)} KB`;
    return true;
  }

  // Form submit event - toon loader en disable knop, progress feedback
  form.addEventListener('submit', e => {
    if (!fileInput.files.length) {
      e.preventDefault();
      fileError.innerText = 'Geen bestand gekozen.';
      fileError.hidden = false;
      fileError.focus();
      return false;
    }
    if (!showFileInfo()) {
      e.preventDefault();
      return false;
    }
    loader.hidden = false;
    importButton.disabled = true;
    importButton.textContent = 'Importeren...'; // Visuele progress feedback
  });
});
