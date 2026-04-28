'use strict';

jQuery(($) => {
  const { ajax_url: ajaxUrl, nonce, translations } = ajax_object;
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

  function detectDarkMode() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      $('body').addClass('dark-mode');
    } else {
      $('body').removeClass('dark-mode');
    }
  }

  detectDarkMode();
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', detectDarkMode);
  }

  const postJSON = (data) => $.post(ajaxUrl, data, null, 'json');

  $(document).on('click', '.media-items-save-meta', ({ currentTarget }) => {
    const id = $(currentTarget).data('attachment-id');
    const data = {
      action: 'mim_update_media_meta',
      nonce: nonce,
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

  $(document).on('click', '.media-items-delete-media', ({ currentTarget }) => {
    if (!confirm(translations.confirm_delete || 'Weet je zeker dat je dit item definitief wilt verwijderen?')) {
      return;
    }

    const id = $(currentTarget).data('attachment-id');
    const $row = $(`[data-attachment-id="${id}"]`).closest('tr');
    const data = {
      action: 'mim_delete_media',
      nonce: nonce,
      attachment_id: id,
    };

    postJSON(data).done((response) => {
      if (response.success) {
        $row.fadeOut(300, () => {
          $row.remove();
          paginateAndFilter();
        });
        showToast(translations.deleted_successfully || 'Item verwijderd!', 'success');
      } else {
        showToast(response.data.message || 'Fout bij verwijderen.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij verwijderen.', 'error');
    });
  });

  $('#bulkDeleteTop, #bulkDeleteBottom').on('click', () => {
    const selectedIds = $('.bulk-delete-checkbox:checked').map(function () {
      return $(this).data('attachment-id');
    }).get();

    if (selectedIds.length === 0) {
      showToast(translations.no_items_selected || 'Geen items geselecteerd voor bulkactie.', 'info');
      return;
    }

    if (!confirm(translations.confirm_bulk_delete || `Weet je zeker dat je ${selectedIds.length} geselecteerde items definitief wilt verwijderen?`)) {
      return;
    }

    const data = {
      action: 'mim_bulk_delete_media',
      nonce: nonce,
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
      } else {
        showToast(response.data.message || 'Fout bij bulkverwijdering.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij bulkverwijdering.', 'error');
    });
  });

  $('#bulkSelectAll').on('change', function () {
    $('.bulk-delete-checkbox').prop('checked', $(this).prop('checked'));
  });

  function paginateAndFilter() {
    const filterTop = $pageFilter.val();
    const filterBottom = $pageFilterB.val();
    const searchTop = typeof $searchTop.val() === 'string' ? $searchTop.val().toLowerCase().trim() : '';
    const searchBottom = typeof $searchBot.val() === 'string' ? $searchBot.val().toLowerCase().trim() : '';
    const perPage = Number($perPageTop.val());
    const $allRows = $tbody.find('.media-items-tr-item');
    let matchedRows = [];

    $allRows.each(function () {
      const $r = $(this);
      const rowRawText = $r.text();
      const rowText = typeof rowRawText === 'string' ? rowRawText.toLowerCase() : '';
      const status = $r.data('status');
      const activeSearchTerm = searchTop || searchBottom;

      if ((filterTop === 'all' || status === filterTop) &&
          (filterBottom === 'all' || status === filterBottom) &&
          rowText.includes(activeSearchTerm)) {
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

  $('#applyFilter, #applyFilterBottom').on('click', () => {
    currentPage = 1;
    paginateAndFilter();
  });

  $('#search-input, #search-input-bottom').on('input', () => {
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

  paginateAndFilter();

  function showToast(message, type = 'info') {
    const $toast = $('<div class="custom-toast" role="alert" aria-live="assertive"></div>').text(message).appendTo('body');
    $toast.addClass(`toast-${type}`);
    $toast.focus();
    setTimeout(() => {
      $toast.fadeOut(400, () => $toast.remove());
    }, 4000);
  }
});
