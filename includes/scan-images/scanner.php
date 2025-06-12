<?php
// File: includes/scan-images/scanner.php
// Unified Admin Page en Scanner voor Duplicate Images inclusief Elementor support

if (!defined('ABSPATH')) {
    exit; // Geen directe toegang
}

/**
 * Enqueue styles en scripts voor scan pagina.
 */
function mim_enqueue_admin_assets_for_scan_page($hook)
{
    if ($hook !== 'modern-image-manager_page_scan-duplicate-images') {
        return;
    }

    wp_enqueue_style(
        'scan-duplicate-images',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
        [],
        '1.0.0'
    );

    // Inline CSS om controls te verbergen als er geen duplicaten zijn
    wp_add_inline_style(
        'scan-duplicate-images',
        "
        .scan-wrapper:not(:has(table.dius-results-table tbody tr)) #mim-enhanced-controls {
            display: none;
        }
        "
    );

    wp_enqueue_script(
        'scan-duplicate-images-js',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js',
        [],
        '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'mim_enqueue_admin_assets_for_scan_page');

/**
 * Scan alle gebruikte afbeeldingen en retourneer duplicaten.
 *
 * @return array<int,array> Attachment ID => usage info
 */
function scan_duplicate_images(): array
{
    $post_imgs = get_images_from_posts_pages();
    $feat_imgs = get_featured_images();
    $acf_imgs  = get_acf_images();
    $tpl_imgs  = mim_get_elementor_template_images();

    // Alle gevonden afbeeldingen samenvoegen
    $all = array_merge_recursive($post_imgs, $feat_imgs, $acf_imgs, $tpl_imgs);
    $duplicates = [];

    foreach ($all as $aid => $usages) {
        $post_obj = get_post($aid);
        if (
            !$post_obj ||
            $post_obj->post_type !== 'attachment' ||
            !wp_get_attachment_url($aid)
        ) {
            continue;
        }

        // Alleen meerdere verwijzingen tellen
        if (count($usages) > 1) {
            $duplicates[$aid] = $usages;
        }
    }

    return $duplicates;
}

/**
 * Alias voor externe aanroep.
 *
 * @return array<int,array>
 */
function dius_scan_pages_for_duplicate_images()
{
    return scan_duplicate_images();
}

/**
 * Toon scanresultaten in een interactieve tabel.
 *
 * @param array<int,array> $results
 */
function dius_display_results($results)
{
    if (empty($results)) {
        echo '<p class="mim-scan-page-description">' .
            esc_html__('✅ Geen dubbele afbeeldingen gevonden.', 'modern-image-manager') .
            '</p>';
        return;
    }

    // Tabel header
    echo '<h2 class="mim-scan-page-title">' . esc_html__('Scan resultaten', 'modern-image-manager') . '</h2>';
    echo '<table class="wp-list-table widefat fixed striped dius-results-table" data-sort-asc="false">';
    echo '<thead><tr>';
    echo '<th onclick="sortTable(0)">' . esc_html__('Attachment', 'modern-image-manager') . ' <span class="sort-arrow"></span></th>';
    echo '<th onclick="sortTable(1)">' . esc_html__('Post(s) / Pagina(s)', 'modern-image-manager') . ' <span class="sort-arrow"></span></th>';
    echo '<th onclick="sortTable(2)">' . esc_html__('Bron', 'modern-image-manager') . ' <span class="sort-arrow"></span></th>';
    echo '</tr></thead><tbody>';

    foreach ($results as $aid => $usages) {
        $url      = wp_get_attachment_url($aid);
        $filename = $url ? basename(parse_url($url, PHP_URL_PATH)) : '(bestand niet gevonden)';

        $posts    = [];
        $contexts = [];

        foreach ($usages as $u) {
            if (!empty($u['post_id'])) {
                $pid   = $u['post_id'];
                $title = get_the_title($pid) ?: __('Onbekend', 'modern-image-manager');
                $link  = get_edit_post_link($pid) ?: '#';

                $posts[$pid] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url($link),
                    esc_html($title)
                );
            }

            if (!empty($u['type'])) {
                switch ($u['type']) {
                    case 'post_content':
                        $contexts[] = __('In content', 'modern-image-manager');
                        break;
                    case 'featured_image':
                        $contexts[] = __('Uitgelichte afbeelding', 'modern-image-manager');
                        break;
                    case 'acf_field':
                        $contexts[] = __('ACF veld:', 'modern-image-manager') . ' ' . esc_html($u['acf_field']);
                        break;
                    default:
                        if (!empty($u['context'])) {
                            $contexts[] = esc_html($u['context']);
                        }
                }
            }
        }

        // Verwijder dubbele links
        $unique_links = array_values(array_unique($posts));
        $first_link   = $unique_links[0] ?? '';

        echo '<tr>';
        echo '<td>';
        if ($url) {
            echo '<img class="scan-thumb" src="' . esc_url($url) . '" alt="' . esc_attr($filename) . '">';
        }
        echo esc_html($filename);
        echo '</td>';

        echo '<td>' . implode(', ', $unique_links);
        if ($first_link) {
            echo ' <a class="mim-view-page-btn" href="' . esc_url(strip_tags($first_link)) . '" target="_blank">'
                . esc_html__('Bekijk pagina', 'modern-image-manager') .
                '</a>';
        }
        echo '</td>';

        echo '<td>' . implode(', ', array_unique($contexts)) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Zoek inline <img> tags en Elementor shortcodes in posts/pages.
 *
 * @return array<int,array> Attachment ID => usage
 */
function get_images_from_posts_pages(): array
{
    global $wpdb;
    $posts = $wpdb->get_results("
        SELECT ID, post_content
        FROM {$wpdb->posts}
        WHERE post_type IN ('post','page')
          AND post_status = 'publish'
    ");
    $images = [];

    foreach ($posts as $post) {
        if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $m)) {
            foreach ($m[1] as $url) {
                if ($aid = attachment_url_to_postid($url)) {
                    $images[$aid][] = [
                        'type'    => 'post_content',
                        'post_id' => $post->ID,
                    ];
                }
            }
        }
        if (preg_match_all('/\[elementor-template\s+id=[\'"](\d+)[\'"]/', $post->post_content, $s)) {
            foreach ($s[1] as $tpl_id) {
                $raw_data = get_post_meta($tpl_id, '_elementor_data', true);
                $elements = json_decode((string)$raw_data, true) ?: [];
                mim_extract_images_from_elementor_data($elements, $images, sprintf('Template via shortcode op Pagina %d', $post->ID));
            }
        }
    }

    return $images;
}

/**
 * Haal alle uitgelichte afbeeldingen (_thumbnail_id) op.
 *
 * @return array<int,array>
 */
function get_featured_images(): array
{
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_thumbnail_id'
    ");
    $images = [];
    foreach ($rows as $row) {
        $aid = (int)$row->meta_value;
        if (get_post_mime_type($aid)) {
            $images[$aid][] = [
                'type'    => 'featured_image',
                'post_id' => $row->post_id,
            ];
        }
    }
    return $images;
}

/**
 * Haal ACF image velden op (meta_value als ID).
 *
 * @return array<int,array>
 */
function get_acf_images(): array
{
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_value REGEXP '^[0-9]+$'
          AND meta_key NOT IN ('_thumbnail_id')
    ");
    $images = [];
    foreach ($rows as $row) {
        $aid = (int)$row->meta_value;
        if (get_post_mime_type($aid)) {
            $images[$aid][] = [
                'type'      => 'acf_field',
                'post_id'   => $row->post_id,
                'acf_field' => $row->meta_key,
            ];
        }
    }
    return $images;
}

/**
 * Recursief afbeeldingen uit Elementor JSON-data extraheren.
 *
 * @param array $elements Elementor element data
 * @param array &$result Resultaat array (ref)
 * @param string $context Context string
 */
function mim_extract_images_from_elementor_data(array $elements, array &$result, string $context): void
{
    foreach ($elements as $el) {
        if (
            isset($el['elType'], $el['widgetType'], $el['settings']['image']['url']) &&
            $el['elType'] === 'widget' &&
            $el['widgetType'] === 'image'
        ) {
            $url = $el['settings']['image']['url'];
            if ($aid = attachment_url_to_postid($url)) {
                $result[$aid][] = [
                    'type'    => 'elementor_template',
                    'context' => $context,
                ];
            }
        }
        if (!empty($el['elements']) && is_array($el['elements'])) {
            mim_extract_images_from_elementor_data($el['elements'], $result, $context);
        }
    }
}

/**
 * Haal alle Elementor-template afbeeldingen op.
 *
 * @return array<int,array>
 */
function mim_get_elementor_template_images(): array
{
    if (!class_exists('\Elementor\Plugin')) {
        return [];
    }
    $templates = get_posts([
        'post_type'      => \Elementor\Plugin::instance()->post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);
    $result = [];
    foreach ($templates as $tpl) {
        $raw_data = get_post_meta($tpl->ID, '_elementor_data', true);
        $elements = json_decode((string)$raw_data, true) ?: [];
        mim_extract_images_from_elementor_data($elements, $result, sprintf('Template: %s (ID %d)', $tpl->post_title, $tpl->ID));
    }
    return $result;
}

/**
 * Map Elementor-templates naar pagina-IDs via voorwaarden.
 *
 * @return array<int,int[]>
 */
function mim_map_elementor_templates_to_pages(): array
{
    if (!class_exists('\Elementor\Plugin')) {
        return [];
    }
    $map = [];
    $templates = get_posts([
        'post_type'      => \Elementor\Plugin::instance()->post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);
    foreach ($templates as $tpl) {
        $conds = get_post_meta($tpl->ID, '_elementor_conditions', true);
        if (is_array($conds)) {
            foreach ($conds as $groups) {
                foreach ((array)$groups as $group) {
                    foreach ((array)$group as $cond) {
                        if (in_array($cond['param'], ['post', 'page'], true)) {
                            foreach ((array)$cond['value'] as $pid) {
                                $map[$tpl->ID][] = (int)$pid;
                            }
                        }
                    }
                }
            }
        }
    }
    return $map;
}