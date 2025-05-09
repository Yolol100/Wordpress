<?php
/**
 * includes/scan-images/scanner.php
 * Unified Admin Page en Scanner voor Duplicate Images,
 * uitgebreid met support voor Elementor-templates
 * én verificatie op echte WP-attachments (via attachment IDs),
 * plus checkboxes voor bulk-delete in de resultaten.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    return; // Zorg ervoor dat de gebruiker voldoende rechten heeft.
}

/**
 * Laad de CSS én JS voor de scanpagina.
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

    // **Toegevoegd: laad de JS voor bulk-select en knop enable/disable**
    wp_enqueue_script(
        'scan-duplicate-images-js',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js',
        [],    // geen jQuery-afhankelijkheid
        '1.0.0',
        true   // in footer laden
    );
}
add_action( 'admin_enqueue_scripts', 'mim_enqueue_admin_assets_for_scan_page' );

/**
 * Verwijdert image size suffix (-300x200) uit een URL.
 */
function mim_strip_image_size_suffix( string $url ): string {
    return preg_replace( '/-\d+x\d+(?=\.\w{3,4}$)/', '', $url );
}

/**
 * Recursief zoeken in Elementor-widget-data en omzet naar attachment ID.
 * Controleert op image.id, achtergrond-id en fallback via URL (met suffix stripping).
 */
function mim_extract_images_from_elementor_data( $elements, & $result, $context ) {
    foreach ( (array) $elements as $el ) {
        $aid = 0;
        // widget image ID
        if ( ! empty( $el['settings']['image']['id'] ) ) {
            $aid = absint( $el['settings']['image']['id'] );
        }
        // widget image URL fallback
        if ( ! $aid && ! empty( $el['settings']['image']['url'] ) ) {
            $url = mim_strip_image_size_suffix( $el['settings']['image']['url'] );
            $aid = attachment_url_to_postid( $url );
        }
        // background image ID
        if ( ! $aid && ! empty( $el['settings']['background_image']['id'] ) ) {
            $aid = absint( $el['settings']['background_image']['id'] );
        }
        // background image URL fallback
        if ( ! $aid && ! empty( $el['settings']['background_image']['url'] ) ) {
            $url = mim_strip_image_size_suffix( $el['settings']['background_image']['url'] );
            $aid = attachment_url_to_postid( $url );
        }

        if ( $aid ) {
            $result[ $aid ][] = [
                'type'    => 'elementor_template',
                'context' => $context,
            ];
        }

        if ( ! empty( $el['elements'] ) ) {
            mim_extract_images_from_elementor_data( $el['elements'], $result, $context );
        }
    }
}

/**
 * Haal alle attachment-IDs uit opgeslagen Elementor-templates.
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
 * Haal attachment-IDs uit inline <img> tags, srcset, gallery-shortcodes en Elementor-shortcodes.
 * Negeert afbeeldingen die niet in de mediabibliotheek staan.
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
        $content = $post->post_content;

        // inline <img>
        if ( preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $content, $m ) ) {
            foreach ( $m[1] as $url ) {
                $url = mim_strip_image_size_suffix( $url );
                if ( $aid = attachment_url_to_postid( $url ) ) {
                    $images[ $aid ][] = [
                        'type'    => 'post_content',
                        'post_id' => $post->ID,
                    ];
                }
            }
        }

        // srcset
        if ( preg_match_all( '/srcset=[\'"]([^\'"]+)[\'"]/i', $content, $s ) ) {
            foreach ( $s[1] as $srcset ) {
                foreach ( explode( ',', $srcset ) as $pair ) {
                    list( $url ) = preg_split( '/\s+/', trim( $pair ) );
                    $url = mim_strip_image_size_suffix( $url );
                    if ( $aid = attachment_url_to_postid( $url ) ) {
                        $images[ $aid ][] = [
                            'type'    => 'post_srcset',
                            'post_id' => $post->ID,
                        ];
                    }
                }
            }
        }

        // [gallery ids="..."]
        if ( preg_match_all( '/\[gallery\s+ids=[\'"]([^\'"]+)[\'"]/', $content, $g ) ) {
            foreach ( explode( ',', $g[1][0] ) as $aid ) {
                $aid = absint( $aid );
                if ( $aid && get_post_mime_type( $aid ) ) {
                    $images[ $aid ][] = [
                        'type'    => 'gallery_shortcode',
                        'post_id' => $post->ID,
                    ];
                }
            }
        }

        // [elementor-template]
        if ( preg_match_all( '/\[elementor-template\s+id=[\'"](\d+)[\'"]/', $content, $s ) ) {
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
 * Haal attachment-IDs uit uitgelichte afbeeldingen.
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
 * Haal attachment-IDs uit ACF-image velden.
 */
function get_acf_images() {
    global $wpdb;
    $rows   = $wpdb->get_results( "
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_value REGEXP '^[0-9]+|^a:' 
          AND meta_key NOT IN ('_thumbnail_id')
    " );
    $images = [];
    foreach ( $rows as $row ) {
        $value = maybe_unserialize( $row->meta_value );
        $ids   = is_array( $value ) ? $value : [ (int) $value ];
        foreach ( $ids as $aid ) {
            $aid = absint( $aid );
            if ( $aid && get_post_mime_type( $aid ) ) {
                $images[ $aid ][] = [
                    'type'      => 'acf_field',
                    'post_id'   => $row->post_id,
                    'acf_field' => $row->meta_key,
                ];
            }
        }
    }
    return $images;
}

/**
 * Scan en vind duplicaten op basis van attachment gebruik.
 */
function scan_duplicate_images() {
    $all = array_merge_recursive(
        get_images_from_posts_pages(),
        get_featured_images(),
        get_acf_images(),
        mim_get_elementor_template_images()
    );

    $tpl_map = mim_map_elementor_templates_to_pages();
    foreach ( mim_get_elementor_template_images() as $aid => $usages ) {
        foreach ( $usages as $u ) {
            if ( preg_match( '/ID (\d+)$/', $u['context'], $m ) ) {
                $tpl_id = (int) $m[1];
                if ( ! empty( $tpl_map[ $tpl_id ] ) ) {
                    foreach ( $tpl_map[ $tpl_id ] as $pid ) {
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

    $duplicates = [];
    foreach ( $all as $aid => $usages ) {
        if ( count( $usages ) > 1 ) {
            $duplicates[ $aid ] = $usages;
        }
    }
    return $duplicates;
}

/**
 * Alias.
 */
function dius_scan_pages_for_duplicate_images() {
    return scan_duplicate_images();
}

/**
 * Render alleen de tabel met checkboxes (zonder eigen form, zonder header of aantal).
 * Aangepast: altijd robuust de attachment tonen indien mogelijk.
 */
function dius_display_results( $results ) {
    if ( empty( $results ) ) {
        echo '<p class="mim-scan-page-description">'
           . esc_html__( 'Geen dubbele afbeeldingen gevonden.', 'modern-image-manager' )
           . '</p>';
        return;
    }

    // Alleen de tabel, geen kop of aantal
    echo '<table class="wp-list-table widefat fixed striped dius-results-table">';
    echo '<thead><tr>';
    echo '<th style="width:2%;"><input type="checkbox" id="select_all"></th>';
    echo '<th>' . esc_html__( 'Attachment', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Post(s) / Pagina(s)', 'modern-image-manager' ) . '</th>';
    echo '<th>' . esc_html__( 'Bron', 'modern-image-manager' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $results as $aid => $usages ) {
        $url = wp_get_attachment_url( $aid );
        $thumb = '';
        // Toon alléén afbeelding als het een echte afbeelding is
        if ( wp_attachment_is_image( $aid ) ) {
            $thumb = wp_get_attachment_image( $aid, [80,80] );
            // Fallback naar 'full' als thumb niet lukt
            if ( ! $thumb ) {
                $image_url = wp_get_attachment_image_url( $aid, 'full' );
                if ( $image_url ) {
                    $thumb = '<img src="' . esc_url( $image_url ) . '" style="max-width:80px;height:auto;" />';
                }
            }
        } else {
            $thumb = '<span style="color:#aaa;">(' . esc_html__( 'Geen afbeelding', 'modern-image-manager' ) . ')</span>';
        }

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
                    case 'post_srcset':
                        $contexts[] = __( 'In srcset', 'modern-image-manager' );
                        break;
                    case 'gallery_shortcode':
                        $contexts[] = __( 'Gallery shortcode', 'modern-image-manager' );
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
        echo '<td><input type="checkbox" class="delete-checkbox" name="delete_ids[]" value="' . esc_attr( $aid ) . '"></td>';
        echo '<td><a href="' . esc_url( $url ) . '" target="_blank">' . $thumb . '</a></td>';
        echo '<td>' . implode( ', ', array_unique( $posts ) ) . '</td>';
        echo '<td>' . implode( ', ', array_unique( $contexts ) ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}