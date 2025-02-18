<?php
class Modern_Image_Manager {
    /**
     * Initialiseert de Modern Image Manager.
     */
    public static function init(): void {
        add_action( 'admin_menu', [ self::class, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
    }

    /**
     * Voegt het hoofdmenu en de submenus toe in het WordPress admin dashboard.
     */
    public static function add_admin_menu(): void {
        // Voeg het top-level menu toe.
        add_menu_page(
            'Modern Image Manager',             // Paginatitel
            'Modern Image',                     // Menutitel
            'manage_options',                   // Bevoegdheden
            'modern-image-manager',             // Menu slug
            'mim_render_admin_table_page',      // Callback (extern gedefinieerd in de hoofdplugin)
            'dashicons-media-document',         // Icon
            20                                  // Positie
        );

        // Verwijder het automatisch gegenereerde duplicaatsubmenu-item.
        remove_submenu_page( 'modern-image-manager', 'modern-image-manager' );

        // Voeg submenus toe.
        add_submenu_page(
            'modern-image-manager',                       // Hoofdmenu slug
            'Exporteer Media',                            // Paginatitel
            'Exporteren',                                 // Submenu naam
            'manage_options',                             // Bevoegdheden
            'export-media',                               // Submenu slug
            [ 'Modern_Image_Manager_Export', 'render_export_page' ] // Callback
        );

        add_submenu_page(
            'modern-image-manager',                       // Hoofdmenu slug
            'Importeer Media',                            // Paginatitel
            'Importeren',                                 // Submenu naam
            'manage_options',                             // Bevoegdheden
            'import-media',                               // Submenu slug
            [ 'Modern_Image_Manager_Import', 'render_import_page' ] // Callback
        );
    }

    /**
     * Laadt de benodigde CSS- en JavaScript-assets voor de adminpagina.
     *
     * @param string $hook De huidige adminpagina hook.
     */
    public static function enqueue_assets( string $hook ): void {
        // Controleer of we op een relevante adminpagina zijn
        if ( ! in_array( $hook, [
            'toplevel_page_modern-image-manager',
            'modern-image-manager_page_export-media',
            'modern-image-manager_page_import-media'
        ], true ) ) {
            return;
        }

        // Assets worden al globally ingeladen via mim_enqueue_global_assets().
        // Deze functie kan indien gewenst extra admin-specifieke assets laden.
    }
}