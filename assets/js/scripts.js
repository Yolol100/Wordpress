'use strict';

jQuery(($) => {
  const { ajax_url: ajaxUrl, translations } = ajax_object;
  let currentPage = 1;

  const $tbody        = $('.media-items-tbody');
  const $noResults    = $('#noResults'); // Dit is de 'Geen resultaten gevonden' melding
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
      alt_text: $(`[data-attachment-id="${id}"].media-items-alt-text-input`).val(),
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

  // Hernoemen bestand
  $(document).on('click', '.media-items-rename-file', ({ currentTarget }) => {
    const id = $(currentTarget).data('attachment-id');
    const newFilename = $(`[data-attachment-id="${id}"].media-items-filename-input`).val();

    if (!newFilename) {
      showToast('Bestandsnaam mag niet leeg zijn.', 'error');
      return;
    }

    const data = {
      action: 'rename_file',
      attachment_id: id,
      new_filename: newFilename,
    };

    postJSON(data).done((response) => {
      if (response.success) {
        showToast(translations.renamed_successfully || 'Bestand hernoemd!', 'success');
        // Optioneel: herlaad de rij of de hele tabel als de URL verandert
        // Voor nu, de GUID wordt bijgewerkt in de PHP, dus een harde refresh is misschien nodig
        // of de tabel opnieuw renderen via AJAX.
        // Eenvoudige oplossing: update de URL in de UI (als die getoond wordt) en de post_name
        // De `paginateAndFilter` kan de zichtbare data bijwerken.
        paginateAndFilter();
      } else {
        showToast(response.data.message || 'Fout bij hernoemen.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij hernoemen.', 'error');
    });
  });

  // Verwijderen media
  $(document).on('click', '.media-items-delete', ({ currentTarget }) => {
    if (!confirm(translations.confirm_delete || 'Weet je zeker dat je dit item wilt verwijderen?')) {
      return;
    }

    const id = $(currentTarget).data('attachment-id');
    const data = {
      action: 'delete_media',
      attachment_id: id,
    };

    postJSON(data).done((response) => {
      if (response.success) {
        $(`[data-attachment-id="${id}"]`).closest('tr').remove();
        showToast(translations.deleted_successfully || 'Item verwijderd!', 'success');
        paginateAndFilter(); // Herfilter en herpaginatie na verwijdering
      } else {
        showToast(response.data.message || 'Fout bij verwijderen.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij verwijderen.', 'error');
    });
  });

  // Bulk verwijderen
  $('#bulkDeleteTop, #bulkDeleteBottom').on('click', () => {
    const selectedIds = $('.bulk-select:checked').map(function () {
      return $(this).val();
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
    };

    postJSON(data).done((response) => {
      if (response.success) {
        selectedIds.forEach((id) => {
          $(`[data-attachment-id="${id}"]`).closest('tr').remove();
        });
        showToast(translations.bulk_deleted_successfully || `${response.data.count} items succesvol verwijderd.`, 'success');
        $('.bulk-select-all').prop('checked', false); // Deselecteer de 'alles selecteren' checkbox
        paginateAndFilter(); // Herfilter en herpaginatie na bulkverwijdering
      } else {
        showToast(response.data.message || 'Fout bij bulkverwijdering.', 'error');
      }
    }).fail(() => {
      showToast('Netwerkfout bij bulkverwijdering.', 'error');
    });
  });

  // Selecteer/deselecteer alles
  $('.bulk-select-all').on('change', function () {
    $('.bulk-select').prop('checked', $(this).prop('checked'));
  });

  // Filter en paginatie logica
  function paginateAndFilter() {
    const filterTop = $pageFilter.val();
    const filterBottom = $pageFilterB.val();
    const searchTop = $searchTop.val().toLowerCase().trim();
    const searchBottom = $searchBot.val().toLowerCase().trim();
    const perPage = Number($perPageTop.val()); // Aanname dat beide selecteren dezelfde waarde hebben

    const $allRows = $tbody.find('.media-items-tr-item');
    let matchedRows = [];

    $allRows.each(function () {
      const $r = $(this);
      const rowText = $r.text().toLowerCase();
      const status = $r.data('status'); // 'linked' of 'unlinked'

      const matchesFilterTop = (filterTop === 'all' || status === filterTop);
      const matchesFilterBottom = (filterBottom === 'all' || status === filterBottom);

      // Gebruik hier de actieve zoekbalkwaarde. Als top leeg is, gebruik dan bottom, andersom.
      // Als beide leeg zijn, dan is de zoekterm ook leeg.
      const activeSearchTerm = searchTop || searchBottom;
      const matchesSearch = rowText.includes(activeSearchTerm);

      if (matchesFilterTop && matchesFilterBottom && matchesSearch) {
        matchedRows.push(this);
      }
    });

    // Toon "Geen resultaten gevonden" als er geen gematchte rijen zijn
    if (matchedRows.length === 0) {
      $noResults.show(); // Toon de melding
      $tbody.hide(); // Verberg de tabel body
      // Verberg ook de paginatie en bulk acties als er geen resultaten zijn
      $('.pagination-navigation').hide();
      $('.bulk-delete-controls').hide();
    } else {
      $noResults.hide(); // Verberg de melding
      $tbody.show(); // Toon de tabel body
      // Toon de paginatie en bulk acties weer
      $('.pagination-navigation').show();
      $('.bulk-delete-controls').show();
    }

    const totalPages = Math.max(1, Math.ceil(matchedRows.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    $curPage.text(currentPage);
    $totPage.text(totalPages);

    // Toon alleen de rijen voor de huidige pagina
    $allRows.hide(); // Eerst alles verbergen
    $(matchedRows).slice((currentPage - 1) * perPage, currentPage * perPage).show();
  }

  // Event bindings
  $('#applyFilter, #applyFilterBottom').on('click', () => {
    currentPage = 1;
    paginateAndFilter();
  });

  // **NIEUWE TOEVOEGING:** Luister naar de 'input' event op de zoekbalken
  $('#search-input, #search-input-bottom').on('input', function() {
    currentPage = 1; // Reset de paginering wanneer er gezocht wordt
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
    $perPageTop.val(v); // Synchroniseer beide dropdowns
    $perPageBot.val(v);
    currentPage = 1;
    paginateAndFilter();
  });

  // Initial call om de tabel te laden en te filteren bij het laden van de pagina
  paginateAndFilter();

  // Toast melding functie (als deze nog niet bestaat)
  function showToast(message, type = 'info') {
    const toast = $('#mim-toast'); // Zorg ervoor dat er een element met ID 'mim-toast' is in je HTML
    if (toast.length === 0) {
      console.warn("Toast element #mim-toast niet gevonden. Maak dit aan in admin-table.php");
      return;
    }
    toast.text(message).removeClass('success error info').addClass(type).fadeIn(300).delay(3000).fadeOut(300);
  }
});