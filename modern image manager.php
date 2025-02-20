<?php
/**
 * Plugin Name:       Modern Image Manager
 * Plugin URI:        https://example.com/plugins/modern-image-manager/
 * Description:       Beheer en bewerk media-informatie direct vanuit WordPress.
 * Version:           1.0.0
 * Author:            Webactueel
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Tested up to:      6.4
 * Text Domain:       modern-image-manager
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkomt directe toegang
}

// Defineer plugin-constanten
define( 'MODERN_IMAGE_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'MODERN_IMAGE_MANAGER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Laadt de vertaalbestanden voor de plugin.
 */
function modern_image_manager_load_textdomain() {
    load_plugin_textdomain( 'modern-image-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'modern_image_manager_load_textdomain' );

/**
 * Initialiseert de plugin.
 */
function mim_init(): void {
    // Inclusie van overige benodigde bestanden
    $includes = [
        'includes/class-modern-image-manager.php',
        'includes/class-ajax-handler.php',
        'includes/export-media.php',
        'includes/import-media.php',
    ];

    foreach ( $includes as $file ) {
        $path = MODERN_IMAGE_MANAGER_PATH . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( "Modern Image Manager: Bestand niet gevonden - $file" );
        }
    }

    // Initialiseer de hoofdklasse als deze bestaat.
    if ( class_exists( 'Modern_Image_Manager' ) ) {
        Modern_Image_Manager::init();
    }
}
add_action( 'plugins_loaded', 'mim_init' );

/**
 * Laadt alleen style.css in plaats van meerdere CSS-bestanden.
 */
function mim_enqueue_admin_assets( $hook ) {
    // Verkrijg het huidige scherm
    $screen = get_current_screen();

    // Definieer de toegestane scherm-ID's voor de plugin:
    $allowed_screens = [
        'toplevel_page_modern-image-manager',
        'modern-image_page_import-media',
        'modern-image_page_export-media'
    ];

    if ( isset( $screen->id ) && in_array( $screen->id, $allowed_screens, true ) ) {
        // Laad alleen de algemene stijl voor de plugin (style.css)
        wp_enqueue_style(
            'modern-image-manager-styles',
            MODERN_IMAGE_MANAGER_URL . 'assets/css/style.css',
            [], 
            '1.0.0', 
            'all'
        );

        // Laad de JavaScript-bestanden
        wp_enqueue_script(
            'modern-image-manager-scripts',
            MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        // Voeg AJAX-URL toe aan het script
        wp_localize_script( 'modern-image-manager-scripts', 'modernImageManagerAjax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ] );
    }
}
add_action( 'admin_enqueue_scripts', 'mim_enqueue_admin_assets' );

/**
 * (Optioneel) Laadt frontend assets als dat nodig is.
 */
function mim_enqueue_frontend_assets() {
    // Als je frontend-functionaliteit hebt, laad dan de assets op de juiste voorwaarde.
    // Voorbeeld:
    // if ( is_singular('modern_media') ) {
    //     wp_enqueue_style(
    //         'modern-image-manager-frontend-styles',
    //         MODERN_IMAGE_MANAGER_URL . 'assets/css/frontend.css',
    //         [], 
    //         '1.0.0',
    //         'all'
    //     );
    //     wp_enqueue_script(
    //         'modern-image-manager-frontend-scripts',
    //         MODERN_IMAGE_MANAGER_URL . 'assets/js/frontend.js',
    //         [ 'jquery' ],
    //         '1.0.0',
    //         true
    //     );
    // }
}
add_action( 'wp_enqueue_scripts', 'mim_enqueue_frontend_assets' );

/**
 * Callback voor het renderen van de admin-tabelpagina.
 */
function mim_render_admin_table_page(): void {
    // Laad de PHP-logica voor de admin-tabel
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/admin-table-functions.php';
    // Laad het templatebestand dat uitsluitend de HTML bevat
    include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
}