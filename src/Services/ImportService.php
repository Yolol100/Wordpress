<?php
declare(strict_types=1);

namespace ModernImageManager\Services;

use ModernImageManager\Support\FileSecurity;
use WP_Post;

defined('ABSPATH') || exit;

final class ImportService
{
    private const MAX_ROWS = 5000;
    private const MAX_FILE_SIZE = 5242880;

    public function __construct(private ?FileSecurity $files = null)
    {
        $this->files = $files ?: new FileSecurity();
    }

    public function renderPage(): void
    {
        if (! current_user_can('upload_files')) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }

        $this->maybeAddResultScript();

        $mim_screen = 'import-media';
        require MODERN_IMAGE_MANAGER_PATH . 'templates/admin-root.php';
    }

    public function maybeAddResultScript(): void
    {
        $resultPayload = ['errors' => [], 'success' => '', 'summary' => null];

        if (isset($_POST['import_csv'])) {
            if (! isset($_POST['mim_import_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mim_import_nonce'])), 'mim_import_csv')) {
                $resultPayload['errors'][] = __('Beveiligingscontrole mislukt.', 'modern-image-manager');
            } else {
                [$errors, $importedCount, $skippedRows] = $this->handleImport();
                $resultPayload = [
                    'errors' => array_values($errors),
                    'success' => $importedCount > 0 && $errors === [] ? sprintf(__('%d rijen geïmporteerd.', 'modern-image-manager'), $importedCount) : '',
                    'summary' => [
                        'imported' => $importedCount,
                        'skipped' => count($skippedRows),
                        'skippedRows' => array_map(
                            static fn($row, $reason): array => ['row' => (int) $row, 'reason' => (string) $reason],
                            array_keys($skippedRows),
                            array_values($skippedRows)
                        ),
                    ],
                ];
            }
        }

        wp_add_inline_script(
            'modern-image-manager-admin',
            'window.ModernImageManagerImportResult = ' . wp_json_encode($resultPayload) . ';',
            'before'
        );
    }

    public function handleImport(): array
    {
        $errors = [];
        $imported = 0;
        $skipped = [];

        if (! current_user_can('upload_files')) {
            return [[__('Je hebt geen rechten om deze import uit te voeren.', 'modern-image-manager')], 0, []];
        }

        $uploadedFile = $_FILES['import_file'] ?? null;
        if (! is_array($uploadedFile) || empty($uploadedFile['tmp_name'])) {
            return [[__('Geen bestand geüpload.', 'modern-image-manager')], 0, []];
        }

        if (! empty($uploadedFile['error'])) {
            return [[__('Bestand uploaden is mislukt.', 'modern-image-manager')], 0, []];
        }
        if (! empty($uploadedFile['size']) && (int) $uploadedFile['size'] > self::MAX_FILE_SIZE) {
            return [[sprintf(__('CSV-bestand is te groot. Maximum is %d MB.', 'modern-image-manager'), (int) (self::MAX_FILE_SIZE / 1048576))], 0, []];
        }

        $filename = sanitize_file_name((string) ($uploadedFile['name'] ?? ''));
        $file = (string) $uploadedFile['tmp_name'];
        if (! is_uploaded_file($file) || ! is_readable($file)) {
            return [[__('Uploadbestand is ongeldig.', 'modern-image-manager')], 0, []];
        }

        $filetype = wp_check_filetype_and_ext($file, $filename, ['csv' => 'text/csv']);
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv' || empty($filetype['ext'])) {
            return [[__('Alleen CSV-bestanden zijn toegestaan.', 'modern-image-manager')], 0, []];
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return [[__('CSV-bestand kon niet worden gelezen.', 'modern-image-manager')], 0, []];
        }

        $headers = fgetcsv($handle);
        $headers = is_array($headers) ? array_map([$this, 'normalizeHeader'], $headers) : $headers;
        if (! is_array($headers) || ! array_intersect(['id', 'filename', 'file_url', 'file_path'], $headers)) {
            fclose($handle);
            return [[__('Verplichte kolom ontbreekt: ID, Bestandsnaam, Bestands-URL of Bestandspad.', 'modern-image-manager')], 0, []];
        }
        if (count($headers) !== count(array_unique($headers))) {
            fclose($handle);
            return [[__('CSV-header bevat dubbele kolommen na normalisatie.', 'modern-image-manager')], 0, []];
        }

        $overwriteEmpty = isset($_POST['overwrite_empty']) && (bool) absint(wp_unslash($_POST['overwrite_empty']));
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($rowNumber > self::MAX_ROWS + 1) {
                $errors[] = sprintf(__('Import gestopt na maximaal %d rijen.', 'modern-image-manager'), self::MAX_ROWS);
                break;
            }
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            if (count($row) !== count($headers)) {
                $skipped[$rowNumber] = __('Aantal kolommen komt niet overeen met de header.', 'modern-image-manager');
                continue;
            }

            $data = array_combine($headers, $row);
            if (! is_array($data)) {
                $skipped[$rowNumber] = __('Rij kon niet worden gelezen.', 'modern-image-manager');
                continue;
            }

            [$attachment, $reason] = $this->findAttachmentForRow($data);
            if ($attachment instanceof WP_Post) {
                $attachmentId = (int) $attachment->ID;
                if (! current_user_can('edit_post', $attachmentId)) {
                    $skipped[$rowNumber] = __('Geen rechten om deze bijlage bij te werken.', 'modern-image-manager');
                    continue;
                }

                $newFilename = isset($data['new_filename']) ? pathinfo(sanitize_file_name(trim((string) $data['new_filename'])), PATHINFO_FILENAME) : '';
                if ($newFilename !== '' && ! $this->renameMediaFile($attachmentId, $newFilename)) {
                    $skipped[$rowNumber] = __('Bestand kon niet worden hernoemd.', 'modern-image-manager');
                    continue;
                }
                if (! $this->updateAttachmentData($attachmentId, $data, $overwriteEmpty)) {
                    $skipped[$rowNumber] = __('Bijlagegegevens konden niet worden bijgewerkt.', 'modern-image-manager');
                    continue;
                }
                $imported++;
                continue;
            }

            if ($reason !== '') {
                $skipped[$rowNumber] = $reason;
                continue;
            }

            $createdId = $this->createNewAttachment($data);
            if ($createdId) {
                $imported++;
            } else {
                $skipped[$rowNumber] = __('Bijlage niet gevonden en kon niet worden aangemaakt.', 'modern-image-manager');
            }
        }

        fclose($handle);

        return [$errors, $imported, $skipped];
    }

    private function findAttachmentForRow(array $data): array
    {
        $id = isset($data['id']) ? absint($data['id']) : 0;
        if ($id > 0) {
            return $this->isImageAttachment($id)
                ? [get_post($id), '']
                : [null, __('Bijlage-ID is geen geldige afbeelding.', 'modern-image-manager')];
        }

        $fileUrl = isset($data['file_url']) ? esc_url_raw(trim((string) $data['file_url'])) : '';
        if ($fileUrl !== '') {
            $urlId = attachment_url_to_postid($fileUrl);
            if ($urlId > 0 && $this->isImageAttachment($urlId)) {
                return [get_post($urlId), ''];
            }
        }

        $relativePath = $this->relativeUploadPathFromData($data);
        if ($relativePath !== '') {
            $pathId = $this->findAttachmentIdByRelativePath($relativePath);
            if ($pathId > 0 && $this->isImageAttachment($pathId)) {
                return [get_post($pathId), ''];
            }
        }

        $safeFilename = isset($data['filename']) ? sanitize_file_name(basename((string) $data['filename'])) : '';
        if ($safeFilename === '') {
            return [null, ''];
        }

        $matches = $this->findAttachmentIdsByFilename($safeFilename);
        if (count($matches) > 1) {
            return [null, __('Meerdere bijlagen met deze bestandsnaam gevonden. Voeg ID of Bestandspad toe aan de CSV.', 'modern-image-manager')];
        }

        return count($matches) === 1 ? [get_post((int) $matches[0]), ''] : [null, ''];
    }

    private function findAttachmentIdByRelativePath(string $relativePath): int
    {
        global $wpdb;

        $attachmentId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT pm.post_id
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_wp_attached_file'
                   AND p.post_type = 'attachment'
                   AND p.post_mime_type LIKE 'image/%'
                   AND pm.meta_value = %s
                 LIMIT 1",
                $relativePath
            )
        );

        return $attachmentId ? (int) $attachmentId : 0;
    }

    private function findAttachmentIdsByFilename(string $filename): array
    {
        global $wpdb;

        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT pm.post_id
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_wp_attached_file'
                   AND p.post_type = 'attachment'
                   AND p.post_mime_type LIKE 'image/%'
                   AND (pm.meta_value = %s OR pm.meta_value LIKE %s)
                 ORDER BY pm.post_id DESC
                 LIMIT 2",
                $filename,
                '%/' . $wpdb->esc_like($filename)
            )
        );

        return array_values(array_filter(array_map('absint', (array) $ids)));
    }

    private function isImageAttachment(int $attachmentId): bool
    {
        $post = get_post($attachmentId);
        return $post instanceof WP_Post && $post->post_type === 'attachment' && str_starts_with((string) get_post_mime_type($attachmentId), 'image/');
    }

    private function renameMediaFile(int $attachmentId, string $newFilename): bool
    {
        $filePath = get_attached_file($attachmentId);
        if (! $filePath || ! $this->files->isAllowedImageFile($filePath)) {
            return false;
        }

        $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
        $currentBase = pathinfo($filePath, PATHINFO_FILENAME);
        $newFilenameBase = sanitize_file_name(pathinfo($newFilename, PATHINFO_FILENAME));
        if ($newFilenameBase === '' || $fileExt === '') {
            return false;
        }
        if ($newFilenameBase === $currentBase) {
            return true;
        }

        $newFilePath = trailingslashit(dirname($filePath)) . wp_unique_filename(dirname($filePath), $newFilenameBase . '.' . $fileExt);
        if (! $this->files->isPathInsideUploads($newFilePath, true)) {
            return false;
        }

        if ($filePath === $newFilePath) {
            return true;
        }

        if (! rename($filePath, $newFilePath)) {
            return false;
        }

        update_attached_file($attachmentId, $newFilePath);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachmentId, $newFilePath);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachmentId, $metadata);
        }

        return true;
    }

    private function updateAttachmentData(int $attachmentId, array $data, bool $overwriteEmpty): bool
    {
        $updateFields = ['ID' => $attachmentId];

        if ($this->shouldImportValue($data, 'slug', $overwriteEmpty)) {
            $slug = sanitize_title((string) $data['slug']);
            if ($slug !== '') {
                $updateFields['post_name'] = $slug;
            }
        }
        if ($this->shouldImportValue($data, 'title', $overwriteEmpty)) {
            $updateFields['post_title'] = sanitize_text_field((string) $data['title']);
        }
        if ($this->shouldImportValue($data, 'caption', $overwriteEmpty)) {
            $updateFields['post_excerpt'] = sanitize_text_field((string) $data['caption']);
        }
        if ($this->shouldImportValue($data, 'description', $overwriteEmpty)) {
            $updateFields['post_content'] = wp_kses_post((string) $data['description']);
        }

        if (count($updateFields) > 1) {
            $result = wp_update_post($updateFields, true);
            if (is_wp_error($result)) {
                return false;
            }
        }

        if ($this->shouldImportValue($data, 'alt_text', $overwriteEmpty)) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field((string) $data['alt_text']));
        }

        return true;
    }

    private function shouldImportValue(array $data, string $key, bool $overwriteEmpty): bool
    {
        if (! array_key_exists($key, $data)) {
            return false;
        }

        return $overwriteEmpty || trim((string) $data[$key]) !== '';
    }

    private function createNewAttachment(array $data): ?int
    {
        if (! current_user_can('upload_files')) {
            return null;
        }

        $filePath = $this->resolveImportFilePath($data);
        if ($filePath === '' || ! $this->files->isAllowedImageFile($filePath)) {
            return null;
        }

        $safeFilename = basename($filePath);
        $mimeType = $this->getMimeType($safeFilename);
        if (! $mimeType) {
            return null;
        }

        $uploadDir = wp_upload_dir();
        $relativePath = $this->relativePathFromAbsolute($filePath);
        $guid = trailingslashit((string) ($uploadDir['baseurl'] ?? '')) . str_replace('%2F', '/', rawurlencode($relativePath));

        $attachmentId = wp_insert_attachment([
            'guid' => $guid,
            'post_mime_type' => $mimeType,
            'post_title' => sanitize_text_field((string) ($data['title'] ?? pathinfo($safeFilename, PATHINFO_FILENAME))),
            'post_content' => wp_kses_post((string) ($data['description'] ?? '')),
            'post_excerpt' => sanitize_text_field((string) ($data['caption'] ?? '')),
            'post_status' => 'inherit',
        ], $filePath);

        if (! $attachmentId || is_wp_error($attachmentId)) {
            return null;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata((int) $attachmentId, wp_generate_attachment_metadata((int) $attachmentId, $filePath));

        if (isset($data['alt_text'])) {
            update_post_meta((int) $attachmentId, '_wp_attachment_image_alt', sanitize_text_field((string) $data['alt_text']));
        }

        return (int) $attachmentId;
    }

    private function resolveImportFilePath(array $data): string
    {
        $uploadDir = wp_upload_dir();
        $baseDir = (string) ($uploadDir['basedir'] ?? '');
        if ($baseDir === '') {
            return '';
        }

        $relativePath = $this->relativeUploadPathFromData($data);
        if ($relativePath === '' && isset($data['file_url'])) {
            $relativePath = $this->relativeUploadPathFromUrl((string) $data['file_url']);
        }
        if ($relativePath === '') {
            return '';
        }

        $filePath = trailingslashit($baseDir) . $relativePath;
        return $this->files->isPathInsideUploads($filePath, true) ? $filePath : '';
    }

    private function relativeUploadPathFromUrl(string $url): string
    {
        $uploadDir = wp_upload_dir();
        $baseUrl = wp_normalize_path(trailingslashit((string) ($uploadDir['baseurl'] ?? '')));
        $normalizedUrl = wp_normalize_path(esc_url_raw(trim($url)));

        if ($baseUrl === '' || $normalizedUrl === '' || ! str_starts_with($normalizedUrl, $baseUrl)) {
            return '';
        }

        $path = rawurldecode(ltrim(substr($normalizedUrl, strlen($baseUrl)), '/'));
        return $this->sanitizeRelativeUploadPath($path);
    }

    private function relativeUploadPathFromData(array $data): string
    {
        $raw = trim((string) ($data['file_path'] ?? ''));
        if ($raw === '' && isset($data['filename']) && str_contains((string) $data['filename'], '/')) {
            $raw = trim((string) $data['filename']);
        }

        return $this->sanitizeRelativeUploadPath($raw);
    }

    private function sanitizeRelativeUploadPath(string $raw): string
    {
        $path = ltrim(wp_normalize_path(trim($raw)), '/');
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return '';
        }

        return implode('/', array_map('sanitize_file_name', array_filter(explode('/', $path), 'strlen')));
    }

    private function relativePathFromAbsolute(string $filePath): string
    {
        $uploadDir = wp_upload_dir();
        $baseDir = wp_normalize_path(trailingslashit((string) ($uploadDir['basedir'] ?? '')));
        $normalized = wp_normalize_path($filePath);

        if ($baseDir !== '' && str_starts_with($normalized, $baseDir)) {
            return ltrim(substr($normalized, strlen($baseDir)), '/');
        }

        return basename($filePath);
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header);
        $key = strtolower(trim($header));
        $key = str_replace([' ', '-', '.'], '_', $key);

        $map = [
            'id' => 'id',
            'attachment_id' => 'id',
            'bijlage_id' => 'id',
            'bestandsnaam' => 'filename',
            'filename' => 'filename',
            'file_name' => 'filename',
            'bestandspad' => 'file_path',
            'file_path' => 'file_path',
            'relative_path' => 'file_path',
            'relatief_pad' => 'file_path',
            'bestands_url' => 'file_url',
            'file_url' => 'file_url',
            'url' => 'file_url',
            'nieuwe_bestandsnaam' => 'new_filename',
            'new_filename' => 'new_filename',
            'slug' => 'slug',
            'titel' => 'title',
            'title' => 'title',
            'bijschrift' => 'caption',
            'caption' => 'caption',
            'beschrijving' => 'description',
            'description' => 'description',
            'alt_tag' => 'alt_text',
            'alt_text' => 'alt_text',
            'pagina' => 'page',
            'page' => 'page',
        ];

        return $map[$key] ?? sanitize_key($key);
    }

    private function getMimeType(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => null,
        };
    }
}
