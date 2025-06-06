<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class Modern_Image_Manager
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function add_admin_menu(): void
    {
        add_menu_page(
            __('Modern Image Manager', 'modern-image-manager'),
            __('Modern Image', 'modern-image-manager'),
            'manage_options',
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
    }

    private static function add_submenu_item(string $page_title, string $menu_title, string $slug, callable|string $callback): void
    {
        add_submenu_page(
            'modern-image-manager',
            $page_title,
            $menu_title,
            'manage_options',
            $slug,
            $callback
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, [
            'toplevel_page_modern-image-manager',
            'modern-image-manager_page_export-media',
            'modern-image-manager_page_import-media',
            'modern-image-manager_page_scan-duplicate-images',
        ], true)) {
            return;
        }

        // Assets worden globaal ingeladen via een globale asset-functie.
    }
}
