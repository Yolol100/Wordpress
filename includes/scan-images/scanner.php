<?php
/**
 * includes/scan-images/scanner.php
 * Unified Admin Page en Scanner voor Duplicate Images,
 * uitgebreid met support voor Elementor-templates
 * (bulk-delete functionaliteit verwijderd)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

/**
 * Laad CSS voor de scanpagina en voeg inline CSS toe om de filter te verbergen als
 * er geen duplicaat-rijen in de resultaten-tabel staan.
 */
function mim_enqueue_admin_assets_for_scan_page( $hook ) {
    if ( $hook !== 'modern-image-manager_page_scan-duplicate-images' ) {
        return;
    }
    wp_enqueue_style(
        'scan-duplicate-images',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
        [],
        '1.0.0'
    );
    wp_add_inline_style(
        'scan-duplicate-images',
        "
        /* verberg dropdown-container als er geen duplicaten zijn */
        .scan-wrapper:not(:has(table.dius-results-table tbody tr)) .mim-scan-filter {
            display: none;
        }
        "
    );
}
add_action( 'admin_enqueue_scripts', 'mim_enqueue_admin_assets_for_scan_page' );

/**
 * Functie die scanpagina rendert. (Definitie hoort in hoofd-pluginbestand te staan.)
 * Hier alleen helper-functies.
 */

/**
 * Scan alle gebruikte afbeeldingen en retourneer duplicaten.
 *
 * @return array<int,array>
 */
function scan_duplicate_images(): array {
    $post_imgs    = get_images_from_posts_pages();
    $feat_imgs    = get_featured_images();
    $acf_imgs     = get_acf_images();
    $tpl_imgs     = mim_get_elementor_template_images();
    $tpl_page_map = mim_map_elementor_templates_to_pages();

    $all = array_merge_recursive( $post_imgs, $feat_imgs, $acf_imgs, $tpl_imgs );
    $duplicates = [];

    foreach ( $all as $aid => $usages ) {
        // Enkel echte mediabibliotheek-items
        $post_obj = get_post( $aid );
        if (
            ! $post_obj ||
            $post_obj->post_type !== 'attachment' ||
            ! wp_get_attachment_url( $aid )
        ) {
            continue;
        }
        if ( count( $usages ) > 1 ) {
            $duplicates[ $aid ] = $usages;
        }
    }

    return $duplicates;
}

/**
 * Alias voor externe aanroep.
 *
 * @return array<int,array>
 */
function dius_scan_pages_for_duplicate_images() {
    return scan_duplicate_images();
}

/**
 * Toon de scanresultaten in een tabel.
 *
 * @param array<int,array> $results
 */
function dius_display_results( $results ) {
    if ( empty( $results ) ) {
        echo '<p class="mim-scan-page-description">'
           . esc_html__( 'Geen dubbele afbeeldingen gevonden.', 'modern-image-manager' )
           . '</p>';
        return;
    }

    echo '<h2 class="mim-scan-page-title">'
       . esc_html__( 'Scan resultaten', 'modern-image-manager' )
       . '</h2>';
    echo '<p class="mim-scan-page-description"><strong>'
       . esc_html__( 'Totaal aantal dubbele afbeeldingen gevonden:', 'modern-image-manager' ) . '</strong> '
       . esc_html( count( $results ) ) . '</p>';

    echo '<table class="wp-list-table widefat fixed striped dius-results-table">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Attachment', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Post(s) / Pagina(s)', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Bron', 'modern-image-manager' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $results as $aid => $usages ) {
        $url      = wp_get_attachment_url( $aid );
        $filename = $url ? basename( parse_url( $url, PHP_URL_PATH ) ) : '(bestand niet gevonden)';

        $posts    = [];
        $contexts = [];
        foreach ( $usages as $u ) {
            if ( ! empty( $u['post_id'] ) ) {
                $pid   = $u['post_id'];
                $title = get_the_title( $pid ) ?: __( 'Onbekend', 'modern-image-manager' );
                $link  = get_edit_post_link( $pid ) ?: '#';
                $posts[ $pid ] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url( $link ),
                    esc_html( $title )
                );
            }
            if ( ! empty( $u['type'] ) ) {
                switch ( $u['type'] ) {
                    case 'post_content':
                        $contexts[] = __( 'In content', 'modern-image-manager' );
                        break;
                    case 'featured_image':
                        $contexts[] = __( 'Uitgelichte afbeelding', 'modern-image-manager' );
                        break;
                    case 'acf_field':
                        $contexts[] = __( 'ACF veld:', 'modern-image-manager' ) . ' ' . esc_html( $u['acf_field'] );
                        break;
                    default:
                        if ( ! empty( $u['context'] ) ) {
                            $contexts[] = esc_html( $u['context'] );
                        }
                }
            }
        }

        // Unieke posts dedupliceren
        $unique_titles = array_unique( array_map( 'strip_tags', $posts ) );
        $unique_links  = [];
        foreach ( $unique_titles as $title ) {
            foreach ( $posts as $html ) {
                if ( strip_tags( $html ) === $title ) {
                    $unique_links[] = $html;
                    break;
                }
            }
        }

        echo '<tr>';
        echo '<td>' . esc_html( $filename ) . '</td>';
        echo '<td>' . implode( ', ', $unique_links ) . '</td>';
        echo '<td>' . implode( ', ', array_unique( $contexts ) ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Helper: inline <img> en Elementor shortcode afbeeldingen verzamelen.
 *
 * @return array<int,array>
 */
function get_images_from_posts_pages(): array {
    global $wpdb;
    $posts = $wpdb->get_results( "
        SELECT ID, post_content
        FROM {$wpdb->posts}
        WHERE post_type IN ('post','page')
          AND post_status = 'publish'
    " );
    $images = [];
    foreach ( $posts as $post ) {
        if ( preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $m ) ) {
            foreach ( $m[1] as $url ) {
                if ( $aid = attachment_url_to_postid( $url ) ) {
                    $images[ $aid ][] = [
                        'type'    => 'post_content',
                        'post_id' => $post->ID,
                    ];
                }
            }
        }
        if ( preg_match_all( '/\[elementor-template\s+id=[\'"](\d+)[\'"]/', $post->post_content, $s ) ) {
            foreach ( $s[1] as $tpl_id ) {
                $raw_data = get_post_meta( $tpl_id, '_elementor_data', true );
                $elements = json_decode( (string) $raw_data, true ) ?: [];
                mim_extract_images_from_elementor_data( $elements, $images, sprintf( 'Template via shortcode op Pagina %d', $post->ID ) );
            }
        }
    }
    return $images;
}

/**
 * Helper: featured images ophalen (_thumbnail_id).
 *
 * @return array<int,array>
 */
function get_featured_images(): array {
    global $wpdb;
    $rows = $wpdb->get_results( "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_thumbnail_id'
    " );
    $images = [];
    foreach ( $rows as $row ) {
        $aid = (int) $row->meta_value;
        if ( get_post_mime_type( $aid ) ) {
            $images[ $aid ][] = [
                'type'    => 'featured_image',
                'post_id' => $row->post_id,
            ];
        }
    }
    return $images;
}

/**
 * Helper: ACF image veld IDs ophalen.
 *
 * @return array<int,array>
 */
function get_acf_images(): array {
    global $wpdb;
    $rows = $wpdb->get_results( "
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_value REGEXP '^[0-9]+$'
          AND meta_key NOT IN ('_thumbnail_id')
    " );
    $images = [];
    foreach ( $rows as $row ) {
        $aid = (int) $row->meta_value;
        if ( get_post_mime_type( $aid ) ) {
            $images[ $aid ][] = [
                'type'      => 'acf_field',
                'post_id'   => $row->post_id,
                'acf_field' => $row->meta_key,
            ];
        }
    }
    return $images;
}

/**
 * Helper: recursief afbeeldingen uit Elementor JSON-data extraheren.
 *
 * @param array  $elements
 * @param array  &$result
 * @param string $context
 */
function mim_extract_images_from_elementor_data( array $elements, array & $result, string $context ): void {
    foreach ( $elements as $el ) {
        if ( isset( $el['elType'], $el['widgetType'], $el['settings']['image']['url'] )
          && $el['elType'] === 'widget'
          && $el['widgetType'] === 'image'
        ) {
            $url = $el['settings']['image']['url'];
            if ( $aid = attachment_url_to_postid( $url ) ) {
                $result[ $aid ][] = [
                    'type'    => 'elementor_template',
                    'context' => $context,
                ];
            }
        }
        if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
            mim_extract_images_from_elementor_data( $el['elements'], $result, $context );
        }
    }
}

/**
 * Helper: alle Elementor-template afbeeldingen ophalen.
 *
 * @return array<int,array>
 */
function mim_get_elementor_template_images(): array {
    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return [];
    }
    $templates = get_posts( [
        'post_type'      => \Elementor\Plugin::instance()->post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );
    $result = [];
    foreach ( $templates as $tpl ) {
        $raw_data = get_post_meta( $tpl->ID, '_elementor_data', true );
        $elements = json_decode( (string) $raw_data, true ) ?: [];
        mim_extract_images_from_elementor_data( $elements, $result, sprintf( 'Template: %s (ID %d)', $tpl->post_title, $tpl->ID ) );
    }
    return $result;
}

/**
 * Helper: map Elementor-templates naar pagina-IDs via voorwaarden.
 *
 * @return array<int,int[]>
 */
function mim_map_elementor_templates_to_pages(): array {
    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return [];
    }
    $map       = [];
    $templates = get_posts( [
        'post_type'      => \Elementor\Plugin::instance()->post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );
    foreach ( $templates as $tpl ) {
        $conds = get_post_meta( $tpl->ID, '_elementor_conditions', true );
        if ( is_array( $conds ) ) {
            foreach ( $conds as $groups ) {
                foreach ( (array) $groups as $group ) {
                    foreach ( (array) $group as $cond ) {
                        if ( in_array( $cond['param'], [ 'post', 'page' ], true ) ) {
                            foreach ( (array) $cond['value'] as $pid ) {
                                $map[ $tpl->ID ][] = (int) $pid;
                            }
                        }
                    }
                }
            }
        }
    }
    return $map;
}