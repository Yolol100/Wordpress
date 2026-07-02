<?php
declare(strict_types=1);

namespace ModernImageManager\Services;

use ModernImageManager\Repositories\AttachmentRepository;
use ModernImageManager\Support\Capabilities;
use ModernImageManager\Support\FileSecurity;
use ZipArchive;

defined('ABSPATH') || exit;

final class ZipService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    public function __construct(
        private ?AttachmentRepository $attachments = null,
        private ?FileSecurity $files = null
    ) {
        $this->attachments = $attachments ?: new AttachmentRepository();
        $this->files = $files ?: new FileSecurity();
    }

    public function renderPage(): void
    {
        if (! current_user_can(Capabilities::manage())) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }

        $mim_screen = 'zip-media';
        require MODERN_IMAGE_MANAGER_PATH . 'templates/admin-root.php';
    }

    public function handleDownload(): void
    {
        if (! current_user_can(Capabilities::manage())) {
            wp_die(esc_html__('Je hebt geen toestemming om deze actie uit te voeren.', 'modern-image-manager'));
        }

        check_admin_referer('mim_zip_export_nonce', 'mim_zip_export_nonce_field');

        if (! class_exists(ZipArchive::class)) {
            wp_die(esc_html__('ZipArchive is niet beschikbaar op deze server.', 'modern-image-manager'));
        }

        $zipFilePath = wp_tempnam('mim-media-export-' . wp_generate_uuid4() . '.zip');
        if (! $zipFilePath) {
            wp_die(esc_html__('Kon tijdelijk exportbestand niet aanmaken.', 'modern-image-manager'));
        }
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            if (is_file($zipFilePath)) {
                unlink($zipFilePath);
            }
            wp_die(esc_html__('Kon het ZIP-bestand niet aanmaken.', 'modern-image-manager'));
        }

        $found = 0;
        $page = 1;
        do {
            $query = $this->attachments->queryImageIds([
                'posts_per_page' => 250,
                'paged' => $page,
            ]);

            foreach ((array) $query->posts as $attachmentId) {
                $filePath = $this->attachments->attachedFile((int) $attachmentId);
                if (! $filePath || ! $this->files->isAllowedImageFile($filePath)) {
                    continue;
                }

                $filename = basename($filePath);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                    continue;
                }

                $archiveName = 'Plaatjes/' . (int) $attachmentId . '-' . sanitize_file_name($filename);
                if ($zip->addFile($filePath, $archiveName)) {
                    $found++;
                }
            }

            $page++;
        } while ($page <= (int) $query->max_num_pages);

        $zip->close();

        if (! is_file($zipFilePath) || $found === 0) {
            if (is_file($zipFilePath)) {
                unlink($zipFilePath);
            }
            wp_die(esc_html__('Geen afbeeldingen gevonden om te zippen.', 'modern-image-manager'));
        }

        if (! is_readable($zipFilePath)) {
            unlink($zipFilePath);
            wp_die(esc_html__('Het ZIP-bestand kon niet worden gelezen.', 'modern-image-manager'));
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="media-export-' . gmdate('Y-m-d') . '.zip"');
        $zipSize = filesize($zipFilePath);
        if ($zipSize !== false) {
            header('Content-Length: ' . $zipSize);
        }
        header('X-Content-Type-Options: nosniff');

        readfile($zipFilePath);
        unlink($zipFilePath);
        exit;
    }
}
