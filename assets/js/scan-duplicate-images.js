/**
 * assets/js/scan-duplicate-images.js
 * AJAX scan pagina script: WordPress, Elementor, ACF, WooCommerce ondersteuning
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const contentContainer = document.getElementById('mim-scan-content');
  const form = document.querySelector('.mim-scan-page-form');

  // Functie om scan via AJAX uit te voeren en resultaten te tonen
  function performScan() {
    if (!contentContainer) return;

    // Toon loader tijdens scan
    contentContainer.innerHTML = `
      <div class="mim-scan-loader-centered" role="status" aria-live="polite" aria-busy="true">
        <div class="spinner" aria-hidden="true"></div>
        <em>${mimAjaxData.scanningText}</em>
      </div>
    `;

    // AJAX call
    fetch(mimAjaxData.ajax_url + '?action=mim_scan_duplicates&mim_scan_duplicates_nonce=' + mimAjaxData.nonce, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (!response.ok) throw new Error('Network response was not ok');
      return response.text();
    })
    .then(html => {
      contentContainer.innerHTML = html;
      initScanPageUI(); // Herinitialiseer UI na verversen content
    })
    .catch(error => {
      contentContainer.innerHTML = `
        <div class="mim-notice mim-notice--error" role="alert">
          ${mimAjaxData.errorText}
        </div>
      `;
      console.error('Scan error:', error);
    });
  }

  // Event listener voor submit formulier (start scan)
  if (form && contentContainer) {
    form.addEventListener('submit', e => {
      e.preventDefault();
      performScan();
    });
  }

  // Initialiseer UI elementen zoals filter, sortering, zoekveld, rescan knop etc.
  function initScanPageUI() {
    const searchInput = document.getElementById('mim-search');
    const filterType = document.getElementById('filter-type');
    const summaryEl = document.getElementById('mim-scan-summary');
    const table = document.querySelector('.dius-results-table');
    const tbody = table ? table.tBodies[0] : null;
    const rescanBtn = document.getElementById('mim-rescan-btn');

    // Nieuwe event listener op knop "Opnieuw scannen" om scan opnieuw te starten via AJAX
    if (rescanBtn) {
      rescanBtn.addEventListener('click', e => {
        e.preventDefault();
        performScan();
      });
    }

    if (!tbody || !summaryEl) return;

    function fillSummary() {
      const visibleRows = Array.from(tbody.rows).filter(r => r.style.display !== 'none');
      const count = visibleRows.length;

      const pages = new Set();
      visibleRows.forEach(row => {
        row.querySelectorAll('td:nth-child(2) a').forEach(a => pages.add(a.href));
      });

      summaryEl.textContent =
        `${count} dubbele afbeelding${count !== 1 ? 'en' : ''} gevonden in ${pages.size} pagina${pages.size !== 1 ? "'s" : ''}.`;
    }

    function filterScanResults() {
      const term = (searchInput.value || '').toLowerCase();
      Array.from(tbody.rows).forEach(row => {
        const rowText = (row.innerText || '').toLowerCase();
        row.style.display = rowText.includes(term) ? '' : 'none';
      });
      fillSummary();
    }

    function filterTableByType(type) {
      Array.from(tbody.rows).forEach(row => {
        const ctx = row.children[2] && typeof row.children[2].innerText === 'string'
          ? row.children[2].innerText.toLowerCase() : '';
        let show = type === 'all';
        if (type === 'post_content') show = ctx.includes('in content');
        else if (type === 'featured_image') show = ctx.includes('uitgelichte');
        else if (type === 'acf_field') show = ctx.includes('acf veld');
        else if (type === 'elementor') show = ctx.includes('elementor');
        else if (type === 'woocommerce') show = ctx.includes('woocommerce');
        row.style.display = show ? '' : 'none';
      });
      fillSummary();
    }

    // Initial setup
    fillSummary();

    if (searchInput) {
      searchInput.addEventListener('input', filterScanResults);
    }
    if (filterType) {
      filterType.addEventListener('change', () => filterTableByType(filterType.value));
    }

    // Sorting op kolommen
    document.querySelectorAll('.dius-results-table thead th').forEach((th, idx) => {
      th.setAttribute('tabindex', '0');
      th.setAttribute('role', 'button');
      th.addEventListener('click', () => sortTable(idx));
      th.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          sortTable(idx);
        }
      });
    });

    function sortTable(colIndex) {
      if (!table || !tbody) return;
      const isAsc = table.querySelectorAll('thead th')[colIndex].getAttribute('aria-sort') === 'ascending';
      const rows = Array.from(tbody.rows);

      rows.sort((a, b) => {
        const aText = (a.cells[colIndex]?.innerText || '').trim().toLowerCase();
        const bText = (b.cells[colIndex]?.innerText || '').trim().toLowerCase();
        return isAsc ? bText.localeCompare(aText) : aText.localeCompare(bText);
      });

      rows.forEach(row => tbody.appendChild(row));

      table.querySelectorAll('thead th').forEach((th, i) => {
        if (i === colIndex) {
          th.setAttribute('aria-sort', isAsc ? 'descending' : 'ascending');
        } else {
          th.removeAttribute('aria-sort');
        }
      });

      table.querySelectorAll('.sort-arrow').forEach(el => el.textContent = '');
      const arrow = table.querySelectorAll('thead th')[colIndex].querySelector('.sort-arrow');
      if (arrow) arrow.textContent = isAsc ? ' ▼' : ' ▲';
    }
  }

  // Globale fallback teksten
  window.mimAjaxData = window.mimAjaxData || {
    scanningText: 'Afbeeldingen worden gescand... Een ogenblik geduld.',
    errorText: 'Er is een fout opgetreden tijdens het scannen. Probeer het opnieuw.',
    ajax_url: '',
    nonce: ''
  };

  // Init UI als content al geladen is (bijvoorbeeld bij AJAX verversing)
  if (contentContainer) {
    initScanPageUI();
  }
});