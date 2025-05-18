/**
 * assets/js/import-media.js
 * Bevat drag-drop, validatie, samenvatting en loader voor importeren.
 */
document.addEventListener('DOMContentLoaded', () => {
  const form       = document.querySelector('.import-page-form');
  const fileInput  = document.querySelector('.import-file-input');
  const summary    = document.getElementById('import-summary');
  const loader     = document.getElementById('import-loader');
  const dragArea   = document.getElementById('drag-drop-area');

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
      updateSummary();
    }
  });

  // File-selectie event
  fileInput.addEventListener('change', updateSummary);

  // Vorm-submit: toon loader
  if (form) {
    form.addEventListener('submit', () => {
      loader.hidden = false;
    });
  }

  // Update samenvatting bij selectie
  function updateSummary() {
    if (!fileInput.files.length) {
      summary.hidden = true;
      return;
    }
    const file = fileInput.files[0];
    const sizeKB = (file.size / 1024).toFixed(2);
    summary.hidden = false;
    summary.textContent = `Bestand: ${file.name}, Grootte: ${sizeKB} KB`;
  }
});