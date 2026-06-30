<?php
declare(strict_types=1);

namespace ModernImageManager;

use ModernImageManager\Admin\Menu;
use ModernImageManager\Assets\AdminAssets;
use ModernImageManager\Api\MediaController;
use ModernImageManager\Api\ScanController;
use ModernImageManager\Services\ExportService;
use ModernImageManager\Services\ImportService;
use ModernImageManager\Services\ZipService;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(): void
    {
        $import = new ImportService();
        $export = new ExportService();
        $zip = new ZipService();

        (new Menu($import, $export, $zip))->register();
        (new AdminAssets())->register();
        (new MediaController())->register();
        (new ScanController())->register();

        add_action('admin_post_mim_export_csv', [$export, 'handleDownload']);
        add_action('admin_post_modern_image_manager_zip_export', [$zip, 'handleDownload']);
    }
}
