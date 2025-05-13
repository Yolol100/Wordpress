'use strict';

jQuery(($) => {
  // Bulk-select All functionality
  $('#bulkSelectAll').on('change', function () {
    const checked = this.checked;
    $('.bulk-delete-checkbox').prop('checked', checked);
  });

  // Bulk-delete handler
  const runBulkDelete = () => {
    const ids = $('.bulk-delete-checkbox:checked')
      .map((_, el) => $(el).data('attachment-id'))
      .get();
    if (!ids.length) {
      alert('Selecteer eerst minstens één item.');
      return;
    }
    if (!confirm('Weet je zeker dat je de geselecteerde media wilt verwijderen?')) {
      return;
    }
    $.post(modernImageManagerAjax.ajax_url, {
      action: 'bulk_delete_media',
      attachment_ids: ids
    }, (response) => {
      if (response.success) {
        location.reload();
      } else {
        alert('Fout bij bulk verwijderen: ' + (response.data?.message ?? 'Onbekende fout'));
      }
    });
  };
  $('#bulkDeleteTop, #bulkDeleteBottom').on('click', runBulkDelete);

  window.currentPage = 1;

  window.updatePagination = () => {
    const rowsPerPage = parseInt($('#rowsPerPage').val(), 10);
    const $filteredRows = $('.unique-tbody tr.filtered-match');
    const totalRows = $filteredRows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

    $('#totalPages, #totalPagesBottom').text(totalPages);
    $('.unique-tbody tr').hide();

    const startIndex = (window.currentPage - 1) * rowsPerPage;
    $filteredRows.slice(startIndex, startIndex + rowsPerPage).show();

    $('#currentPage, #currentPageBottom').text(window.currentPage);
    $('#prevPage, #prevPageBottom').prop('disabled', window.currentPage === 1);
    $('#nextPage, #nextPageBottom').prop('disabled', window.currentPage === totalPages);

    document.getElementById("noResults").style.display = $filteredRows.length === 0 ? "block" : "none";
  };

  window.filterTable = () => {
    const inputHeader = document.getElementById("search-input")?.value || "";
    const inputFooter = document.getElementById("search-input-bottom")?.value || "";
    const input = (inputHeader || inputFooter).trim().toLowerCase();
    let resultsFound = false;

    const selectedFilter = $('#pageFilter').val() || $('#pageFilterBottom').val();
    const $filteredRows = $('.unique-tbody tr').filter(function () {
      const status = $(this).data('status');
      return selectedFilter === 'all' || selectedFilter === status;
    });

    if (input === "") {
      $filteredRows.each(function () {
        $(this).addClass("filtered-match");
      });
      document.getElementById("noResults").style.display = "none";
    } else {
      $filteredRows.each(function () {
        const bestandsnaam        = $(this).find('.filename-input').val()             || '';
        const nieuweBestandsnaam  = $(this).find('.new-filename-input').val()         || '';
        const pagina              = $(this).find('.unique-td-pagina').text()          || '';
        const titel               = $(this).find('.title-input').val()                || '';
        const caption             = $(this).find('.caption-input').val()              || '';
        const description         = $(this).find('.description-input').val()          || '';
        const alt                 = $(this).find('.alt-input').val()                  || '';
        const rowText = `${bestandsnaam} ${nieuweBestandsnaam} ${pagina} ${titel} ${caption} ${description} ${alt}`.toLowerCase();

        if (rowText.includes(input)) {
          $(this).addClass("filtered-match");
          resultsFound = true;
        } else {
          $(this).removeClass("filtered-match");
        }
      });

      document.getElementById("noResults").style.display = resultsFound ? "none" : "block";
    }

    window.currentPage = 1;
    window.updatePagination();
  };

  const applyFilterFunction = (filterSelector) => {
    const selectedFilter = $(filterSelector).val();
    $('.unique-tbody tr').each(function () {
      const $row = $(this);
      const status = $row.data('status');
      $row.toggleClass('filtered-match', selectedFilter === 'all' || selectedFilter === status);
    });

    $('#search-input, #search-input-bottom').val('');
    document.getElementById("noResults").style.display = "none";
    window.currentPage = 1;
    window.updatePagination();
  };

  $('#applyFilter').on('click', () => applyFilterFunction('#pageFilter'));
  $('#applyFilterBottom').on('click', () => applyFilterFunction('#pageFilterBottom'));

  $('#apply-filters').on('click', () => {
    const titleFilter   = $('#filter-title').val().toLowerCase();
    const captionFilter = $('#filter-caption').val().toLowerCase();
    const altFilter     = $('#filter-alt').val().toLowerCase();

    $('.unique-tbody tr').each(function () {
      const $row   = $(this);
      const title  = $row.find('.title-input').val().toLowerCase();
      const caption = $row.find('.caption-input').val().toLowerCase();
      const alt     = $row.find('.alt-input').val().toLowerCase();
      const isMatch = (!titleFilter || title.includes(titleFilter)) &&
                      (!captionFilter || caption.includes(captionFilter)) &&
                      (!altFilter || alt.includes(altFilter));
      $row.toggleClass('filtered-match', isMatch);
    });

    window.currentPage = 1;
    window.updatePagination();
  });

  $('#clearFilter, #clear-filters').on('click', () => {
    $('#pageFilter, #pageFilterBottom').val('all');
    $('#filter-title, #filter-caption, #filter-alt, #search-input, #search-input-bottom').val('');
    $('.unique-tbody tr').addClass('filtered-match').show();
    window.currentPage = 1;
    window.updatePagination();
  });

  $('#rowsPerPage, #rowsPerPageBottom').on('change', function () {
    const rowsPerPage = $(this).val();
    $('#rowsPerPage, #rowsPerPageBottom').val(rowsPerPage);
    window.currentPage = 1;
    window.updatePagination();
  });

  $('.save-meta').off('click').on('click', function () {
    const $button = $(this);
    const $row    = $button.closest('tr');
    $row.removeClass('success error');

    const attachmentId   = $button.data('attachment-id');
    const newTitle       = $row.find('.title-input').val();
    const newCaption     = $row.find('.caption-input').val();
    const newDescription = $row.find('.description-input').val();
    const newAlt         = $row.find('.alt-input').val();
    const newFilename    = $row.find('.new-filename-input').val();

    if (!modernImageManagerAjax?.ajax_url) {
      alert('AJAX URL is niet beschikbaar!');
      return;
    }

    $button.prop('disabled', true);

    $.ajax({
      url: modernImageManagerAjax.ajax_url,
      method: 'POST',
      data: {
        action: 'update_media_meta',
        attachment_id: attachmentId,
        title: newTitle,
        caption: newCaption,
        description: newDescription,
        alt: newAlt,
        new_filename: newFilename,
      },
      success: (response) => {
        $button.prop('disabled', false);
        if (response.success) {
          $row.addClass('success');
          setTimeout(() => $row.removeClass('success'), 2000);
          if (newFilename) {
            $row.find('.filename-input').val(newFilename);
          }
          alert("Succes: " + (response.data?.message ?? "Actie succesvol uitgevoerd."));
        } else {
          $row.addClass('error');
          setTimeout(() => $row.removeClass('error'), 2000);
          alert("Fout: " + (response.data?.message ?? "Onbekende fout."));
        }
      },
      error: (xhr, status, error) => {
        console.error('AJAX Error:', status, error);
        $button.prop('disabled', false);
        $row.addClass('error');
        setTimeout(() => $row.removeClass('error'), 2000);
        alert('Er is een fout opgetreden bij het verzenden van het verzoek.');
      },
    });
  });

  $('.delete-media').off('click').on('click', function () {
    if (!confirm('Weet u zeker dat u deze media wilt verwijderen?')) return;

    const $button       = $(this);
    const $row          = $button.closest('tr');
    const attachmentId  = $button.data('attachment-id');

    if (!modernImageManagerAjax?.ajax_url) {
      alert('AJAX URL is niet beschikbaar!');
      return;
    }

    $button.prop('disabled', true);

    $.ajax({
      url: modernImageManagerAjax.ajax_url,
      method: 'POST',
      data: {
        action: 'delete_media',
        attachment_id: attachmentId,
      },
      success: (response) => {
        if (response.success) {
          $row.fadeOut(500, () => {
            $row.remove();
            window.updatePagination();
          });
        } else {
          alert("Fout: " + (response.data?.message ?? "Onbekende fout bij verwijderen."));
          $button.prop('disabled', false);
        }
      },
      error: (xhr, status, error) => {
        console.error('AJAX Error:', status, error);
        alert('Er is een fout opgetreden bij het verwijderen.');
        $button.prop('disabled', false);
      },
    });
  });

  $('#nextPage, #nextPageBottom').on('click', () => {
    window.currentPage++;
    window.updatePagination();
  });
  $('#prevPage, #prevPageBottom').on('click', () => {
    window.currentPage--;
    window.updatePagination();
  });

  $('.unique-tbody tr').addClass('filtered-match');
  window.updatePagination();
});