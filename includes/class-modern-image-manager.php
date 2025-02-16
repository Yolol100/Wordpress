<?php

class Modern_Image_Manager {
    /**
     * Initialiseert de Modern Image Manager.
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Voegt het hoofdmenu en de submenus toe in het WordPress admin dashboard.
     */
    public static function add_admin_menu(): void {
        // Hoofdmenu
        add_menu_page(
            'Modern Image Manager',           // Paginatitel
            'Modern Image',                   // Menutitel
            'manage_options',                 // Bevoegdheden
            'modern-image-manager',           // Menu slug
            [self::class, 'render_admin_page'], // Callback
            'dashicons-media-document',       // Icon
            20                                // Positie
        );

        // Submenu voor export
        add_submenu_page(
            'modern-image-manager',               // Hoofdmenu slug
            'Exporteer Media',                    // Paginatitel
            'Exporteren',                         // Submenu naam
            'manage_options',                     // Bevoegdheden
            'export-media',                       // Submenu slug
            ['Modern_Image_Manager_Export', 'render_export_page'] // Callback
        );

        // Submenu voor import
        add_submenu_page(
            'modern-image-manager',               // Hoofdmenu slug
            'Importeer Media',                    // Paginatitel
            'Importeren',                         // Submenu naam
            'manage_options',                     // Bevoegdheden
            'import-media',                       // Submenu slug
            ['Modern_Image_Manager_Import', 'render_import_page'] // Callback
        );
    }

    /**
     * Rendert de adminpagina met de mediatabel.
     */
    public static function render_admin_page(): void {
        include MODERN_IMAGE_MANAGER_PATH . 'templates/admin-table.php';
    }

    /**
     * Laadt de benodigde CSS- en JavaScript-assets voor de adminpagina.
     *
     * @param string $hook De huidige adminpagina hook.
     */
    public static function enqueue_assets(string $hook): void {
        // Controleer of we op een relevante adminpagina zijn
        if (!in_array($hook, [
            'toplevel_page_modern-image-manager', 
            'modern-image-manager_page_export-media', 
            'modern-image-manager_page_import-media'
        ], true)) {
            return;
        }

        // Laad CSS
        wp_enqueue_style(
            'modern-image-manager-styles',
            MODERN_IMAGE_MANAGER_URL . 'assets/css/styles.css',
            [],
            '1.0.0'
        );

        // Laad JavaScript
        wp_enqueue_script(
            'modern-image-manager-scripts',
            MODERN_IMAGE_MANAGER_URL . 'assets/js/scripts.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Voeg AJAX-URL toe aan het script
        wp_localize_script('modern-image-manager-scripts', 'modernImageManagerAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}