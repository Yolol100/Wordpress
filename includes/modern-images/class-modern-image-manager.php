<?php
// File: class-main.php
// Plugin hoofdklasse met admin menu en assets

declare(strict_types=1);
defined('ABSPATH') || exit;

final class Modern_Image_Manager
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    // Voeg hoofd- en submenu's toe
    public static function add_admin_menu(): void
    {
        add_menu_page(
            __('Modern Image Manager', 'modern-image-manager'),
            __('Modern Image', 'modern-image-manager'),
            'upload_files',
            'modern-image-manager',
            'mim_render_admin_table_page',
            'dashicons-media-document',
            20
        );

        remove_submenu_page('modern-image-manager', 'modern-image-manager');

        self::add_submenu_item(
            __('Exporteer Media', 'modern-image-manager'),
            __('Exporteren', 'modern-image-manager'),
            'export-media',
            ['Modern_Image_Manager_Export', 'render_export_page']
        );

        self::add_submenu_item(
            __('Importeer Media', 'modern-image-manager'),
            __('Importeren', 'modern-image-manager'),
            'import-media',
            ['Modern_Image_Manager_Import', 'render_import_page']
        );

        self::add_submenu_item(
            __('Scan Duplicate Images', 'modern-image-manager'),
            __('Scan Duplicate Images', 'modern-image-manager'),
            'scan-duplicate-images',
            'scan_duplicate_images_page'
        );

        // Nieuw submenu "Zip" toegevoegd
        self::add_submenu_item(
            __('Maak ZIP van media', 'modern-image-manager'),
            __('Zip', 'modern-image-manager'),
            'zip-media',
            ['Modern_Image_Manager_Zip', 'render_zip_page']
        );
    }

    // Helper submenu toevoegen
    private static function add_submenu_item(string $page_title, string $menu_title, string $slug, callable|string $callback): void
    {
        add_submenu_page(
            'modern-image-manager',
            $page_title,
            $menu_title,
            'upload_files',
            $slug,
            $callback
        );
    }

    // Enqueue assets op juiste pagina's, inclusief nieuwe zip-media pagina
    public static function enqueue_assets(string $hook): void
    {
        $allowed_pages = [
            'toplevel_page_modern-image-manager',
            'modern-image-manager_page_export-media',
            'modern-image-manager_page_import-media',
            'modern-image-manager_page_scan-duplicate-images',
            'modern-image-manager_page_zip-media', // Toegevoegd voor ZIP pagina
        ];

        if (!in_array($hook, $allowed_pages, true)) {
            return;
        }

        // Assets worden globaal ingeladen via een globale asset-functie.
        // Hier eventueel specifieke scripts/styles toevoegen voor de pagina's.
    }
}

Modern_Image_Manager::init();