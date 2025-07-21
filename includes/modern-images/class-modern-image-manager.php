<?php
// File: includes/modern-images/class-modern-image-manager.php

declare(strict_types=1);
defined('ABSPATH') || exit;

final class Modern_Image_Manager
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Voeg hoofd- en submenu's toe aan het admin menu.
     */
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

        // Verwijder dubbel hoofdsubmenu
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

        self::add_submenu_item(
            __('Maak ZIP van media', 'modern-image-manager'),
            __('Zip', 'modern-image-manager'),
            'zip-media',
            ['Modern_Image_Manager_Zip', 'render_zip_page']
        );
    }

    /**
     * Helper om submenu-items toe te voegen.
     *
     * @param string $page_title
     * @param string $menu_title
     * @param string $slug
     * @param callable|string $callback
     */
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

    /**
     * Laad admin assets alleen op specifieke plugin pagina's.
     *
     * @param string $hook
     */
    public static function enqueue_assets(string $hook): void
    {
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

        // Hier admin scripts en styles enqueue'en indien nodig.
        // Bijvoorbeeld:
        // wp_enqueue_script('modern-image-manager-admin', plugins_url('assets/js/admin.js', __FILE__), ['jquery'], '1.0.0', true);
        // wp_enqueue_style('modern-image-manager-style', plugins_url('assets/css/style.css', __FILE__), [], '1.0.0');
    }
}

Modern_Image_Manager::init();