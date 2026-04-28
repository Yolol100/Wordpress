jQuery(document).ready(function($) {
    $('#mim-zip-export-form').on('submit', function(e) {
        e.preventDefault();

        var $button = $('#mim-zip-export-button');
        var $spinner = $('#mim-zip-export-spinner');
        var $result = $('#mim-zip-export-result');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.text('');

        $.ajax({
            url: mim_ajax.ajax_url,
            type: 'POST',
            data: $(this).serialize(),
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                var filename = "";
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    var matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                }
                var blob = new Blob([data], {type: 'application/zip'});
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename || 'media-export.zip';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.text(mim_ajax.download_started_text);
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.text(mim_ajax.error_text);
            }
        });
    });
});