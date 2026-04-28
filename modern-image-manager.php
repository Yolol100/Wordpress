<?php
declare(strict_types=1);

/**
 * Plugin Name:       Modern Image Manager
 * Plugin URI:        https://webactueel.nl/
 * Description:       Beheer en bewerk media-informatie in WordPress. Nu met scan voor dubbele afbeeldingen uit WP, Elementor, ACF en WooCommerce.
 * Version:           1.1.2
 * Author:            Webactueel
 * Author URI:        https://webactueel.nl
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      8.1
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Text Domain:       modern-image-manager
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

// Plugin paths
define('MODERN_IMAGE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('MODERN_IMAGE_MANAGER_URL', plugin_dir_url(__FILE__));

// Laad vertalingen
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain(
        'modern-image-manager',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Laad benodigde pluginbestanden
add_action('plugins_loaded', function (): void {
    $includes = [
        'includes/modern-images/class-modern-image-manager.php',
        'includes/modern-images/class-ajax-handler.php',
        'includes/modern-images/export-media.php',
        'includes/modern-images/import-media.php',
        'includes/modern-images/zip-media.php',
        'includes/scan-images/scanner.php',
        'includes/modern-images/admin-table-functions.php',
        'includes/modern-images/ajax-scan-handler.php',
    ];

    foreach ($includes as $file) {
        $path = MODERN_IMAGE_MANAGER_PATH . $file;
        if (is_file($path)) {
            require_once $path;
        } else {
            error_log("Modern Image Manager: Bestand niet gevonden – {$file}");
        }
    }

    // Start hoofdfunctie
    if (class_exists('Modern_Image_Manager')) {
        Modern_Image_Manager::init();
    }
});

// Admin assets (CSS/JS) inladen, alleen op pluginpagina's.
add_action('admin_enqueue_scripts', function (string $hook): void {
    $allowed_pages = [
        'toplevel_page_modern-image-manager',
        'modern-image-manager_page_export-media',
        'modern-image-manager_page_import-media',
        'modern-image-manager_page_scan-duplicate-images',
        'modern-image-manager_page_zip-media',
    ];

    if (!in_array($hook, $allowed_pages, true)) {
        return;
    }

    wp_enqueue_style('modern-image-manager-roots', MODERN_IMAGE_MANAGER_URL . 'assets/css/roots.css', [], '1.1.2');
    wp_enqueue_style('modern-image-manager-styles', MODERN_IMAGE_MANAGER_URL . 'assets/css/style.css', ['modern-image-manager-roots'], '1.1.2');
    wp_enqueue_style('modern-image-manager-dark-mode', MODERN_IMAGE_MANAGER_URL . 'assets/css/dark-mode.css', ['modern-image-manager-styles'], '1.1.2');

    if ($hook === 'toplevel_page_modern-image-manager') {
        wp_enqueue_script('modern-image-manager-scripts', MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js', ['jquery'], '1.1.2', true);
        wp_localize_script('modern-image-manager-scripts', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('modern_image_manager_ajax'),
            'translations' => [
                'no_results' => __('Geen resultaten gevonden.', 'modern-image-manager'),
                'saved_successfully' => __('Opgeslagen!', 'modern-image-manager'),
                'deleted_successfully' => __('Item verwijderd!', 'modern-image-manager'),
                'bulk_deleted_successfully' => __('Items succesvol verwijderd.', 'modern-image-manager'),
                'confirm_delete' => __('Weet je zeker dat je dit item definitief wilt verwijderen?', 'modern-image-manager'),
                'confirm_bulk_delete' => __('Weet je zeker dat je de geselecteerde items definitief wilt verwijderen?', 'modern-image-manager'),
                'no_items_selected' => __('Geen items geselecteerd voor bulkactie.', 'modern-image-manager'),
            ],
        ]);
    }

    if ($hook === 'modern-image-manager_page_import-media') {
        wp_enqueue_script('modern-image-manager-import-media', MODERN_IMAGE_MANAGER_URL . 'assets/js/import-media.js', [], '1.1.2', true);
    }

    if ($hook === 'modern-image-manager_page_scan-duplicate-images') {
        wp_enqueue_style('scan-duplicate-images', MODERN_IMAGE_MANAGER_URL . 'assets/css/scan-duplicate-images.css', ['modern-image-manager-roots'], '1.1.2');
        wp_enqueue_script('scan-duplicate-images-script', MODERN_IMAGE_MANAGER_URL . 'assets/js/scan-duplicate-images.js', ['jquery'], '1.1.2', true);
        wp_localize_script('scan-duplicate-images-script', 'mimAjaxData', [
            'scanningText' => __('Afbeeldingen worden gescand... Een ogenblik geduld.', 'modern-image-manager'),
            'errorText'    => __('Er is een fout opgetreden tijdens het scannen. Probeer het opnieuw.', 'modern-image-manager'),
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('mim_scan_duplicates'),
        ]);
    }

    if ($hook === 'modern-image-manager_page_zip-media') {
        wp_enqueue_style('modern-image-manager-zip-export-style', MODERN_IMAGE_MANAGER_URL . 'assets/css/zip-export.css', ['modern-image-manager-roots'], '1.1.2');
        wp_enqueue_script('modern-image-manager-zip-export', MODERN_IMAGE_MANAGER_URL . 'assets/js/zip-export.js', ['jquery'], '1.1.2', true);
        wp_localize_script('modern-image-manager-zip-export', 'mim_ajax', [
            'ajax_url' => admin_url('admin-post.php'),
            'download_started_text' => __('Download gestart!', 'modern-image-manager'),
            'error_text' => __('Er is een fout opgetreden tijdens het maken van het ZIP-bestand.', 'modern-image-manager'),
        ]);
    }
});

/**
 * Veilige admin callback voor scan duplicate images.
 * Nonce + capability check.
 */
function scan_duplicate_images_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
    }
    include MODERN_IMAGE_MANAGER_PATH . 'templates/scan-duplicate-page.php';
}

/**
 * Veilige admin callback voor de media-table.
 * Nonce + capability check.
 */
function mim_render_admin_table_page(): void {
    if (!current_user_can('upload_files')) {
        wp_die(__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
    }
    include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
}

/**
 * Algemene helper om nonce en capability te checken bij POST-acties.
 * Kan zo worden gebruikt in AJAX- en admin-post handlers.
 */
function mim_check_nonce_and_capability(string $nonce_action, string $nonce_field, string $capability): void {
    if (
        !isset($_POST[$nonce_field]) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_field])), $nonce_action)
    ) {
        wp_send_json_error(['message' => __('Beveiligingscontrole mislukt.', 'modern-image-manager')], 403);
        exit;
    }
    if (!current_user_can($capability)) {
        wp_send_json_error(['message' => __('Je hebt geen rechten om deze actie uit te voeren.', 'modern-image-manager')], 403);
        exit;
    }
}