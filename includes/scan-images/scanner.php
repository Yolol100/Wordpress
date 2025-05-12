<?php
/**
 * includes/scan-images/scanner.php
 * Unified Admin Page en Scanner voor Duplicate Images,
 * uitgebreid met support voor Elementor-templates
 * (bulk-delete functionaliteit verwijderd)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    return; // Zorg ervoor dat de gebruiker voldoende rechten heeft.
}

/**
 * Laad de CSS voor de scanpagina.
 */
function mim_enqueue_admin_assets_for_scan_page( $hook ) {
    if ( $hook !== 'toplevel_page_modern-image-manager'
      && $hook !== 'modern-image-manager_page_scan-duplicate-images'
    ) {
        return;
    }

    wp_enqueue_style(
        'scan-duplicate-images',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
        [],
        '1.0.0',
        'all'
    );
}
add_action( 'admin_enqueue_scripts', 'mim_enqueue_admin_assets_for_scan_page' );

/**
 * Recursief zoeken in Elementor-widget-data en omzet naar attachment ID.
 */
function mim_extract_images_from_elementor_data( $elements, & $result, $context ) {
    foreach ( (array) $elements as $el ) {
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
        if ( ! empty( $el['elements'] ) ) {
            mim_extract_images_from_elementor_data( $el['elements'], $result, $context );
        }
    }
}

/**
 * Haal alle afbeelding-IDs uit opgeslagen Elementor-templates.
 */
function mim_get_elementor_template_images() {
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
        $data     = get_post_meta( $tpl->ID, '_elementor_data', true );
        $elements = json_decode( $data, true );
        $context  = sprintf( 'Template: %s (ID %d)', $tpl->post_title, $tpl->ID );
        mim_extract_images_from_elementor_data( $elements, $result, $context );
    }
    return $result;
}

/**
 * Lees Elementor display conditions om templates te mappen naar pagina-ID's.
 */
function mim_map_elementor_templates_to_pages() {
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

/**
 * Haal attachment-IDs uit inline <img> tags en Elementor-shortcodes in posts/pages.
 */
function get_images_from_posts_pages() {
    global $wpdb;
    $posts   = $wpdb->get_results( "
        SELECT ID, post_content
        FROM {$wpdb->posts}
        WHERE post_type IN ('post','page')
          AND post_status = 'publish'
    " );
    $images = [];
    foreach ( $posts as $post ) {
        // Inline <img> tags
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
        // Elementor-template shortcode
        if ( preg_match_all( '/\[elementor-template\s+id=[\'"](\d+)[\'"]/', $post->post_content, $s ) ) {
            foreach ( $s[1] as $tpl_id ) {
                $data     = get_post_meta( $tpl_id, '_elementor_data', true );
                $elements = json_decode( $data, true );
                $context  = sprintf( 'Template via shortcode op Pagina %d', $post->ID );
                mim_extract_images_from_elementor_data( $elements, $images, $context );
            }
        }
    }
    return $images;
}

/**
 * Haal attachment-IDs uit uitgelichte afbeeldingen (_thumbnail_id).
 */
function get_featured_images() {
    global $wpdb;
    $rows   = $wpdb->get_results( "
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
 * Haal attachment-IDs uit ACF-image velden (numeric meta_value).
 */
function get_acf_images() {
    global $wpdb;
    $rows   = $wpdb->get_results( "
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
 * Combineer alle bronnen per attachment ID, voeg template-pages toe en filter duplicaten.
 */
function scan_duplicate_images() {
    $post_imgs     = get_images_from_posts_pages();
    $feat_imgs     = get_featured_images();
    $acf_imgs      = get_acf_images();
    $tpl_imgs      = mim_get_elementor_template_images();
    $tpl_page_map  = mim_map_elementor_templates_to_pages();

    // Merge usages per ID
    $all = array_merge_recursive( $post_imgs, $feat_imgs, $acf_imgs, $tpl_imgs );

    // Voeg pagina-conditions voor template-images
    foreach ( $tpl_imgs as $aid => $usages ) {
        foreach ( $usages as $u ) {
            if ( $u['type'] === 'elementor_template'
              && preg_match( '/ID (\d+)$/', $u['context'], $match )
            ) {
                $tpl_id = (int) $match[1];
                if ( ! empty( $tpl_page_map[ $tpl_id ] ) ) {
                    foreach ( $tpl_page_map[ $tpl_id ] as $pid ) {
                        $all[ $aid ][] = [
                            'type'    => 'elementor_template_page',
                            'post_id' => $pid,
                            'context' => sprintf( 'Toont via Template %d op Pagina %d', $tpl_id, $pid ),
                        ];
                    }
                }
            }
        }
    }

    // Filter alleen attachments met meer dan één gebruik
    $duplicates = [];
    foreach ( $all as $aid => $usages ) {
        if ( count( $usages ) > 1 ) {
            $duplicates[ $aid ] = $usages;
        }
    }

    return $duplicates;
}

/**
 * Alias voor compatibiliteit.
 */
function dius_scan_pages_for_duplicate_images() {
    return scan_duplicate_images();
}

/**
 * Weergave van de resultaten in een tabel (zonder checkboxes of bulk-delete).
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
       . esc_html__( 'Totaal aantal dubbele afbeeldingen gevonden:', 'modern-image-manager' )
       . '</strong> ' . esc_html( count( $results ) ) . '</p>';

    echo '<table class="wp-list-table widefat fixed striped dius-results-table">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Attachment', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Post(s) / Pagina(s)', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Bron', 'modern-image-manager' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $results as $aid => $usages ) {
        $url      = wp_get_attachment_url( $aid );
        $thumb    = wp_get_attachment_image( $aid, [80,80] );
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
                    case 'elementor_template':
                    case 'elementor_template_page':
                        $contexts[] = esc_html( $u['context'] );
                        break;
                }
            }
        }

        echo '<tr>';
        echo '<td><a href="' . esc_url( $url ) . '" target="_blank">' . $thumb . '</a></td>';
        echo '<td>' . implode( ', ', array_unique( $posts ) ) . '</td>';
        echo '<td>' . implode( ', ', array_unique( $contexts ) ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}