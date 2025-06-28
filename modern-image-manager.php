<?php
declare(strict_types=1);

/**
 * Plugin Name:       Modern Image Manager
 * Plugin URI:        https://example.com/plugins/modern-image-manager/
 * Description:       Beheer en bewerk media-informatie in WordPress.
 * Version:           1.0.1
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

// Tekstdomein laden
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
        'includes/modern-images/zip-media.php',
        'includes/scan-images/scanner.php',
        'includes/modern-images/admin-table-functions.php',
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

// Admin assets inladen
add_action('admin_enqueue_scripts', function (string $hook): void {
    // Centraal variabelen bestand altijd eerst
    wp_enqueue_style(
        'modern-image-manager-roots',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/roots.css',
        [],
        '1.0.1'
    );

    // Basis styles laden, afhankelijk van roots.css
    wp_enqueue_style(
        'modern-image-manager-styles',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/style.css',
        ['modern-image-manager-roots'],
        '1.0.1'
    );

    wp_enqueue_style(
        'modern-image-manager-dark-mode',
        MODERN_IMAGE_MANAGER_URL . 'assets/css/dark-mode.css',
        ['modern-image-manager-styles'],
        '1.0.1'
    );

    wp_enqueue_script(
        'modern-image-manager-scripts',
        MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
        ['jquery'],
        '1.0.1',
        true
    );

    wp_localize_script('modern-image-manager-scripts', 'ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'translations' => [
            'no_results' => esc_js(__('Geen resultaten gevonden.', 'modern-image-manager')),
        ],
    ]);

    // Scan Duplicate Images pagina
    if (strpos($hook, 'scan-duplicate-images') !== false) {
        wp_enqueue_style(
            'scan-duplicate-images',
            MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css',
            ['modern-image-manager-roots'],
            '1.0.1'
        );
        wp_enqueue_script(
            'scan-duplicate-images-script',
            MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js',
            ['jquery'],
            '1.0.1',
            true
        );
        wp_localize_script(
            'scan-duplicate-images-script',
            'mim_scan_progress_nonce',
            [
                'no_duplicates_found'   => esc_js(__('Geen dubbele afbeeldingen gevonden.', 'modern-image-manager')),
                'found_duplicates_text' => esc_js(__('Duplicaten gevonden:', 'modern-image-manager')),
            ]
        );
    }

    // ZIP Export pagina – afhankelijk van roots.css
    if (strpos($hook, 'zip-media') !== false) {
        wp_enqueue_style(
            'modern-image-manager-zip-export-style',
            MODERN_IMAGE_MANAGER_URL . 'assets/css/zip-export.css',
            ['modern-image-manager-roots'],
            '1.0.0'
        );
        wp_enqueue_script(
            'modern-image-manager-zip-export',
            MODERN_IMAGE_MANAGER_URL . 'assets/js/zip-export.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script('modern-image-manager-zip-export', 'mim_ajax', [
            'ajax_url' => admin_url('admin-post.php'),
            'download_started_text' => __('Download gestart!', 'modern-image-manager'),
            'error_text' => __('Er is een fout opgetreden tijdens het maken van het ZIP-bestand.', 'modern-image-manager'),
        ]);
    }
});

// Frontend assets placeholder
add_action('wp_enqueue_scripts', function (): void {
    // (optioneel) frontend styles/scripts
});

// Admin tabelpagina renderen
function mim_render_admin_table_page(): void {
    include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
}

// Scannerpagina renderen
function scan_duplicate_images_page(): void {
    include MODERN_IMAGE_MANAGER_PATH . 'templates/scan-duplicate-page.php';
}

// Scanner functionaliteit los inladen
function include_scan_duplicate_images_functionality(): void {
    require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
}