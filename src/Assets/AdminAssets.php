<?php
declare(strict_types=1);

namespace ModernImageManager\Assets;

defined('ABSPATH') || exit;

final class AdminAssets
{
    private const ALLOWED_PAGES = [
        'toplevel_page_modern-image-manager' => 'media',
        'modern-image-manager_page_export-media' => 'export-media',
        'modern-image-manager_page_import-media' => 'import-media',
        'modern-image-manager_page_scan-duplicate-images' => 'scan-duplicate-images',
        'modern-image-manager_page_zip-media' => 'zip-media',
    ];

    private const PAGE_SCREENS = [
        'modern-image-manager' => 'media',
        'export-media' => 'export-media',
        'import-media' => 'import-media',
        'scan-duplicate-images' => 'scan-duplicate-images',
        'zip-media' => 'zip-media',
    ];

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        $screen = self::ALLOWED_PAGES[$hook] ?? $this->screenFromRequest();
        if ($screen === null) {
            return;
        }

        $assetFile = MODERN_IMAGE_MANAGER_PATH . 'build/admin/index.asset.php';
        $asset = is_file($assetFile) ? require $assetFile : ['dependencies' => ['wp-element', 'wp-api-fetch', 'wp-i18n'], 'version' => MODERN_IMAGE_MANAGER_VERSION];

        $styleFile = MODERN_IMAGE_MANAGER_PATH . 'build/admin/style-index.css';
        $scriptFile = MODERN_IMAGE_MANAGER_PATH . 'build/admin/index.js';
        $styleVersion = is_file($styleFile) ? MODERN_IMAGE_MANAGER_VERSION . '-' . (string) filemtime($styleFile) : (string) $asset['version'];
        $scriptVersion = is_file($scriptFile) ? MODERN_IMAGE_MANAGER_VERSION . '-' . (string) filemtime($scriptFile) : (string) $asset['version'];
        $styleDependencies = wp_style_is('wp-components', 'registered') ? ['wp-components'] : [];

        wp_enqueue_style('modern-image-manager-admin', MODERN_IMAGE_MANAGER_URL . 'build/admin/style-index.css', $styleDependencies, $styleVersion);
        wp_enqueue_script('modern-image-manager-admin', MODERN_IMAGE_MANAGER_URL . 'build/admin/index.js', (array) $asset['dependencies'], $scriptVersion, true);
        wp_script_add_data('modern-image-manager-admin', 'strategy', 'defer');

        wp_set_script_translations('modern-image-manager-admin', 'modern-image-manager', MODERN_IMAGE_MANAGER_PATH . 'languages');

        $config = [
            'screen' => $screen,
            'restUrl' => esc_url_raw(rest_url('modern-image-manager/v1')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'adminPostUrl' => admin_url('admin-post.php'),
            'exportNonce' => wp_create_nonce('mim_export_csv'),
            'importNonce' => wp_create_nonce('mim_import_csv'),
            'zipNonce' => wp_create_nonce('mim_zip_export_nonce'),
            'pluginUrl' => MODERN_IMAGE_MANAGER_URL,
            'version' => MODERN_IMAGE_MANAGER_VERSION,
            'zipAvailable' => class_exists(\ZipArchive::class),
            'translations' => [
                'confirmDelete' => __('Weet je zeker dat je dit item wilt verwijderen? Afhankelijk van je WordPress-instellingen gaat het naar de prullenbak of wordt het verwijderd.', 'modern-image-manager'),
                'confirmBulkDelete' => __('Weet je zeker dat je %d geselecteerde items wilt verwijderen? Afhankelijk van je WordPress-instellingen gaan ze naar de prullenbak of worden ze verwijderd.', 'modern-image-manager'),
                'saved' => __('Opgeslagen.', 'modern-image-manager'),
                'deleted' => __('Verwijderd.', 'modern-image-manager'),
                'loading' => __('Laden...', 'modern-image-manager'),
                'error' => __('Er is iets misgegaan.', 'modern-image-manager'),
                'missingFile' => __('Kies eerst een CSV-bestand.', 'modern-image-manager'),
            ],
        ];

        wp_add_inline_script(
            'modern-image-manager-admin',
            'window.ModernImageManagerAdmin = ' . wp_json_encode($config) . ';',
            'before'
        );
    }
    private function screenFromRequest(): ?string
    {
        if (! isset($_GET['page'])) {
            return null;
        }

        $page = sanitize_key(wp_unslash($_GET['page']));

        return self::PAGE_SCREENS[$page] ?? null;
    }
}
