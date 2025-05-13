'use strict';

jQuery(($) => {
	const $postTypeSelect = $('#post_type');
	const $pageSelect = $('#pages');

	const initializePostType = () => {
		if (!$postTypeSelect.length) return;

		const selectedPostType = $postTypeSelect.val() || getFirstValidPostType();
		if (selectedPostType) {
			console.log(`Initialiseer post type: ${selectedPostType}`);
			loadPagesByPostType(selectedPostType);
		} else {
			$pageSelect.empty().append(new Option('Geen post type geselecteerd', ''));
		}
	};

	const getFirstValidPostType = () => {
		const firstValidPostType = $postTypeSelect
			.find('option')
			.filter((_, el) => $(el).val())
			.first()
			.val();

		if (firstValidPostType) {
			$postTypeSelect.val(firstValidPostType);
			console.log(`Eerste geldige post type geselecteerd: ${firstValidPostType}`);
		}
		return firstValidPostType ?? null;
	};

	$postTypeSelect.on('change', (e) => {
		const selectedPostType = $(e.currentTarget).val();
		if (selectedPostType) {
			console.log(`Post type veranderd naar: ${selectedPostType}`);
			loadPagesByPostType(selectedPostType);
		} else {
			$pageSelect.empty().append(new Option('Selecteer een post type', ''));
		}
	});

	const loadPagesByPostType = (postType) => {
		console.log(`Post type verzonden: ${postType}`);

		$.ajax({
			url: ajax_object?.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'get_pages_by_post_type',
				post_type: postType,
			},
			beforeSend: () => {
				console.log('Laden van pagina\'s...');
				$pageSelect
					.prop('disabled', true)
					.empty()
					.append(new Option('Laden...', ''));
			},
			success: (response) => {
				console.log('AJAX-respons ontvangen:', response);

				$pageSelect.empty();

				if (response?.success && Array.isArray(response.data?.pages)) {
					if (response.data.pages.length) {
						response.data.pages.forEach(({ title, id }) => {
							$pageSelect.append(new Option(title, id));
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
				$pageSelect
					.empty()
					.append(new Option('Fout bij ophalen van items', ''))
					.prop('disabled', false);
			},
		});
	};

	initializePostType();
});