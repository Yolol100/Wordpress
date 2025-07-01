'use strict';

jQuery(($) => {
  const { ajax_url: ajaxUrl, translations } = ajax_object; // nonce verwijderd uit destructuring
  let currentPage = 1;
  let undoTimeout = null;
  let undoData = null; // Data voor undo acties

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

  // Voeg dark mode class toe op basis van user preference
  function detectDarkMode() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      $('body').addClass('dark-mode');
    } else {
      $('body').removeClass('dark-mode');
    }
  }
  detectDarkMode();
  // Optioneel: monitor veranderingen
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      if (e.matches) {
        $('body').addClass('dark-mode');
      } else {
        $('body').removeClass('dark-mode');
      }
    });
  }

  // Undo-toast element wordt dynamisch toegevoegd
  const $undoToast = $('<div id="undoToast" role="alert" aria-live="assertive" tabindex="0" style="position:fixed;bottom:20px;right:20px;background:#333;color:#fff;padding:15px 25px;border-radius:6px;display:none;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.3);z-index:9999;">Actie ongedaan maken</div>').appendTo('body');

  // Helper voor POST
  const postJSON = (data) => $.post(ajaxUrl, data, null, 'json');

  // Functie om undo-toast te tonen
  function showUndoToast(onUndo) {
    $undoToast.show().focus();
    $undoToast.off('click').on('click', () => {
      onUndo();
      clearTimeout(undoTimeout);
      $undoToast.hide();
    });

    undoTimeout = setTimeout(() => {
      $undoToast.hide();
      undoData = null;
    }, 10000); // toast zichtbaar 10 seconden
  }

  // Metadata opslaan
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
    };

    postJSON(data).done((response) => {
      if (response.success) {
        showToast(translations.saved_successfully || 'Opgeslagen!', 'success');

        // Rij highlight als visuele feedback
        const $row = $(`[data-attachment-id="${id}"]`).closest('tr');
        $row.addClass('highlight-success');
        setTimeout(() => $row.removeClass('highlight-success'), 2000);
      } else {
        showToast(response.data.message || 'Fout bij opslaan.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij opslaan.', 'error');
    });
  });

  // Media verwijderen met undo optie
  $(document).on('click', '.media-items-delete-media', ({ currentTarget }) => {
    if (!confirm(translations.confirm_delete || 'Weet je zeker dat je dit item wilt verwijderen?')) {
      return;
    }

    const id = $(currentTarget).data('attachment-id');
    const $row = $(`[data-attachment-id="${id}"]`).closest('tr');
    const deletedHtml = $row.prop('outerHTML'); // Bewaar rij HTML

    const data = {
      action: 'delete_media',
      attachment_id: id,
    };

    postJSON(data).done((response) => {
      if (response.success) {
        $row.fadeOut(300, () => {
          $row.remove();
          paginateAndFilter();
        });
        showToast(translations.deleted_successfully || 'Item verwijderd!', 'success');

        // Bewaar undo data en toon toast
        undoData = { type: 'delete', id: id, html: deletedHtml };
        showUndoToast(() => undoDelete(undoData));
      } else {
        showToast(response.data.message || 'Fout bij verwijderen.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij verwijderen.', 'error');
    });
  });

  // Bulk verwijderen met undo
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

    const deletedHtmlMap = {};
    selectedIds.forEach(id => {
      deletedHtmlMap[id] = $(`[data-attachment-id="${id}"]`).closest('tr').prop('outerHTML');
    });

    const data = {
      action: 'bulk_delete_media',
      attachment_ids: selectedIds,
    };

    postJSON(data).done((response) => {
      if (response.success) {
        selectedIds.forEach((id) => {
          $(`[data-attachment-id="${id}"]`).closest('tr').fadeOut(300, function() {
            $(this).remove();
          });
        });
        showToast(translations.bulk_deleted_successfully || `${response.data.count} items succesvol verwijderd.`, 'success');
        $('#bulkSelectAll').prop('checked', false);
        paginateAndFilter();

        undoData = { type: 'bulk-delete', items: deletedHtmlMap };
        showUndoToast(() => undoBulkDelete(undoData));
      } else {
        showToast(response.data.message || 'Fout bij bulkverwijdering.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij bulkverwijdering.', 'error');
    });
  });

  // Undo functies
  function undoDelete(data) {
    if (!data || data.type !== 'delete') return;
    $tbody.prepend(data.html);
    paginateAndFilter();
    showToast('Verwijdering ongedaan gemaakt.', 'success');
  }

  function undoBulkDelete(data) {
    if (!data || data.type !== 'bulk-delete') return;
    for (const id in data.items) {
      $tbody.prepend(data.items[id]);
    }
    paginateAndFilter();
    showToast('Bulk verwijdering ongedaan gemaakt.', 'success');
  }

  // Selecteer/deselecteer alles checkbox
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

  // Event bindings voor filter, zoek en paginatie
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

  // Toast notificaties (inline, fadeout, ipv alert)
  function showToast(message, type = 'info') {
    const $toast = $('<div class="custom-toast" role="alert" aria-live="assertive"></div>').text(message).appendTo('body');
    $toast.addClass(`toast-${type}`);
    $toast.focus();
    setTimeout(() => {
      $toast.fadeOut(400, () => $toast.remove());
    }, 4000);
  }
});