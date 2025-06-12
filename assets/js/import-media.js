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

  // Drag & drop events – visuele feedback bij drag-over
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

  // Drop event – bestanden plaatsen in file input
  dragArea.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      showFileInfo();
    }
  });

  // Klik op drag area opent file picker
  dragArea.addEventListener('click', () => fileInput.click());

  // Update UI met bestandsnaam en grootte (geen validatie)
  fileInput.addEventListener('change', showFileInfo);

  function showFileInfo() {
    fileError.hidden = true;
    fileError.innerText = '';
    fileSelectedName.innerText = '';
    summary.hidden = true;

    const file = fileInput.files[0];
    if (!file) {
      fileError.innerText = 'Geen bestand gekozen';
      fileError.hidden = false;
      fileError.focus();
      return false;
    }

    fileSelectedName.innerText = file.name;
    summary.hidden = false;
    summary.textContent = `Bestand: ${file.name}, Grootte: ${(file.size / 1024).toFixed(2)} KB`;
    return true;
  }

  // Form submit event - toon loader en disable knop, geen validatie
  form.addEventListener('submit', e => {
    if (!fileInput.files.length) {
      e.preventDefault();
      fileError.innerText = 'Geen bestand gekozen';
      fileError.hidden = false;
      fileError.focus();
      return false;
    }
    loader.hidden = false;
    importButton.disabled = true;
  });

  // Toegankelijkheid: focus op foutmelding als deze zichtbaar wordt
  fileError.addEventListener('DOMSubtreeModified', () => {
    if (!fileError.hidden) fileError.focus();
  });
});