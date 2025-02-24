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
function modern_image_manager_load_textdomain(): void {
    load_plugin_textdomain( 'modern-image-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'modern_image_manager_load_textdomain' );

/**
 * Initialiseert de plugin.
 */
function mim_init(): void {
    $includes = [
        'includes/modern-images/class-modern-image-manager.php',
        'includes/modern-images/class-ajax-handler.php',
        'includes/modern-images/export-media.php',
        'includes/modern-images/import-media.php',
        'includes/scan-images/scanner.php', // ✅ Scanner wordt correct ingeladen
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
 * ✅ Laadt de admin CSS en JS voor alle pagina's.
 */
function mim_enqueue_admin_assets( $hook ): void {
    // Algemene pluginstijl en scripts laden
    wp_enqueue_style(
        'modern-image-manager-styles',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/style.css',
        [],
        '1.0.0',
        'all'
    );

    wp_enqueue_script(
        'modern-image-manager-scripts',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
        [ 'jquery' ],
        '1.0.0',
        true
    );

    wp_localize_script( 'modern-image-manager-scripts', 'modernImageManagerAjax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
    ] );

    // Laadt altijd de CSS & JS voor de scannerpagina, ongeacht de adminpagina
    wp_enqueue_style(
        'scan-duplicate-images',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
        [],
        '1.0.0',
        'all'
    );
    wp_enqueue_script(
        'scan-duplicate-images-script',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js',
        [ 'jquery' ],
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'mim_enqueue_admin_assets' );

/**
 * (Optioneel) Laadt frontend assets als dat nodig is.
 */
function mim_enqueue_frontend_assets(): void {
    // Laad assets voor de frontend (indien nodig).
}
add_action( 'wp_enqueue_scripts', 'mim_enqueue_frontend_assets' );

/**
 * Callback voor het renderen van de admin-tabelpagina.
 */
function mim_render_admin_table_page(): void {
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/modern-images/admin-table-functions.php';
    include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
}

/**
 * Render de pagina voor de duplicaten-scanner.
 */
function scan_duplicate_images_page(): void {
    include MODERN_IMAGE_MANAGER_PATH . 'templates/scan-duplicate-page.php';
}

/**
 * Functie om de scanner-functionaliteit in te laden.
 */
function include_scan_duplicate_images_functionality(): void {
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
}