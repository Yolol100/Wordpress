'use strict';

jQuery(($) => {
  const { ajax_url: ajaxUrl, translations } = ajax_object; // nonce verwijderd uit destructuring
  let currentPage = 1;

  const $tbody        = $('.media-items-tbody');
  const $noResults    = $('#noResults');
  const $pageFilter   = $('#pageFilter');
  const $pageFilterB  = $('#pageFilterBottom');
  const $searchTop    = $('#search-input');
  const $searchBot    = $('#search-input-bottom');
  const $prevTop      = $('#prevPage');
  const $prevBot      = $('#prevPageBottom');
  const $nextTop      = $('#nextPage');
  const $nextBot      = $('#nextPageBottom');
  const $perPageTop   = $('#rowsPerPage');
  const $perPageBot   = $('#rowsPerPageBottom');
  const $curPage      = $('#currentPage, #currentPageBottom');
  const $totPage      = $('#totalPages, #totalPagesBottom');

  // Helper voor POST
  const postJSON = (data) => $.post(ajaxUrl, data, null, 'json');

  // Opslaan metadata
  $(document).on('click', '.media-items-save-meta', ({ currentTarget }) => {
    const id = $(currentTarget).data('attachment-id');
    const data = {
      action: 'update_media_meta',
      attachment_id: id,
      title: $(`[data-attachment-id="${id}"].media-items-title-input`).val(),
      caption: $(`[data-attachment-id="${id}"].media-items-caption-input`).val(),
      description: $(`[data-attachment-id="${id}"].media-items-description-input`).val(),
      alt: $(`[data-attachment-id="${id}"].media-items-alt-input`).val(),
      new_filename: $(`[data-attachment-id="${id}"].media-items-new-filename-input`).val(),
      // nonce verwijderd
    };

    postJSON(data).done((response) => {
      if (response.success) {
        showToast(translations.saved_successfully || 'Opgeslagen!', 'success');
      } else {
        showToast(response.data.message || 'Fout bij opslaan.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij opslaan.', 'error');
    });
  });

  // Verwijderen media
  $(document).on('click', '.media-items-delete-media', ({ currentTarget }) => {
    if (!confirm(translations.confirm_delete || 'Weet je zeker dat je dit item wilt verwijderen?')) {
      return;
    }

    const id = $(currentTarget).data('attachment-id');
    const data = {
      action: 'delete_media',
      attachment_id: id,
      // nonce verwijderd
    };

    postJSON(data).done((response) => {
      if (response.success) {
        $(`[data-attachment-id="${id}"]`).closest('tr').remove();
        showToast(translations.deleted_successfully || 'Item verwijderd!', 'success');
        paginateAndFilter();
      } else {
        showToast(response.data.message || 'Fout bij verwijderen.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij verwijderen.', 'error');
    });
  });

  // Bulk verwijderen
  $('#bulkDeleteTop, #bulkDeleteBottom').on('click', () => {
    const selectedIds = $('.bulk-delete-checkbox:checked').map(function () {
      return $(this).data('attachment-id');
    }).get();

    if (selectedIds.length === 0) {
      showToast(translations.no_items_selected || 'Geen items geselecteerd voor bulkactie.', 'info');
      return;
    }

    if (!confirm(translations.confirm_bulk_delete || `Weet je zeker dat je ${selectedIds.length} geselecteerde items wilt verwijderen?`)) {
      return;
    }

    const data = {
      action: 'bulk_delete_media',
      attachment_ids: selectedIds,
      // nonce verwijderd
    };

    postJSON(data).done((response) => {
      if (response.success) {
        selectedIds.forEach((id) => {
          $(`[data-attachment-id="${id}"]`).closest('tr').remove();
        });
        showToast(translations.bulk_deleted_successfully || `${response.data.count} items succesvol verwijderd.`, 'success');
        $('#bulkSelectAll').prop('checked', false);
        paginateAndFilter();
      } else {
        showToast(response.data.message || 'Fout bij bulkverwijdering.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij bulkverwijdering.', 'error');
    });
  });

  // Selecteer/deselecteer alles
  $('#bulkSelectAll').on('change', function () {
    $('.bulk-delete-checkbox').prop('checked', $(this).prop('checked'));
  });

  // Filter en paginatie logica
  function paginateAndFilter() {
    const filterTop = $pageFilter.val();
    const filterBottom = $pageFilterB.val();
    const searchTop = $searchTop.val().toLowerCase().trim();
    const searchBottom = $searchBot.val().toLowerCase().trim();
    const perPage = Number($perPageTop.val());

    const $allRows = $tbody.find('.media-items-tr-item');
    let matchedRows = [];

    $allRows.each(function () {
      const $r = $(this);
      const rowText = $r.text().toLowerCase();
      const status = $r.data('status');

      const matchesFilterTop = (filterTop === 'all' || status === filterTop);
      const matchesFilterBottom = (filterBottom === 'all' || status === filterBottom);

      const activeSearchTerm = searchTop || searchBottom;
      const matchesSearch = rowText.includes(activeSearchTerm);

      if (matchesFilterTop && matchesFilterBottom && matchesSearch) {
        matchedRows.push(this);
      }
    });

    if (matchedRows.length === 0) {
      $noResults.show();
      $tbody.hide();
      $('.pagination-navigation').hide();
      $('.bulk-delete-controls').hide();
    } else {
      $noResults.hide();
      $tbody.show();
      $('.pagination-navigation').show();
      $('.bulk-delete-controls').show();
    }

    const totalPages = Math.max(1, Math.ceil(matchedRows.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    $curPage.text(currentPage);
    $totPage.text(totalPages);

    $allRows.hide();
    $(matchedRows).slice((currentPage - 1) * perPage, currentPage * perPage).show();
  }

  // Event bindings
  $('#applyFilter, #applyFilterBottom').on('click', () => {
    currentPage = 1;
    paginateAndFilter();
  });

  $('#search-input, #search-input-bottom').on('input', function() {
    currentPage = 1;
    paginateAndFilter();
  });

  $prevTop.add($prevBot).on('click', () => {
    if (currentPage > 1) {
      currentPage--;
      paginateAndFilter();
    }
  });

  $nextTop.add($nextBot).on('click', () => {
    if (currentPage < Number($totPage.text())) {
      currentPage++;
      paginateAndFilter();
    }
  });

  $perPageTop.add($perPageBot).on('change', function () {
    const v = $(this).val();
    $perPageTop.val(v);
    $perPageBot.val(v);
    currentPage = 1;
    paginateAndFilter();
  });

  // Initial call om de tabel te laden en te filteren bij het laden van de pagina
  paginateAndFilter();

  // Gewone alert als notificatie in plaats van toast
  function showToast(message, type = 'info') {
    alert(message);
  }
});