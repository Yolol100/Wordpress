jQuery(document).ready(($) => {
  // Filter toepassen
  $('#applyFilter').on('click', () => {
    const selectedFilter = $('#pageFilter').val();
    $('.unique-tbody tr').each(function () {
      const $row = $(this);
      const status = $row.data('status');
      $row.toggle(selectedFilter === 'all' || selectedFilter === status);
    });
  });

  // Filters toepassen op specifieke velden
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

  // Filters wissen
  $('#clearFilter, #clear-filters').on('click', () => {
    $('#pageFilter').val('all');
    $('#filter-title, #filter-caption, #filter-alt').val('');
    $('.unique-tbody tr').show();
  });

  // Opslaan van metadata
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

  // Verwijderen van media
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
          });
        } else {
          alert(
            response.data && response.data.message
              ? `Fout: ${response.data.message}`
              : 'Er is een onbekende fout opgetreden bij het verwijderen.'
          );
        }
        $button.prop('disabled', false);
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
  const $rowsPerPage = $('#rowsPerPage');
  const $currentPageDisplay = $('#currentPage');
  const $totalPagesDisplay = $('#totalPages');

  const updatePagination = () => {
    const rowsPerPage = parseInt($rowsPerPage.val(), 10);
    const totalRows = $rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    $totalPagesDisplay.text(totalPages);

    $rows.hide();
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    $rows.slice(startIndex, endIndex).show();

    $currentPageDisplay.text(currentPage);

    $('#prevPage').prop('disabled', currentPage === 1);
    $('#nextPage').prop('disabled', currentPage === totalPages);
  };

  $rowsPerPage.on('change', () => {
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

  updatePagination();
});