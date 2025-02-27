'use strict';

jQuery(document).ready(($) => {
    const $postTypeSelect = $('#post_type');
    const $pageSelect = $('#pages');

    /**
     * Initialiseer de post type selectie en laad de bijbehorende pagina's.
     */
    const initializePostType = () => {
        if ($postTypeSelect.length) {
            const selectedPostType = $postTypeSelect.val() || getFirstValidPostType();
            if (selectedPostType) {
                console.log(`Initialiseer post type: ${selectedPostType}`);
                loadPagesByPostType(selectedPostType);
            } else {
                $pageSelect.empty().append(new Option('Geen post type geselecteerd', ''));
            }
        }
    };

    /**
     * Haal het eerste geldige post type op.
     * @returns {string|null} Het eerste geldige post type of null.
     */
    const getFirstValidPostType = () => {
        const firstValidPostType = $postTypeSelect
            .find('option')
            .filter((_, option) => $(option).val())
            .first()
            .val();
        if (firstValidPostType) {
            $postTypeSelect.val(firstValidPostType);
            console.log(`Eerste geldige post type geselecteerd: ${firstValidPostType}`);
        }
        return firstValidPostType || null;
    };

    /**
     * Event listener voor verandering van post type selectie.
     */
    $postTypeSelect.on('change', function () {
        const selectedPostType = $(this).val();
        if (selectedPostType) {
            console.log(`Post type veranderd naar: ${selectedPostType}`);
            loadPagesByPostType(selectedPostType);
        } else {
            $pageSelect.empty().append(new Option('Selecteer een post type', ''));
        }
    });

    /**
     * Laad pagina's op basis van het geselecteerde post type via AJAX.
     * @param {string} postType - Het geselecteerde post type.
     */
    const loadPagesByPostType = (postType) => {
        console.log(`Post type verzonden: ${postType}`);

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_pages_by_post_type',
                post_type: postType,
            },
            beforeSend: () => {
                console.log("Laden van pagina's...");
                $pageSelect.prop('disabled', true)
                    .empty()
                    .append(new Option('Laden...', ''));
            },
            success: (response) => {
                console.log('AJAX-respons ontvangen:', response);

                $pageSelect.empty();

                if (response.success && Array.isArray(response.data.pages)) {
                    if (response.data.pages.length > 0) {
                        response.data.pages.forEach((page) => {
                            $pageSelect.append(new Option(page.title, page.id));
                        });
                    } else {
                        $pageSelect.append(new Option('Geen items gevonden', ''));
                    }
                } else {
                    console.warn('Onverwachte respons of geen data ontvangen.');
                    $pageSelect.append(new Option('Geen items gevonden', ''));
                }

                $pageSelect.prop('disabled', false);
            },
            error: (xhr, status, error) => {
                console.error(`AJAX-fout: ${status}, ${error}`);
                $pageSelect.empty().append(new Option('Fout bij ophalen van items', ''));
                $pageSelect.prop('disabled', false);
            },
        });
    };

    // Initialiseer bij het laden van de pagina
    initializePostType();
});