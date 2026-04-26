<?php
// File: includes/scan-images/scanner.php

if (! defined('ABSPATH')) {
    exit; // Geen directe toegang
}

/**
 * Hoofdscan: combineert alle bronnen en geeft enkel dubbele afbeeldingen terug.
 *
 * @return array<int,array> Attachment ID => usage info
 */
function scan_duplicate_images(): array {
    $post_imgs      = get_images_from_posts_pages();
    $feat_imgs      = get_featured_images();
    $acf_imgs       = get_acf_images();
    $tpl_imgs       = mim_get_elementor_template_images();
    $elementor_imgs = mim_scan_all_elementor_images();

    // Merge alle bronnen
    $all = array_merge_recursive(
        $post_imgs,
        $feat_imgs,
        $acf_imgs,
        $tpl_imgs,
        $elementor_imgs
    );

    $duplicates = [];
    foreach ($all as $key => $usages) {
        // Alleen WP attachments (numeric key); sla externe URLs altijd over!
        if (!is_numeric($key)) {
            continue;
        }
        $aid = (int) $key;
        $post = get_post($aid);
        if (!$post || $post->post_type !== 'attachment' || !wp_get_attachment_url($aid)) {
            continue;
        }
        // Alleen items met minstens 2 gebruiksplekken
        if (count($usages) > 1) {
            $duplicates[$key] = $usages;
        }
    }
    return $duplicates;
}

/**
 * Alias voor externe aanroep vanuit de template.
 *
 * @return array<int,array>
 */
function dius_scan_pages_for_duplicate_images() {
    return scan_duplicate_images();
}

/**
 * Toon de resultaten in een admin-tabel.
 *
 * @param array<int,array> $results
 */
function dius_display_results(array $results): void {
    if (empty($results)) {
        echo '<p class="mim-scan-page-description">'
           . esc_html__('✅ Geen dubbele afbeeldingen gevonden.', 'modern-image-manager')
           . '</p>';
        return;
    }

    echo '<h2 class="mim-scan-page-title">'
       . esc_html__('Scan resultaten', 'modern-image-manager')
       . '</h2>';
    echo '<table class="wp-list-table widefat fixed striped dius-results-table">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Attachment', 'modern-image-manager') . '</th>';
    echo '<th>' . esc_html__('Post(s) / Pagina(s)', 'modern-image-manager') . '</th>';
    echo '<th>' . esc_html__('Bron', 'modern-image-manager') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($results as $key => $usages) {
        $url      = wp_get_attachment_url((int)$key);
        $filename = $url ? basename(parse_url($url, PHP_URL_PATH)) : '(bestand niet gevonden)';

        $posts    = [];
        $contexts = [];

        foreach ($usages as $u) {
            if (!empty($u['post_id'])) {
                $pid   = (int)$u['post_id'];
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
                    case 'elementor_widget':
                        $contexts[] = __('Elementor widget (media)', 'modern-image-manager');
                        break;
                    case 'elementor_background':
                        $contexts[] = __('Elementor achtergrond (media)', 'modern-image-manager');
                        break;
                    case 'elementor_setting':
                        $contexts[] = __('Elementor instelling (media)', 'modern-image-manager');
                        break;
                    case 'elementor_template':
                        $contexts[] = __('Elementor template', 'modern-image-manager');
                        break;
                    default:
                        if (!empty($u['context'])) {
                            $contexts[] = esc_html($u['context']);
                        }
                }
            }
        }

        $unique_links = array_values(array_unique($posts));
        $first_link   = $unique_links[0] ?? '';

        echo '<tr>';
        echo '<td>';
        if ($url) {
            echo '<img class="scan-thumb" src="' . esc_url($url) . '" alt="' . esc_attr($filename) . '" style="max-width:80px; margin-right:8px; vertical-align:middle;">';
        }
        echo esc_html($filename);
        echo '</td>';
        echo '<td>' . implode(', ', $unique_links);
        if ($first_link) {
            echo ' <a href="' . esc_url(strip_tags($first_link)) . '" target="_blank">'
                 . esc_html__('Bekijk pagina', 'modern-image-manager')
                 . '</a>';
        }
        echo '</td>';
        echo '<td>' . implode(', ', array_unique($contexts)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Haal inline <img> en shortcode-images uit posts/pages.
 *
 * @return array<int|string,array>
 */
function get_images_from_posts_pages(): array {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT ID, post_content
        FROM {$wpdb->posts}
        WHERE post_type IN ('post','page') 
          AND post_status = 'publish'
    ");
    $images = [];

    foreach ($rows as $row) {
        $pid     = (int) $row->ID;
        $content = $row->post_content;

        // inline <img>
        if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $content, $m)) {
            foreach ($m[1] as $src) {
                if ($aid = attachment_url_to_postid($src)) {
                    $images[$aid][] = ['post_id' => $pid, 'type' => 'post_content'];
                }
                // externe URLs worden NIET meer meegenomen
            }
        }
        // Elementor shortcode
        if (preg_match_all('/\[elementor-template\s+id=[\'"](\d+)[\'"]/', $content, $tpls)) {
            foreach ($tpls[1] as $tpl_id) {
                $data     = get_post_meta((int)$tpl_id, '_elementor_data', true);
                $elements = json_decode((string)$data, true) ?: [];
                mim_extract_images_from_elementor_data($elements, $images, sprintf('Template shortcode op pagina %d', $pid));
            }
        }
    }
    return $images;
}

/**
 * Featured images (_thumbnail_id).
 *
 * @return array<int,array>
 */
function get_featured_images(): array {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_thumbnail_id'
    ");
    $images = [];
    foreach ($rows as $r) {
        $aid = (int)$r->meta_value;
        if (get_post_mime_type($aid)) {
            $images[$aid][] = ['post_id' => (int)$r->post_id, 'type' => 'featured_image'];
        }
    }
    return $images;
}

/**
 * ACF image velden.
 *
 * @return array<int,array>
 */
function get_acf_images(): array {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_value REGEXP '^[0-9]+$'
          AND meta_key NOT IN ('_thumbnail_id')
    ");
    $images = [];
    foreach ($rows as $r) {
        $aid = (int)$r->meta_value;
        if (get_post_mime_type($aid)) {
            $images[$aid][] = [
                'post_id'   => (int)$r->post_id,
                'type'      => 'acf_field',
                'acf_field' => $r->meta_key,
            ];
        }
    }
    return $images;
}

/**
 * Recursief afbeeldingen uit Elementor JSON-data extraheren.
 *
 * @param array  $elements
 * @param array  &$result
 * @param string $context
 */
function mim_extract_images_from_elementor_data(array $elements, array &$result, string $context): void {
    foreach ($elements as $el) {
        if (!empty($el['settings']) && is_array($el['settings'])) {
            $s = $el['settings'];

            // image widget (media)
            if (!empty($s['image']['id'])) {
                $result[(int)$s['image']['id']][] = ['post_id' => $context, 'type' => 'elementor_widget', 'context' => $context];
            }
            // background (media)
            if (!empty($s['background_image']['id'])) {
                $result[(int)$s['background_image']['id']][] = ['post_id' => $context, 'type' => 'elementor_background', 'context' => $context];
            }
            // third-party settings
            foreach ($s as $set) {
                if (is_array($set)) {
                    if (!empty($set['id']) && get_post_type((int)$set['id']) === 'attachment') {
                        $result[(int)$set['id']][] = ['post_id' => $context, 'type' => 'elementor_setting', 'context' => $context];
                    }
                }
            }
        }
        // recursief children
        if (!empty($el['elements']) && is_array($el['elements'])) {
            mim_extract_images_from_elementor_data($el['elements'], $result, $context);
        }
    }
}

/**
 * Scan alle Elementor-widgets in pagina’s & posts (full recursive).
 *
 * @return array<int,array>
 */
function mim_scan_all_elementor_images(): array {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_elementor_data'
    ");
    $images = [];
    foreach ($rows as $r) {
        $pid      = (int)$r->post_id;
        $elements = json_decode((string)$r->meta_value, true) ?: [];
        mim_extract_images_from_elementor_data($elements, $images, sprintf('Elementor data op pagina %d', $pid));
    }
    return $images;
}

/**
 * Haal alle afbeeldingen uit Elementor-templates.
 *
 * @return array<int,array>
 */
function mim_get_elementor_template_images(): array {
    if (!class_exists('\Elementor\Plugin')) {
        return [];
    }
    $templates = get_posts([
        'post_type'      => \Elementor\Plugin::instance()->post_type,
        'posts_per_page' => 500,
        'post_status'    => 'publish',
    ]);
    $images = [];
    foreach ($templates as $tpl) {
        $data     = get_post_meta($tpl->ID, '_elementor_data', true);
        $elements = json_decode((string)$data, true) ?: [];
        mim_extract_images_from_elementor_data($elements, $images, sprintf('Template: %s (ID %d)', $tpl->post_title, $tpl->ID));
    }
    return $images;
}