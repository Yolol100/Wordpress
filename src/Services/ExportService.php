<?php
declare(strict_types=1);

namespace ModernImageManager\Services;

use ModernImageManager\Repositories\AttachmentRepository;
use ModernImageManager\Support\AttachmentHelper;

defined('ABSPATH') || exit;

final class ExportService
{
    public function __construct(
        private ?AttachmentRepository $attachments = null,
        private ?AttachmentHelper $helper = null
    ) {
        $this->attachments = $attachments ?: new AttachmentRepository();
        $this->helper = $helper ?: new AttachmentHelper();
    }

    public function renderPage(): void
    {
        if (! current_user_can('upload_files')) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }

        $mim_screen = 'export-media';
        require MODERN_IMAGE_MANAGER_PATH . 'templates/admin-root.php';
    }

    public function handleDownload(): void
    {
        if (! current_user_can('upload_files')) {
            wp_die(esc_html__('Je hebt geen toestemming om deze export te downloaden.', 'modern-image-manager'));
        }

        check_admin_referer('mim_export_csv', 'mim_export_nonce');

        $exportOption = sanitize_key(wp_unslash($_GET['export_option'] ?? 'all'));
        if (! in_array($exportOption, ['all', 'linked', 'unlinked'], true)) {
            $exportOption = 'all';
        }
        $selectedColumns = isset($_GET['export_columns']) ? array_map('sanitize_key', (array) wp_unslash($_GET['export_columns'])) : [];

        $this->generateCsv($exportOption, $selectedColumns);
    }

    public function generateCsv(string $exportOption = 'all', array $selectedColumns = []): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="media-export-' . gmdate('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');

        $columnHeaders = $this->columnHeaders();
        $selectedColumns = array_values(array_intersect(array_keys($columnHeaders), $selectedColumns));
        if ($selectedColumns === []) {
            $selectedColumns = array_keys($columnHeaders);
        }

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon output stream niet openen.', 'modern-image-manager'));
        }

        fputcsv($output, array_map(static fn(string $column): string => $columnHeaders[$column] ?? '', $selectedColumns));

        $page = 1;
        do {
            $query = $this->attachments->queryImageIds([
                'posts_per_page' => 250,
                'paged' => $page,
            ]);

            foreach ((array) $query->posts as $attachmentId) {
                $attachment = $this->attachments->find((int) $attachmentId);
                if (! $attachment) {
                    continue;
                }

                $isLinked = $this->attachments->hasAttachmentContext((int) $attachment->ID);
                if (($exportOption === 'linked' && ! $isLinked) || ($exportOption === 'unlinked' && $isLinked)) {
                    continue;
                }

                $filePath = $this->attachments->attachedFile((int) $attachment->ID);
                $rowData = [
                    'id' => (string) $attachment->ID,
                    'filename' => basename($filePath) ?: '',
                    'file_path' => $this->relativeUploadPath($filePath),
                    'new_filename' => '',
                    'slug' => $attachment->post_name ?: '',
                    'page' => $this->helper->getAttachedPostTitle((int) $attachment->ID),
                    'title' => $attachment->post_title ?: '',
                    'caption' => $attachment->post_excerpt ?: '',
                    'description' => $attachment->post_content ?: '',
                    'alt_text' => $this->attachments->altText((int) $attachment->ID),
                    'file_url' => $this->attachments->url((int) $attachment->ID),
                ];

                fputcsv($output, array_map(static fn(string $column): string => (string) ($rowData[$column] ?? ''), $selectedColumns));
            }

            $page++;
        } while ($page <= (int) $query->max_num_pages);

        fclose($output);
        exit;
    }


    private function relativeUploadPath(string $filePath): string
    {
        $uploadDir = wp_upload_dir();
        $baseDir = wp_normalize_path(trailingslashit((string) ($uploadDir['basedir'] ?? '')));
        $normalized = wp_normalize_path($filePath);

        if ($baseDir !== '' && str_starts_with($normalized, $baseDir)) {
            return ltrim(substr($normalized, strlen($baseDir)), '/');
        }

        return '';
    }

    private function columnHeaders(): array
    {
        return [
            'id' => __('ID', 'modern-image-manager'),
            'filename' => __('Bestandsnaam', 'modern-image-manager'),
            'file_path' => __('Bestandspad', 'modern-image-manager'),
            'new_filename' => __('Nieuwe bestandsnaam', 'modern-image-manager'),
            'slug' => __('Slug', 'modern-image-manager'),
            'page' => __('Pagina', 'modern-image-manager'),
            'title' => __('Titel', 'modern-image-manager'),
            'caption' => __('Bijschrift', 'modern-image-manager'),
            'description' => __('Beschrijving', 'modern-image-manager'),
            'alt_text' => __('Alt-tag', 'modern-image-manager'),
            'file_url' => __('Bestands-URL', 'modern-image-manager'),
        ];
    }
}
