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
 */

if (!defined('ABSPATH')) {
    exit; // Voorkomt directe toegang
}

// Defineer plugin-constanten
define('MODERN_IMAGE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('MODERN_IMAGE_MANAGER_URL', plugin_dir_url(__FILE__));

/**
 * Initialiseert de plugin.
 *
 * @return void
 */
function mim_init(): void {
    // Vereiste bestanden includeren
    $includes = [
        'includes/class-modern-image-manager.php',
        'includes/class-ajax-handler.php',
        'includes/export-media.php',
        'includes/import-media.php',
    ];

    foreach ($includes as $file) {
        $path = MODERN_IMAGE_MANAGER_PATH . $file;
        if (file_exists($path)) {
            require_once $path;
        } else {
            error_log("Modern Image Manager: Bestand niet gevonden - $file");
        }
    }

    // Controleer en initialiseer de hoofdklasse
    if (class_exists('Modern_Image_Manager')) {
        Modern_Image_Manager::init();
    }
}
add_action('plugins_loaded', 'mim_init');

/**
 * Laadt globale CSS- en JavaScript-assets.
 *
 * @return void
 */
function mim_enqueue_global_assets(): void {
    // CSS-bestand laden
    wp_enqueue_style(
        'mim-global-styles',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/styles.css',
        [],
        '1.0.0',
        'all' // Media-parameter toegevoegd voor expliciete compatibiliteit
    );

    // JavaScript-bestand laden
    wp_enqueue_script(
        'mim-global-scripts',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
        ['jquery'], // Afhankelijk van jQuery, verwijder als dit niet nodig is
        '1.0.0',
        true // Laadt het script in de footer
    );
}
add_action('wp_enqueue_scripts', 'mim_enqueue_global_assets'); // Voor de front-end
add_action('admin_enqueue_scripts', 'mim_enqueue_global_assets'); // Voor de admin