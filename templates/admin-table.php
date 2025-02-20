<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

// Haal media-items op
$media_items = modern_image_manager_get_media_items();
?>

<!-- Filter balk bovenaan -->
<div class="wrap unique-wrap filter-top-container">
    <div class="filter-header filter-top-header">
        <div class="nav-controls filter-top-nav-controls">
            <select id="pageFilter" class="page-filter-select filter-top-page-filter">
                <option value="all"><?php echo __("Alle pagina's", "modern-image-manager"); ?></option>
                <option value="linked"><?php echo __("Gekoppelde pagina's", "modern-image-manager"); ?></option>
                <option value="unlinked"><?php echo __("Niet gekoppelde pagina's", "modern-image-manager"); ?></option>
            </select>
            <button id="applyFilter" class="page-button filter-top-apply-button"><?php echo __("Filter toepassen", "modern-image-manager"); ?></button>
            <div class="pagination-navigation filter-top-pagination">
                <button id="prevPage" class="page-button filter-top-prev-button"><?php echo __("Vorige", "modern-image-manager"); ?></button>
                <span id="currentPage" class="filter-top-current-page">1</span> / <span id="totalPages" class="filter-top-total-pages">50</span>
                <button id="nextPage" class="page-button filter-top-next-button"><?php echo __("Volgende", "modern-image-manager"); ?></button>
            </div>
        </div>
        <div class="search-container filter-top-search-container">
            <input type="text" id="search-input" class="search-box filter-top-search-box" placeholder="<?php echo __("Zoeken...", "modern-image-manager"); ?>" onkeyup="filterTable()">
        </div>
        <div class="rows-controls filter-top-rows-controls">
            <div class="rows-per-page-container filter-top-rows-per-page-container">
                <label for="rowsPerPage" class="filter-top-rows-per-page-label"><?php echo __("Rijen per pagina:", "modern-image-manager"); ?></label>
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
</div>

<table class="wp-list-table widefat fixed striped unique-table media-items-table">
    <thead class="unique-thead media-items-thead">
        <tr class="unique-tr-head media-items-tr-head">
            <th><?php echo __("Bestandsnaam", "modern-image-manager"); ?></th>
            <th><?php echo __("Nieuwe bestandsnaam", "modern-image-manager"); ?></th>
            <th><?php echo __("Pagina", "modern-image-manager"); ?></th>
            <th><?php echo __("Titel", "modern-image-manager"); ?></th>
            <th><?php echo __("Caption", "modern-image-manager"); ?></th>
            <th><?php echo __("Description", "modern-image-manager"); ?></th>
            <th><?php echo __("Alt-tag", "modern-image-manager"); ?></th>
            <th><?php echo __("Opslaan", "modern-image-manager"); ?></th>
            <th><?php echo __("Verwijderen", "modern-image-manager"); ?></th>
        </tr>
    </thead>
    <tbody class="unique-tbody media-items-tbody">
        <?php if ($media_items->have_posts()) : ?>
            <?php while ($media_items->have_posts()) : $media_items->the_post(); ?>
                <?php
                $attachment_id = (int) get_the_ID();
                $attachment    = get_post($attachment_id);
                if (!$attachment) {
                    continue;
                }

                // Huidige waarden ophalen
                $alt_text    = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $caption     = $attachment->post_excerpt;
                $description = $attachment->post_content;
                $title       = $attachment->post_title;
                $page_title  = modern_image_manager_get_attached_post_title($attachment_id);

                // Bestandsnaam bepalen (extensie wordt niet meer getoond)
                $path     = get_attached_file($attachment_id);
                $filename = pathinfo($path, PATHINFO_FILENAME);

                // Status voor filtering
                $status = (strpos($page_title, 'Niet gekoppeld') !== false) ? 'unlinked' : 'linked';
                ?>
                <tr class="unique-tr-item media-items-tr-item unique-tr-<?php echo esc_attr($attachment_id); ?>" data-status="<?php echo esc_attr($status); ?>">
                    <td class="unique-td-bestandsnaam media-items-td-bestandsnaam">
                        <input type="text"
                               class="filename-input media-items-filename-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($filename); ?>"
                               placeholder="<?php echo __("Voer bestandsnaam in", "modern-image-manager"); ?>"
                               readonly />
                    </td>
                    <td class="unique-td-nieuwe-bestandsnaam media-items-td-nieuwe-bestandsnaam">
                        <input type="text"
                               class="new-filename-input media-items-new-filename-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               placeholder="<?php echo __("Voer nieuwe bestandsnaam in", "modern-image-manager"); ?>" />
                    </td>
                    <td class="unique-td-pagina media-items-td-pagina"><?php echo esc_html($page_title); ?></td>
                    <td class="unique-td-titel media-items-td-titel">
                        <input type="text"
                               class="title-input media-items-title-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($title); ?>"
                               placeholder="<?php echo __("Voer titel in", "modern-image-manager"); ?>" />
                    </td>
                    <td class="unique-td-caption media-items-td-caption">
                        <input type="text"
                               class="caption-input media-items-caption-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($caption); ?>"
                               placeholder="<?php echo __("Voer caption in", "modern-image-manager"); ?>" />
                    </td>
                    <td class="unique-td-beschrijving media-items-td-beschrijving">
                        <textarea class="description-input media-items-description-input"
                                  data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                                  placeholder="<?php echo __("Voer description in", "modern-image-manager"); ?>"><?php echo esc_textarea($description); ?></textarea>
                    </td>
                    <td class="unique-td-alttag media-items-td-alttag">
                        <input type="text"
                               class="alt-input media-items-alt-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($alt_text); ?>"
                               placeholder="<?php echo __("Voer alt-tag in", "modern-image-manager"); ?>" />
                    </td>
                    <td class="unique-td-acties media-items-td-acties">
                        <button class="save-meta page-button media-items-save-meta" data-attachment-id="<?php echo esc_attr($attachment_id); ?>"><?php echo __("Opslaan", "modern-image-manager"); ?></button>
                    </td>
                    <td class="unique-td-verwijderen media-items-td-verwijderen">
                        <button class="delete-media page-button media-items-delete-media" data-attachment-id="<?php echo esc_attr($attachment_id); ?>"><?php echo __("Verwijderen", "modern-image-manager"); ?></button>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <tr class="unique-tr-no-media media-items-no-media">
                <td colspan="9" class="unique-td-no-media media-items-td-no-media"><?php echo __("Geen media gevonden.", "modern-image-manager"); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Geen resultaten container -->
<div id="noResults" class="filter-footer no-results media-items-no-results" style="display: none;">
    <p><?php echo __("Geen afbeeldingen gevonden voor de geselecteerde filteroptie.", "modern-image-manager"); ?></p>
</div>

<!-- Filter balk onderaan -->
<div class="wrap unique-wrap filter-bottom-container">
    <div class="filter-footer filter-bottom-footer">
        <div class="nav-controls filter-bottom-nav-controls">
            <select id="pageFilterBottom" class="page-filter-select filter-bottom-page-filter">
                <option value="all"><?php echo __("Alle pagina's", "modern-image-manager"); ?></option>
                <option value="linked"><?php echo __("Gekoppelde pagina's", "modern-image-manager"); ?></option>
                <option value="unlinked"><?php echo __("Niet gekoppelde pagina's", "modern-image-manager"); ?></option>
            </select>
            <button id="applyFilterBottom" class="page-button filter-bottom-apply-button"><?php echo __("Filter toepassen", "modern-image-manager"); ?></button>
            <div class="pagination-navigation filter-bottom-pagination">
                <button id="prevPageBottom" class="page-button filter-bottom-prev-button"><?php echo __("Vorige", "modern-image-manager"); ?></button>
                <span id="currentPageBottom" class="filter-bottom-current-page">1</span> / <span id="totalPagesBottom" class="filter-bottom-total-pages">50</span>
                <button id="nextPageBottom" class="page-button filter-bottom-next-button"><?php echo __("Volgende", "modern-image-manager"); ?></button>
            </div>
        </div>
        <div class="search-container filter-bottom-search-container">
            <input type="text" id="search-input-bottom" class="search-box filter-bottom-search-box" placeholder="<?php echo __("Zoeken...", "modern-image-manager"); ?>" onkeyup="filterTable()">
        </div>
        <div class="rows-controls filter-bottom-rows-controls">
            <div class="rows-per-page-container filter-bottom-rows-per-page-container">
                <label for="rows-per-pageBottom" class="filter-bottom-rows-per-page-label"><?php echo __("Rijen per pagina:", "modern-image-manager"); ?></label>
                <select id="rows-per-pageBottom" class="rows-per-page-select filter-bottom-rows-per-page-select">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="30" selected>30</option>
                    <option value="40">40</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>
</div>