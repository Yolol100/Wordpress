<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Haalt de titel op van de post waaraan de bijlage is gekoppeld via:
 * 1. WordPress: Als de afbeelding als uitgelichte afbeelding is ingesteld (post_parent > 0).
 * 2. ACF: Als er een koppeling via ACF-meta-velden is gevonden (meta_key beginnend met "field_").
 * 3. Anders: 'Niet gekoppeld'.
 *
 * @param int $attachment_id De ID van de bijlage.
 * @return string De titel van de gekoppelde post, of 'Niet gekoppeld' als er geen koppeling is.
 */
function modern_image_manager_get_attached_post_title(int $attachment_id): string {
    global $wpdb;

    // Haal de bijlage op.
    $attachment = get_post($attachment_id);
    if (!$attachment) {
        return 'Niet gekoppeld';
    }

    // 1. Controleer of de afbeelding als uitgelichte afbeelding is ingesteld (WordPress).
    if ($attachment->post_parent > 0) {
        $parent = get_post($attachment->post_parent);
        return $parent ? $parent->post_title : 'Niet gekoppeld';
    }

    // 2. Zoek naar een koppeling via ACF-meta-velden (meta_key beginnend met "field_").
    $acf_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %d AND meta_key LIKE 'field_%%' LIMIT 1",
            $attachment_id
        )
    );
    if ($acf_data && !empty($acf_data->post_id)) {
        $parent = get_post((int) $acf_data->post_id);
        return $parent ? $parent->post_title : 'Niet gekoppeld';
    }

    // Geen koppeling gevonden.
    return 'Niet gekoppeld';
}

// Query voor het ophalen van alle mediabestanden.
$args = [
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
];
$media_items = new WP_Query($args);
?>

<!-- Filter balk bovenaan -->
<div class="wrap unique-wrap">
    <div class="filter-controls">
        <select id="pageFilter" class="page-filter-select">
            <option value="all">Alle pagina's</option>
            <option value="linked">Gekoppelde pagina's</option>
            <option value="unlinked">Niet gekoppelde pagina's</option>
        </select>
        <button id="applyFilter">Filter toepassen</button>
        <div class="pagination-navigation">
            <button id="prevPage">Vorige</button>
            <span id="currentPage">1</span> / <span id="totalPages">50</span>
            <button id="nextPage">Volgende</button>
        </div>
        <div class="rows-per-page-container">
            <label for="rowsPerPage">Rijen per pagina:</label>
            <select id="rowsPerPage" class="rows-per-page-select">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
            </select>
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
            <th>Acties</th>
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

                // Bestandsnaam en extensie bepalen
                $path      = get_attached_file($attachment_id);
                $filename  = pathinfo($path, PATHINFO_FILENAME);
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                // Bepaal de status voor filtering: als er "Niet gekoppeld" in staat, is het unlinked
                $status = (strpos($page_title, 'Niet gekoppeld') !== false) ? 'unlinked' : 'linked';
                ?>
                <tr class="unique-tr-item unique-tr-<?php echo esc_attr($attachment_id); ?>" data-status="<?php echo esc_attr($status); ?>">
                    <td class="unique-td-bestandsnaam">
                        <input type="text"
                               class="filename-input"
                               data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                               value="<?php echo esc_attr($filename); ?>"
                               placeholder="Voer bestandsnaam in" />.<?php echo esc_html($extension); ?>
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
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <tr class="unique-tr-no-media">
                <td colspan="9" class="unique-td-no-media">Geen media gevonden.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Filter balk onderaan -->
<div class="wrap unique-wrap">
    <div class="filter-controls">
        <select id="pageFilterBottom" class="page-filter-select">
            <option value="all">Alle pagina's</option>
            <option value="linked">Gekoppelde pagina's</option>
            <option value="unlinked">Niet gekoppelde pagina's</option>
        </select>
        <button id="applyFilterBottom">Filter toepassen</button>
        <div class="pagination-navigation">
            <button id="prevPageBottom">Vorige</button>
            <span id="currentPageBottom">1</span> / <span id="totalPagesBottom">50</span>
            <button id="nextPageBottom">Volgende</button>
        </div>
        <div class="rows-per-page-container">
            <label for="rowsPerPageBottom">Rijen per pagina:</label>
            <select id="rowsPerPageBottom" class="rows-per-page-select">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
</div>
