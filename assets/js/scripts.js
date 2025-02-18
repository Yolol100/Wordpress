jQuery(document).ready(($) => {
  // Dynamische filtering op de bovenste balk
  $('#applyFilter').on('click', () => {
    const selectedFilter = $('#pageFilter').val();
    $('.unique-tbody tr').each(function () {
      const $row = $(this);
      const status = $row.data('status');
      // Als 'all' of als de status overeenkomt, tonen; anders verbergen.
      $row.toggle(selectedFilter === 'all' || selectedFilter === status);
    });
  });

  // Dynamische filtering op de onderste balk
  $('#applyFilterBottom').on('click', () => {
    const selectedFilter = $('#pageFilterBottom').val();
    $('.unique-tbody tr').each(function () {
      const $row = $(this);
      const status = $row.data('status');
      $row.toggle(selectedFilter === 'all' || selectedFilter === status);
    });
  });

  // Filtering op specifieke tekstvelden (titel, caption, alt)
  $('#apply-filters').on('click', () => {
    const titleFilter = $('#filter-title').val().toLowerCase();
    const captionFilter = $('#filter-caption').val().toLowerCase();
    const altFilter = $('#filter-alt').val().toLowerCase();

    $('.unique-tbody tr').each(function () {
      const $row = $(this);
      const title = $row.find('.title-input').val().toLowerCase();
      const caption = $row.find('.caption-input').val().toLowerCase();
      const alt = $row.find('.alt-input').val().toLowerCase();

      const isMatch =
        (!titleFilter || title.includes(titleFilter)) &&
        (!captionFilter || caption.includes(captionFilter)) &&
        (!altFilter || alt.includes(altFilter));

      $row.toggle(isMatch);
    });
  });

  // Filters wissen voor beide balken
  $('#clearFilter, #clear-filters').on('click', () => {
    $('#pageFilter, #pageFilterBottom').val('all');
    $('#filter-title, #filter-caption, #filter-alt').val('');
    $('.unique-tbody tr').show();
  });

  // Opslaan van metadata via AJAX
  $('.save-meta').off('click').on('click', function () {
    const $button = $(this);
    const $row = $button.closest('tr');
    $row.removeClass('success error');

    const attachmentId = $button.data('attachment-id');
    const newTitle = $row.find('.title-input').val();
    const newCaption = $row.find('.caption-input').val();
    const newDescription = $row.find('.description-input').val();
    const newAlt = $row.find('.alt-input').val();
    const newFilename = $row.find('.new-filename-input').val();

    if (typeof modernImageManagerAjax === 'undefined' || !modernImageManagerAjax.ajax_url) {
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
        } else {
          $row.addClass('error');
          setTimeout(() => $row.removeClass('error'), 2000);
          const message =
            response.data && response.data.message
              ? `Fout: ${response.data.message}`
              : 'Er is een onbekende fout opgetreden.';
          alert(message);
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

  // Verwijderen van media via AJAX zonder paginareload
  $('.delete-media').off('click').on('click', function () {
    if (!confirm('Weet u zeker dat u deze media wilt verwijderen?')) {
      return;
    }
    const $button = $(this);
    const $row = $button.closest('tr');
    const attachmentId = $button.data('attachment-id');

    if (typeof modernImageManagerAjax === 'undefined' || !modernImageManagerAjax.ajax_url) {
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
          $row.fadeOut(500, function () {
            $(this).remove();
            updatePagination();
          });
        } else {
          alert(
            response.data && response.data.message
              ? `Fout: ${response.data.message}`
              : 'Er is een onbekende fout opgetreden bij het verwijderen.'
          );
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

  // Paginering
  let currentPage = 1;
  const $rows = $('.unique-tbody tr');

  const updatePagination = () => {
    const rowsPerPage = parseInt($('#rowsPerPage').val(), 10);
    const totalRows = $rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    $('#totalPages, #totalPagesBottom').text(totalPages);

    $rows.hide();
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    $rows.slice(startIndex, endIndex).show();

    $('#currentPage, #currentPageBottom').text(currentPage);

    $('#prevPage, #prevPageBottom').prop('disabled', currentPage === 1);
    $('#nextPage, #nextPageBottom').prop('disabled', currentPage === totalPages);
  };

  // Evenementen voor paginering (bovenste balk)
  $('#rowsPerPage').on('change', () => {
    currentPage = 1;
    updatePagination();
  });
  $('#nextPage').on('click', () => {
    currentPage++;
    updatePagination();
  });
  $('#prevPage').on('click', () => {
    currentPage--;
    updatePagination();
  });

  // Evenementen voor paginering (onderste balk)
  $('#rowsPerPageBottom').on('change', () => {
    currentPage = 1;
    updatePagination();
  });
  $('#nextPageBottom').on('click', () => {
    currentPage++;
    updatePagination();
  });
  $('#prevPageBottom').on('click', () => {
    currentPage--;
    updatePagination();
  });

  updatePagination();
});