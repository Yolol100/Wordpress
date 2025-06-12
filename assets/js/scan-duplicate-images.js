/**
 * assets/js/scan-duplicate-images.js
 * Complete JS for scan page: loader, search/filter, sort, rescan, localStorage & “no results” logic.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const intro       = document.getElementById('mim-scan-intro');
  const loader      = document.getElementById('mim-scan-loader');
  const form        = document.querySelector('.mim-scan-page-form');
  const searchInput = document.getElementById('mim-search');
  const filterType  = document.getElementById('filter-type');
  const rescanBtn   = document.getElementById('mim-rescan-btn');

  // Restore last search/filter from localStorage
  if (searchInput && localStorage.getItem('mim-search')) {
    searchInput.value = localStorage.getItem('mim-search');
  }
  if (filterType && localStorage.getItem('mim-filter')) {
    filterType.value = localStorage.getItem('mim-filter');
  }

  // On form submit: hide intro, show loader
  if (form) {
    form.addEventListener('submit', () => {
      if (intro)  intro.hidden  = true;
      if (loader) loader.hidden = false;
    });
  }

  // Initial UI update based on results
  fillSummary();

  // Search input: update localStorage and filter results live
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      localStorage.setItem('mim-search', searchInput.value);
      filterScanResults();
    });
  }

  // Type filter: update localStorage and filter by type
  if (filterType) {
    filterType.addEventListener('change', () => {
      localStorage.setItem('mim-filter', filterType.value);
      filterTableByType(filterType.value);
    });
  }

  // Column sorting on header click
  document.querySelectorAll('.dius-results-table thead th').forEach((th, idx) => {
    th.addEventListener('click', () => sortTable(idx));
  });

  // Rescan button: reload page
  if (rescanBtn) {
    rescanBtn.addEventListener('click', () => {
      location.reload();
    });
  }

  // Functions

  function fillSummary() {
    const rows = Array.from(document.querySelectorAll('.dius-results-table tbody tr'))
      .filter(r => r.style.display !== 'none');
    const count = rows.length;

    // Show/hide controls & results depending on rows count
    const controls   = document.getElementById('mim-enhanced-controls');
    const wrapperEls = document.querySelectorAll('.scan-wrapper > *');
    if (count > 0) {
      if (controls) controls.style.display = '';
      wrapperEls.forEach(el => el.style.display = '');
    }

    // Update summary text with count and unique pages
    const pages = new Set();
    rows.forEach(r => {
      r.querySelectorAll('td:nth-child(2) a').forEach(a => pages.add(a.href));
    });
    const summaryEl = document.getElementById('mim-scan-summary');
    if (summaryEl) {
      summaryEl.textContent =
        `${count} dubbele afbeelding${count !== 1 ? 'en' : ''} gevonden in ${pages.size} pagina${pages.size !== 1 ? "'s" : ''}.`;
    }
  }

  function filterScanResults() {
    const term = searchInput.value.toLowerCase();
    document.querySelectorAll('.dius-results-table tbody tr').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
    fillSummary();
  }

  function filterTableByType(type) {
    document.querySelectorAll('.dius-results-table tbody tr').forEach(row => {
      const ctx = row.children[2]?.innerText.toLowerCase() || '';
      let show = type === 'all';
      if (type === 'post_content')        show = ctx.includes('in content');
      if (type === 'featured_image')      show = ctx.includes('uitgelichte');
      if (type === 'acf_field')           show = ctx.includes('acf veld');
      if (type === 'elementor_template')  show = ctx.includes('elementor');
      row.style.display = show ? '' : 'none';
    });
    fillSummary();
  }

  function sortTable(colIndex) {
    const table = document.querySelector('.dius-results-table');
    if (!table) return;
    const tbody = table.tBodies[0];
    const asc   = table.dataset.sortAsc === 'true' ? false : true;
    const rows  = Array.from(tbody.rows);
    rows.sort((a, b) => {
      const aText = a.cells[colIndex].innerText.trim().toLowerCase();
      const bText = b.cells[colIndex].innerText.trim().toLowerCase();
      return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });
    rows.forEach(r => tbody.appendChild(r));
    table.dataset.sortAsc = asc;
    table.querySelectorAll('.sort-arrow').forEach(el => el.textContent = '');
    const arrow = table.querySelector(`thead th:nth-child(${colIndex+1}) .sort-arrow`);
    if (arrow) arrow.textContent = asc ? ' ▲' : ' ▼';
  }
});