'use strict';

jQuery(($) => {
	const $postTypeSelect = $('#post_type');
	const $pageSelect = $('#pages');
	const $form = $('.export-page-form');
	const $exportButton = $form.find('.export-button');
	const $spinner = $('#export-spinner');
	const $summary = $('#export-summary');
	const $checkboxes = $form.find('input[name="export_columns[]"]');
	const $exportOption = $('#export-option');

	const LOCAL_STORAGE_KEY = 'mim_export_profile';

	const initializePostType = () => {
		if (!$postTypeSelect.length) return;

		const selectedPostType = $postTypeSelect.val() || getFirstValidPostType();
		if (selectedPostType) {
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
		}
		return firstValidPostType ?? null;
	};

	const loadPagesByPostType = (postType) => {
		$.ajax({
			url: ajax_object?.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'get_pages_by_post_type',
				post_type: postType,
			},
			beforeSend: () => {
				$pageSelect
					.prop('disabled', true)
					.empty()
					.append(new Option('Laden...', ''));
			},
			success: (response) => {
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
					$pageSelect.append(new Option('Geen items gevonden', ''));
				}

				$pageSelect.prop('disabled', false);
			},
			error: () => {
				$pageSelect
					.empty()
					.append(new Option('Fout bij ophalen van items', ''))
					.prop('disabled', false);
			},
		});
	};

	if ($form.length) {
		$form.on('submit', (e) => {
			const selected = $checkboxes.filter(':checked').length;
			if (selected === 0) {
				e.preventDefault();
				alert('Selecteer minstens één kolom om te exporteren.');
				return false;
			}
			$exportButton.prop('disabled', true);
			$spinner.show();
		});
	}

	const formatBytes = (bytes) => {
		if (bytes === 0) return '0 bytes';
		const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB'];
		const i = Math.floor(Math.log(bytes) / Math.log(1024));
		const value = bytes / Math.pow(1024, i);
		return `${value.toFixed(1)} ${sizes[i]}`;
	};

	const updateSummary = () => {
		const selectedCols = $checkboxes.filter(':checked').length;
		const exportOption = $exportOption.val();

		if (selectedCols === 0) {
			$summary.text('⚠️ Selecteer minstens één kolom om te exporteren.');
			return;
		}

		$.ajax({
			url: ajax_object.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'get_export_row_count',
				export_option: exportOption,
			},
			success: (response) => {
				if (
					response?.success &&
					typeof response.data?.count === 'number'
				) {
					const rowCount = response.data.count;
					const sizeStr = response.data.total_size
						? formatBytes(response.data.total_size)
						: '?';
					$summary.text(
						`U exporteert ${rowCount} rij(en) met ${selectedCols} kolom(men), totaal ${sizeStr}.`
					);
				} else {
					$summary.text(`U exporteert ? rij(en) met ${selectedCols} kolom(men).`);
				}
			},
			error: () => {
				$summary.text(`U exporteert ? rij(en) met ${selectedCols} kolom(men).`);
			},
		});
	};

	const initSelectButtons = () => {
		$('#select-all-columns').on('click', (e) => {
			e.preventDefault();
			$checkboxes.prop('checked', true);
			updateSummary();
		});

		$('#deselect-all-columns').on('click', (e) => {
			e.preventDefault();
			$checkboxes.prop('checked', false);
			updateSummary();
		});

		$('#select-seo-columns').on('click', (e) => {
			e.preventDefault();
			const seoColumns = ['title', 'alt_text', 'description'];
			$checkboxes.each(function () {
				$(this).prop('checked', seoColumns.includes($(this).val()));
			});
			updateSummary();
		});
	};

	const initProfileButtons = () => {
		$('#save-export-profile').on('click', (e) => {
			e.preventDefault();
			const selected = $checkboxes
				.filter(':checked')
				.map(function () {
					return $(this).val();
				})
				.get();

			if (selected.length === 0) {
				alert('Selecteer minstens één kolom om op te slaan als profiel.');
				return;
			}

			localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(selected));
			alert('Exportprofiel opgeslagen.');
		});

		$('#load-export-profile').on('click', (e) => {
			e.preventDefault();
			const saved = localStorage.getItem(LOCAL_STORAGE_KEY);
			if (!saved) {
				alert('Er is geen opgeslagen profiel gevonden.');
				return;
			}
			const selected = JSON.parse(saved);
			$checkboxes.each(function () {
				$(this).prop('checked', selected.includes($(this).val()));
			});
			updateSummary();
		});

		$('#delete-export-profile').on('click', (e) => {
			e.preventDefault();
			localStorage.removeItem(LOCAL_STORAGE_KEY);
			alert('Exportprofiel verwijderd.');
		});
	};

	// Bind alle events
	if ($checkboxes.length && $summary.length) {
		$checkboxes.on('change', updateSummary);
	}
	if ($exportOption.length) {
		$exportOption.on('change', updateSummary);
	}

	initializePostType();
	updateSummary();
	initSelectButtons();
	initProfileButtons();
});