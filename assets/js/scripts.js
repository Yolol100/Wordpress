'use strict';

jQuery(($) => {
  // Zorg dat deze code alleen draait op de ZIP export pagina
  if (!$('.mim-zip-container').length) return;

  // Elementen met nieuwe klassen
  const $form    = $('#mim-zip-form');
  const $button  = $('#mim-zip-export-button');
  const $spinner = $('#mim-zip-export-spinner');
  const $result  = $('#mim-zip-export-result');

  // AJAX URL & teksten via wp_localize_script
  const ajaxUrl = typeof mim_ajax !== 'undefined' ? mim_ajax.ajax_url : '';
  const downloadText = typeof mim_ajax !== 'undefined' ? mim_ajax.download_started_text : 'Download gestart!';
  const errorText = typeof mim_ajax !== 'undefined' ? mim_ajax.error_text : 'Er is een fout opgetreden tijdens het maken van het ZIP-bestand.';

  $form.on('submit', function (e) {
    e.preventDefault();

    $button.prop('disabled', true);
    $spinner.addClass('is-active');
    $result.text('');

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: $form.serialize(),
      xhrFields: {
        responseType: 'blob'
      },
      success: function (data, status, xhr) {
        let filename = '';
        const disposition = xhr.getResponseHeader('Content-Disposition');
        if (disposition && disposition.indexOf('attachment') !== -1) {
          const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
          const matches = filenameRegex.exec(disposition);
          if (matches && matches[1]) filename = matches[1].replace(/['"]/g, '');
        }
        const blob = new Blob([data], { type: 'application/zip' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename || 'media-export.zip';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        $button.prop('disabled', false);
        $spinner.removeClass('is-active');
        $result.text(downloadText);
      },
      error: function () {
        $button.prop('disabled', false);
        $spinner.removeClass('is-active');
        $result.text(errorText);
      }
    });
  });
});