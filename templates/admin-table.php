<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

$media_items = modern_image_manager_get_media_items();
?>

<!-- Filter bovenaan -->
<div class="wrap unique-wrap filter-top-container">
  <div class="filter-header filter-top-header">
    <div class="nav-controls filter-top-nav-controls">
      <select id="pageFilter" class="page-filter-select filter-top-page-filter">
        <option value="all"><?php echo esc_html__('Alle pagina\'s', 'modern-image-manager'); ?></option>
        <option value="linked"><?php echo esc_html__('Gekoppelde pagina\'s', 'modern-image-manager'); ?></option>
        <option value="unlinked"><?php echo esc_html__('Niet gekoppelde pagina\'s', 'modern-image-manager'); ?></option>
      </select>
      <button id="applyFilter" class="page-button filter-top-apply-button">
        <?php echo esc_html__('Filter toepassen', 'modern-image-manager'); ?>
      </button>
      <div class="pagination-navigation filter-top-pagination">
        <button id="prevPage" class="page-button filter-top-prev-button"><?php echo esc_html__('Vorige', 'modern-image-manager'); ?></button>
        <span id="currentPage" class="filter-top-current-page">1</span> /
        <span id="totalPages" class="filter-top-total-pages">50</span>
        <button id="nextPage" class="page-button filter-top-next-button"><?php echo esc_html__('Volgende', 'modern-image-manager'); ?></button>
      </div>
    </div>

    <!-- Bulk-delete knop bovenaan -->
    <div class="bulk-delete-controls filter-top-bulk-delete">
      <button id="bulkDeleteTop" class="page-button bulk-delete-button">
        <?php echo esc_html__('Verwijder geselecteerde', 'modern-image-manager'); ?>
      </button>
    </div>

    <div class="search-container filter-top-search-container">
      <input
        type="text"
        id="search-input"
        class="search-box filter-top-search-box"
        placeholder="<?php echo esc_attr__('Zoeken...', 'modern-image-manager'); ?>"
      >
    </div>

    <div class="rows-controls filter-top-rows-controls">
      <label for="rowsPerPage" class="filter-top-rows-per-page-label">
        <?php echo esc_html__('Rijen per pagina:', 'modern-image-manager'); ?>
      </label>
      <select id="rowsPerPage" class="rows-per-page-select filter-top-rows-per-page-select">
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="30" selected>30</option>
        <option value="40">40</option>
        <option value="50">50</option>
      </select>
    </div>
  </div>
</div>

<table class="wp-list-table widefat fixed striped unique-table media-items-table">
  <thead class="unique-thead media-items-thead">
    <tr class="unique-tr-head media-items-tr-head">
      <th><input type="checkbox" id="bulkSelectAll" /></th>
      <?php
      $headers = [
        'Bestandsnaam', 'Nieuwe bestandsnaam', 'Pagina',
        'Titel', 'Caption', 'Description', 'Alt-tag',
        'Opslaan', 'Verwijderen'
      ];
      foreach ($headers as $header) {
        echo '<th>' . esc_html__($header, 'modern-image-manager') . '</th>';
      }
      ?>
    </tr>
  </thead>
  <tbody class="unique-tbody media-items-tbody">
    <?php if ($media_items->have_posts()) : ?>
      <?php while ($media_items->have_posts()) : $media_items->the_post(); ?>
        <?php
        $id       = (int) get_the_ID();
        $post     = get_post($id);
        if (! $post) {
          continue;
        }
        $alt      = get_post_meta($id, '_wp_attachment_image_alt', true);
        $caption  = $post->post_excerpt;
        $desc     = $post->post_content;
        $title    = $post->post_title;
        $page     = modern_image_manager_get_attached_post_title($id);
        $filename = pathinfo(get_attached_file($id), PATHINFO_FILENAME);
        $status   = str_contains($page, 'Niet gekoppeld') ? 'unlinked' : 'linked';
        ?>
        <tr
          class="unique-tr-item media-items-tr-item unique-tr-<?php echo esc_attr($id); ?>"
          data-status="<?php echo esc_attr($status); ?>"
        >
          <td>
            <input
              type="checkbox"
              class="bulk-delete-checkbox"
              data-attachment-id="<?php echo esc_attr($id); ?>"
            />
          </td>
          <td>
            <input
              type="text"
              class="filename-input media-items-filename-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              value="<?php echo esc_attr($filename); ?>"
              readonly
            >
          </td>
          <td>
            <input
              type="text"
              class="new-filename-input media-items-new-filename-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              placeholder="<?php esc_attr_e('Voer nieuwe bestandsnaam in', 'modern-image-manager'); ?>"
            >
          </td>
          <td><?php echo esc_html($page); ?></td>
          <td>
            <input
              type="text"
              class="title-input media-items-title-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              value="<?php echo esc_attr($title); ?>"
              placeholder="<?php esc_attr_e('Voer titel in', 'modern-image-manager'); ?>"
            >
          </td>
          <td>
            <input
              type="text"
              class="caption-input media-items-caption-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              value="<?php echo esc_attr($caption); ?>"
              placeholder="<?php esc_attr_e('Voer caption in', 'modern-image-manager'); ?>"
            >
          </td>
          <td>
            <textarea
              class="description-input media-items-description-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              placeholder="<?php esc_attr_e('Voer description in', 'modern-image-manager'); ?>"
            ><?php echo esc_textarea($desc); ?></textarea>
          </td>
          <td>
            <input
              type="text"
              class="alt-input media-items-alt-input"
              data-attachment-id="<?php echo esc_attr($id); ?>"
              value="<?php echo esc_attr($alt); ?>"
              placeholder="<?php esc_attr_e('Voer alt-tag in', 'modern-image-manager'); ?>"
            >
          </td>
          <td>
            <button
              class="save-meta page-button media-items-save-meta"
              data-attachment-id="<?php echo esc_attr($id); ?>"
            ><?php esc_html_e('Opslaan', 'modern-image-manager'); ?></button>
          </td>
          <td>
            <button
              class="delete-media page-button media-items-delete-media"
              data-attachment-id="<?php echo esc_attr($id); ?>"
            ><?php esc_html_e('Verwijderen', 'modern-image-manager'); ?></button>
          </td>
        </tr>
      <?php endwhile; wp_reset_postdata(); ?>
    <?php else : ?>
      <tr class="unique-tr-no-media media-items-no-media">
        <td colspan="10"><?php esc_html_e('Geen media gevonden.', 'modern-image-manager'); ?></td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- “Geen resultaten” melding -->
<div id="noResults" class="filter-footer no-results media-items-no-results" style="display: none;">
  <p><?php esc_html_e('Geen afbeeldingen gevonden voor de geselecteerde filteroptie.', 'modern-image-manager'); ?></p>
</div>

<!-- Filter & paginering onderaan -->
<div class="wrap unique-wrap filter-bottom-container">
  <div class="filter-footer filter-bottom-footer">
    <div class="nav-controls filter-bottom-nav-controls">
      <select id="pageFilterBottom" class="page-filter-select filter-bottom-page-filter">
        <option value="all"><?php echo esc_html__('Alle pagina\'s', 'modern-image-manager'); ?></option>
        <option value="linked"><?php echo esc_html__('Gekoppelde pagina\'s', 'modern-image-manager'); ?></option>
        <option value="unlinked"><?php echo esc_html__('Niet gekoppelde pagina\'s', 'modern-image-manager'); ?></option>
      </select>
      <button id="applyFilterBottom" class="page-button filter-bottom-apply-button"><?php esc_html_e('Filter toepassen', 'modern-image-manager'); ?></button>
      <div class="pagination-navigation filter-bottom-pagination">
        <button id="prevPageBottom" class="page-button filter-bottom-prev-button"><?php esc_html_e('Vorige', 'modern-image-manager'); ?></button>
        <span id="currentPageBottom" class="filter-bottom-current-page">1</span> /
        <span id="totalPagesBottom" class="filter-bottom-total-pages">50</span>
        <button id="nextPageBottom" class="page-button filter-bottom-next-button"><?php esc_html_e('Volgende', 'modern-image-manager'); ?></button>
      </div>
    </div>

    <div class="bulk-delete-controls filter-bottom-bulk-delete">
      <button id="bulkDeleteBottom" class="page-button bulk-delete-button">
        <?php echo esc_html__('Verwijder geselecteerde', 'modern-image-manager'); ?>
      </button>
    </div>

    <div class="search-container filter-bottom-search-container">
      <input
        type="text"
        id="search-input-bottom"
        class="search-box filter-bottom-search-box"
        placeholder="<?php echo esc_attr__('Zoeken...', 'modern-image-manager'); ?>"
      >
    </div>

    <div class="rows-controls filter-bottom-rows-controls">
      <label for="rowsPerPageBottom" class="filter-bottom-rows-per-page-label">
        <?php esc_html_e('Rijen per pagina:', 'modern-image-manager'); ?>
      </label>
      <select id="rowsPerPageBottom" class="rows-per-page-select filter-bottom-rows-per-page-select">
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="30" selected>30</option>
        <option value="40">40</option>
        <option value="50">50</option>
      </select>
    </div>
  </div>
</div>