<?php
declare(strict_types=1);

/**
 * Plugin Name:       Modern Image Manager
 * Plugin URI:        https://example.com/plugins/modern-image-manager/
 * Description:       Beheer en bewerk media-informatie in WordPress.
 * Version:           1.0.0
 * Author:            Webactueel
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      8.2
 * Requires at least: 6.0
 * Tested up to:      6.4
 * Text Domain:       modern-image-manager
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

// Basisconstanten
define('MODERN_IMAGE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('MODERN_IMAGE_MANAGER_URL', plugin_dir_url(__FILE__));

// Tekstdomein
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain(
        'modern-image-manager',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Plugin initialisatie
add_action('plugins_loaded', function (): void {
    $includes = [
        'includes/modern-images/class-modern-image-manager.php',
        'includes/modern-images/class-ajax-handler.php',
        'includes/modern-images/export-media.php',
        'includes/modern-images/import-media.php',
        'includes/scan-images/scanner.php',
    ];

    foreach ($includes as $file) {
        $path = MODERN_IMAGE_MANAGER_PATH . $file;
        if (is_file($path)) {
            require_once $path;
        } else {
            error_log("Modern Image Manager: Bestand niet gevonden – {$file}");
        }
    }

    if (class_exists(Modern_Image_Manager::class)) {
        Modern_Image_Manager::init();
    }
});

// Admin assets
add_action('admin_enqueue_scripts', function (string $hook): void {
    wp_enqueue_style(
        'modern-image-manager-styles',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/style.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'modern-image-manager-scripts',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script('modern-image-manager-scripts', 'ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'translations' => [
            'no_results' => esc_js(__('Geen resultaten gevonden.', 'modern-image-manager')),
        ],
    ]);

    wp_enqueue_style(
        'scan-duplicate-images',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'scan-duplicate-images-script',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Localizeer de nonce en vertalingen voor de scanpagina
    wp_localize_script(
        'scan-duplicate-images-script',
        'mim_scan_progress_nonce',
        [
            'nonce'                 => wp_create_nonce('mim_scan_progress_nonce'),
            'no_duplicates_found'   => esc_js(__('Geen dubbele afbeeldingen gevonden.', 'modern-image-manager')),
            'found_duplicates_text' => esc_js(__('Duplicaten gevonden:', 'modern-image-manager')),
        ]
    );
});

// Frontend assets (optioneel)
add_action('wp_enqueue_scripts', function (): void {
    // Toekomstige ondersteuning
});

// Admin tabelpagina
function mim_render_admin_table_page(): void {
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/modern-images/admin-table-functions.php';
    include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
}

// Scannerpagina
function scan_duplicate_images_page(): void {
    include MODERN_IMAGE_MANAGER_PATH . 'templates/scan-duplicate-page.php';
}

// Scannerfunctionaliteit los
function include_scan_duplicate_images_functionality(): void {
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
}