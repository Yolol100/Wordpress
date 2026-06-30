<?php
declare(strict_types=1);

namespace ModernImageManager\Admin;

use ModernImageManager\Services\ExportService;
use ModernImageManager\Services\ImportService;
use ModernImageManager\Services\ZipService;

defined('ABSPATH') || exit;

final class Menu
{
    public function __construct(
        private ImportService $import,
        private ExportService $export,
        private ZipService $zip
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            __('Media beheren', 'modern-image-manager'),
            __('Mediatools', 'modern-image-manager'),
            'upload_files',
            'modern-image-manager',
            [$this, 'renderMediaPage'],
            'dashicons-admin-tools',
            20
        );

        remove_submenu_page('modern-image-manager', 'modern-image-manager');
    }

    public function renderMediaPage(): void
    {
        $this->import->maybeAddResultScript();
        $screen = isset($_POST['import_csv']) ? 'import' : 'media';
        $this->renderRoot($screen, 'upload_files');
    }

    private function renderRoot(string $screen, string $capability): void
    {
        if (! current_user_can($capability)) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }

        $mim_screen = $screen;
        require MODERN_IMAGE_MANAGER_PATH . 'templates/admin-root.php';
    }
}
