<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

$media_items = modern_image_manager_get_media_items();
?>

<!-- Filter balk bovenaan -->
<div class="wrap unique-wrap">
    <div class="filter-header">
        <div class="nav-controls">
            <select id="pageFilter" class="page-filter-select">
                <option value="all">Alle pagina's</option>
                <option value="linked">Gekoppelde pagina's</option>
                <option value="unlinked">Niet gekoppelde pagina's</option>
            </select>
            <button id="applyFilter" class="page-button">Filter toepassen</button>
            <div class="pagination-navigation">
                <button id="prevPage" class="page-button">Vorige</button>
                <span id="currentPage">1</span> / <span id="totalPages">50</span>
                <button id="nextPage" class="page-button">Volgende</button>
            </div>
        </div>
        <div class="search-container">
            <input type="text" id="search-input" class="search-box" placeholder="Zoeken..." onkeyup="filterTable()">
        </div>
        <div class="rows-controls">
            <div class="rows-per-page-container">
                <label for="rowsPerPage">Rijen per pagina:</label>
                <select id="rowsPerPage" class="rows-per-page-select">
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

<table class="wp-list-table widefat fixed striped unique-table">
    <thead class="unique-thead">
        <tr class="unique-tr-head">
            <th>Bestandsnaam</th>
            <th>Nieuwe bestandsnaam</th>
            <th>Pagina</th>
            <th>Titel</th>
            <th>Caption</th>
            <th>Description</th>
            <th>Alt-tag</th>
            <th>Opslaan</th>
            <th>Verwijderen</th>
        </tr>
    </thead>
    <tbody class="unique-tbody">
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
                <tr class="unique-tr-item unique-tr-<?php echo esc_attr($attachment_id); ?>" data-status="<?php echo esc_attr($status); ?>">
                    <td class="unique-td-bestandsnaam">
                        <input type="text"
                               class="filename-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($filename); ?>"
                               placeholder="Voer bestandsnaam in" />
                    </td>
                    <td class="unique-td-nieuwe-bestandsnaam">
                        <input type="text"
                               class="new-filename-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               placeholder="Voer nieuwe bestandsnaam in" />
                    </td>
                    <td class="unique-td-pagina"><?php echo esc_html($page_title); ?></td>
                    <td class="unique-td-titel">
                        <input type="text"
                               class="title-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($title); ?>"
                               placeholder="Voer titel in" />
                    </td>
                    <td class="unique-td-caption">
                        <input type="text"
                               class="caption-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($caption); ?>"
                               placeholder="Voer caption in" />
                    </td>
                    <td class="unique-td-beschrijving">
                        <textarea class="description-input"
                                  data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                                  placeholder="Voer description in"><?php echo esc_textarea($description); ?></textarea>
                    </td>
                    <td class="unique-td-alttag">
                        <input type="text"
                               class="alt-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($alt_text); ?>"
                               placeholder="Voer alt-tag in" />
                    </td>
                    <td class="unique-td-acties">
                        <button class="save-meta page-button" data-attachment-id="<?php echo esc_attr($attachment_id); ?>">Opslaan</button>
                    </td>
                    <td class="unique-td-verwijderen">
                        <button class="delete-media page-button" data-attachment-id="<?php echo esc_attr($attachment_id); ?>">Verwijderen</button>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <tr class="unique-tr-no-media">
                <td colspan="9" class="unique-td-no-media">Geen media gevonden.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Geen resultaten container -->
<div id="noResults" class="filter-footer no-results" style="display: none;">
    <p>Geen resultaten gevonden.</p>
</div>

<!-- Filter balk onderaan -->
<div class="unique-wrap">
    <div class="filter-footer">
        <div class="nav-controls">
            <select id="pageFilterBottom" class="page-filter-select">
                <option value="all">Alle pagina's</option>
                <option value="linked">Gekoppelde pagina's</option>
                <option value="unlinked">Niet gekoppelde pagina's</option>
            </select>
            <button id="applyFilterBottom" class="page-button">Filter toepassen</button>
            <div class="pagination-navigation">
                <button id="prevPageBottom" class="page-button">Vorige</button>
                <span id="currentPageBottom">1</span> / <span id="totalPagesBottom">50</span>
                <button id="nextPageBottom" class="page-button">Volgende</button>
            </div>
        </div>
        <div class="search-container">
            <input type="text" id="search-input-bottom" class="search-box" placeholder="Zoeken..." onkeyup="filterTable()">
        </div>
        <div class="rows-controls">
            <div class="rows-per-page-container">
                <label for="rowsPerPageBottom">Rijen per pagina:</label>
                <select id="rows-per-pageBottom" class="rows-per-page-select">
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