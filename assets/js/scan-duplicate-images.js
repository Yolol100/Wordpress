/**
 * assets/js/scan-duplicate-images.js
 * Volledig vernieuwd voor scanpagina: ondersteunt WordPress, Elementor, ACF en WooCommerce.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const intro       = document.getElementById('mim-scan-intro');
  const loader      = document.getElementById('mim-scan-loader');
  const form        = document.querySelector('.mim-scan-page-form');
  const searchInput = document.getElementById('mim-search');
  const filterType  = document.getElementById('filter-type');
  const rescanBtn   = document.getElementById('mim-rescan-btn');
  const controls    = document.getElementById('mim-enhanced-controls');
  const summaryEl   = document.getElementById('mim-scan-summary');
  const table       = document.querySelector('.dius-results-table');
  const tbody       = table ? table.tBodies[0] : null;

  // Restore last search/filter from localStorage
  if (searchInput && localStorage.getItem('mim-search')) {
    searchInput.value = localStorage.getItem('mim-search');
  }
  if (filterType && localStorage.getItem('mim-filter')) {
    filterType.value = localStorage.getItem('mim-filter');
  }

  // On form submit: hide intro, show loader
  if (form) {
    form.addEventListener('submit', (e) => {
      if (intro)  intro.hidden  = true;
      if (loader) loader.hidden = false;
    });
  }

  // Initial UI update based on results
  fillSummary();
  filterScanResults();
  if (filterType) filterTableByType(filterType.value);

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

  // Column sorting on header click, with aria-sort update
  document.querySelectorAll('.dius-results-table thead th').forEach((th, idx) => {
    th.setAttribute('tabindex', '0');
    th.setAttribute('role', 'button');
    th.addEventListener('click', () => sortTable(idx));
    th.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        sortTable(idx);
      }
    });
  });

  // Rescan button: reload page + keyboard accessible
  if (rescanBtn) {
    rescanBtn.setAttribute('tabindex', '0');
    rescanBtn.setAttribute('role', 'button');
    rescanBtn.addEventListener('click', () => {
      localStorage.removeItem('mim-search');
      localStorage.removeItem('mim-filter');
      location.reload();
    });
    rescanBtn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        localStorage.removeItem('mim-search');
        localStorage.removeItem('mim-filter');
        location.reload();
      }
    });
  }

  // Functions

  // Update summary text and toggle controls visibility
  function fillSummary() {
    if (!tbody) return;

    const visibleRows = Array.from(tbody.rows).filter(r => r.style.display !== 'none');
    const count = visibleRows.length;

    // Show/hide controls & results depending on rows count
    if (controls) controls.style.display = count > 0 ? '' : 'none';

    // Show/hide scan wrapper children except intro when no results
    document.querySelectorAll('.scan-wrapper > *').forEach(el => {
      if (count > 0) {
        el.style.display = '';
      } else if (!el.classList.contains('mim-scan-page-description')) {
        el.style.display = 'none';
      }
    });

    // Verzamel unieke pagina's/producten/post types
    const pages = new Set();
    visibleRows.forEach(row => {
      row.querySelectorAll('td:nth-child(2) a').forEach(a => pages.add(a.href));
    });
    if (summaryEl) {
      summaryEl.textContent =
        `${count} dubbele afbeelding${count !== 1 ? 'en' : ''} gevonden in ${pages.size} pagina${pages.size !== 1 ? "'s" : ''}.`;
    }
  }

  // Filter rows based on search input
  function filterScanResults() {
    if (!tbody) return;
    const term = searchInput ? searchInput.value.toLowerCase() : '';
    Array.from(tbody.rows).forEach(row => {
      const rowText = row.innerText.toLowerCase();
      row.style.display = rowText.includes(term) ? '' : 'none';
    });
    fillSummary();
  }

  // Filter rows based on selected type, nu inclusief WooCommerce, ACF, Elementor, en standaard WP
  function filterTableByType(type) {
    if (!tbody) return;
    Array.from(tbody.rows).forEach(row => {
      const ctx = row.children[2]?.innerText.toLowerCase() || '';
      let show = type === 'all';
      if (type === 'post_content')           show = ctx.includes('in content');
      else if (type === 'featured_image')    show = ctx.includes('uitgelichte');
      else if (type === 'acf_field')         show = ctx.includes('acf veld');
      else if (type === 'elementor_template') show = ctx.includes('elementor');
      else if (type === 'woocommerce')       show = ctx.includes('woocommerce');
      row.style.display = show ? '' : 'none';
    });
    fillSummary();
  }

  // Sort table column by index
  function sortTable(colIndex) {
    if (!table || !tbody) return;

    const currentTh = table.querySelectorAll('thead th')[colIndex];
    const isAsc = currentTh.getAttribute('aria-sort') === 'ascending';
    const rows = Array.from(tbody.rows);

    rows.sort((a, b) => {
      const aText = a.cells[colIndex].innerText.trim().toLowerCase();
      const bText = b.cells[colIndex].innerText.trim().toLowerCase();
      return isAsc ? bText.localeCompare(aText) : aText.localeCompare(bText);
    });

    rows.forEach(row => tbody.appendChild(row));

    // Update aria-sort on all headers
    table.querySelectorAll('thead th').forEach((th, i) => {
      if (i === colIndex) {
        th.setAttribute('aria-sort', isAsc ? 'descending' : 'ascending');
      } else {
        th.removeAttribute('aria-sort');
      }
    });

    // Update sort arrow indicators
    table.querySelectorAll('.sort-arrow').forEach(el => el.textContent = '');
    const arrow = currentTh.querySelector('.sort-arrow');
    if (arrow) arrow.textContent = isAsc ? ' ▼' : ' ▲';
  }
});