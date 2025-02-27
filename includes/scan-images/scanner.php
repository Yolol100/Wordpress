<?php
/**
 * Unified Admin Page en Scanner voor Duplicate Images.
 * Dit bestand combineert de functionaliteit van de admin-page (voor het tonen van de resultaten)
 * en de scan-logica (voor het ophalen en verwerken van afbeeldingen).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    return; // Zorg ervoor dat de gebruiker voldoende rechten heeft.
}

/**
 * Zorgt ervoor dat de CSS correct wordt ingeladen op de scanpagina.
 */
function mim_enqueue_admin_assets_for_scan_page( $hook ) {
    if ( $hook !== 'toplevel_page_modern-image-manager' && $hook !== 'modern-image-manager_page_scan-duplicate-images' ) {
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
 * Haalt afbeeldingen uit de content van berichten en pagina’s.
 */
function get_images_from_posts_pages() {
    global $wpdb;

    $query = "
        SELECT ID, post_content 
        FROM {$wpdb->posts} 
        WHERE post_type IN ('post', 'page') 
        AND post_status = 'publish'
    ";

    $results = $wpdb->get_results( $query );
    $images = [];

    foreach ( $results as $post ) {
        if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches ) ) {
            foreach ( $matches[1] as $image ) {
                $images[ $image ][] = [
                    'type'    => 'post_content',
                    'post_id' => $post->ID,
                ];
            }
        }
    }

    return $images;
}

/**
 * Haalt uitgelichte afbeeldingen op.
 */
function get_featured_images() {
    global $wpdb;

    $query = "
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_thumbnail_id'
    ";

    $results = $wpdb->get_results( $query );
    $images = [];

    foreach ( $results as $row ) {
        $image_url = wp_get_attachment_url( $row->meta_value );
        if ( $image_url ) {
            $images[ $image_url ][] = [
                'type'    => 'featured_image',
                'post_id' => $row->post_id,
            ];
        }
    }

    return $images;
}

/**
 * Haalt afbeeldingen uit ACF-velden op.
 * Deze functie sluit meta_key _thumbnail_id uit, zodat deze niet dubbel komt (als uitgelichte afbeelding).
 */
function get_acf_images() {
    global $wpdb;

    $query = "
        SELECT post_id, meta_key, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_value REGEXP '^[0-9]+$'
          AND meta_key NOT IN ('_thumbnail_id')
    ";

    $results = $wpdb->get_results( $query );
    $images = [];

    foreach ( $results as $row ) {
        $image_url = wp_get_attachment_url( $row->meta_value );
        if ( $image_url ) {
            $images[ $image_url ][] = [
                'type'      => 'acf_field',
                'post_id'   => $row->post_id,
                'acf_field' => $row->meta_key,
            ];
        }
    }

    return $images;
}

/**
 * Voegt alle afbeeldingen samen en zoekt duplicaten.
 */
function scan_duplicate_images() {
    $post_images     = get_images_from_posts_pages();
    $featured_images = get_featured_images();
    $acf_images      = get_acf_images();
    
    $all_images = array_merge_recursive( $post_images, $featured_images, $acf_images );
    
    $duplicates = [];
    foreach ( $all_images as $image_url => $usage ) {
        if ( count( $usage ) > 1 ) {
            $duplicates[ $image_url ] = $usage;
        }
    }
    
    return $duplicates;
}

/**
 * Alias voor compatibiliteit met eerdere aanroepen.
 */
function dius_scan_pages_for_duplicate_images() {
    return scan_duplicate_images();
}

/**
 * Toont de scanresultaten in een nette tabel, met een gecombineerde weergave van de herkomst.
 */
function dius_display_results( $results ) {
    if ( empty( $results ) ) {
        echo '<p class="mim-scan-page-description">' . esc_html__( 'Geen dubbele afbeeldingen gevonden.', 'modern-image-manager' ) . '</p>';
        return;
    }

    echo '<h2 class="mim-scan-page-title">' . esc_html__( 'Scan resultaten', 'modern-image-manager' ) . '</h2>';
    echo '<p class="mim-scan-page-description"><strong>' . esc_html__( 'Totaal aantal dubbele afbeeldingen gevonden:', 'modern-image-manager' ) . '</strong> ' . esc_html( count( $results ) ) . '</p>';

    echo '<table class="wp-list-table widefat fixed striped dius-results-table">';
    echo '<thead>
            <tr>
                <th style="width:30%;">' . esc_html__( 'Post(s)', 'modern-image-manager' ) . '</th>
                <th style="width:40%;">' . esc_html__( 'Afbeelding', 'modern-image-manager' ) . '</th>
                <th style="width:30%;">' . esc_html__( 'Bron', 'modern-image-manager' ) . '</th>
            </tr>
          </thead>';
    echo '<tbody>';

    foreach ( $results as $image_url => $usages ) {
        // Verzamel alle posts waarin de afbeelding voorkomt, gesorteerd op post_id (om duplicaten te voorkomen).
        $post_links = [];
        foreach ( $usages as $usage ) {
            if ( isset( $usage['post_id'] ) ) {
                $post_id = $usage['post_id'];
                $post_title = get_the_title( $post_id ) ? get_the_title( $post_id ) : esc_html__( 'Onbekend', 'modern-image-manager' );
                $post_link  = get_edit_post_link( $post_id ) ? get_edit_post_link( $post_id ) : '#';
                $post_links[ $post_id ] = '<a href="' . esc_url( $post_link ) . '" target="_blank">' . esc_html( $post_title ) . '</a>';
            }
        }
        // Maak een lijst van alle unieke posts (geordend op post_id).
        ksort( $post_links );
        $post_text = implode( ', ', $post_links );

        // Verzamel alle bron-informatie.
        $all_usage_types = [];
        foreach ( $usages as $usage ) {
            if ( isset( $usage['type'] ) ) {
                if ( $usage['type'] === 'post_content' ) {
                    $all_usage_types[] = esc_html__( 'Afbeelding in content (WordPress)', 'modern-image-manager' );
                } elseif ( $usage['type'] === 'featured_image' ) {
                    $all_usage_types[] = esc_html__( 'Uitgelichte afbeelding (WordPress)', 'modern-image-manager' );
                } elseif ( $usage['type'] === 'acf_field' && isset( $usage['acf_field'] ) ) {
                    $all_usage_types[] = esc_html__( 'ACF veld: ', 'modern-image-manager' ) . esc_html( $usage['acf_field'] );
                }
            }
        }
        $all_usage_types = array_unique( $all_usage_types );
        $usage_text = implode( ', ', $all_usage_types );

        echo "<tr>";
        echo "<td>" . $post_text . "</td>";
        echo "<td><a href='" . esc_url( $image_url ) . "' target='_blank'><img src='" . esc_url( $image_url ) . "' width='80'></a></td>";
        echo "<td>" . $usage_text . "</td>";
        echo "</tr>";
    }

    echo '</tbody></table>';
}
?>