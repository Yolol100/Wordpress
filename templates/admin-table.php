<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Haalt de titel op van de post waaraan de bijlage is gekoppeld, inclusief ACF-velden.
 *
 * Eerst wordt gecontroleerd of de bijlage is ingesteld als uitgelichte afbeelding via _thumbnail_id.
 * Als dat niet zo is, wordt er in de database gezocht naar een ACF-koppeling, waarbij gecontroleerd wordt
 * of ergens in de postmeta de attachment ID als meta_value voorkomt.
 *
 * @param int $attachment_id De ID van de bijlage.
 * @return string De titel van de gekoppelde post, of 'Niet gekoppeld' als er geen koppeling is.
 */
function modern_image_manager_get_attached_post_title(int $attachment_id): string {
    global $wpdb;

    // Controleer eerst of de bijlage als uitgelichte afbeelding is ingesteld.
    $attached_posts = get_posts([
        'post_type'  => 'any',
        'meta_query' => [
            [
                'key'     => '_thumbnail_id',
                'value'   => $attachment_id,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($attached_posts)) {
        return (string) $attached_posts[0]->post_title;
    }

    // Als er geen koppeling via _thumbnail_id is, zoek dan in alle ACF-velden.
    $post_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %d LIMIT 1",
            $attachment_id
        )
    );

    if ($post_id) {
        $post_title = get_the_title($post_id);
        return $post_title ? $post_title : 'Niet gekoppeld';
    }

    return 'Niet gekoppeld';
}

// Query voor het ophalen van mediabestanden
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
            <option value="unlinked">Niet-gekoppelde pagina's</option>
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

                // Bepaal de status voor filtering
                $status = ($page_title === 'Niet gekoppeld') ? 'unlinked' : 'linked';
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
            <option value="unlinked">Niet-gekoppelde pagina's</option>
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