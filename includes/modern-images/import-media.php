<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class Modern_Image_Manager_Import
{
    public static function render_import_page(): void
    {
        $errors = [];
        $imported_count = 0;
        $skipped_rows = [];
        $success = '';
        $import_summary = '';

        if (isset($_POST['import_csv'])) {
            list($errors, $imported_count, $skipped_rows) = self::handle_import();
            if ($imported_count > 0 && empty($errors)) {
                $success = sprintf(__('%d rijen geïmporteerd.', 'modern-image-manager'), $imported_count);
            }

            // Uitgebreide samenvatting tonen na import
            $skipped_count = count($skipped_rows);
            $import_summary = '<div class="import-summary">';
            $import_summary .= '<strong>' . esc_html__('Import samenvatting', 'modern-image-manager') . '</strong><ul>';
            $import_summary .= '<li>' . sprintf(esc_html__('%d rijen succesvol geïmporteerd.', 'modern-image-manager'), $imported_count) . '</li>';
            $import_summary .= '<li>' . sprintf(esc_html__('%d rijen overgeslagen wegens fouten.', 'modern-image-manager'), $skipped_count) . '</li>';
            if ($skipped_count > 0) {
                $import_summary .= '<details><summary>' . esc_html__('Bekijk overgeslagen rijen', 'modern-image-manager') . '</summary><ul>';
                foreach ($skipped_rows as $rownum => $reason) {
                    $import_summary .= '<li>' . sprintf(esc_html__('Rij %d: %s', 'modern-image-manager'), $rownum, esc_html($reason)) . '</li>';
                }
                $import_summary .= '</ul></details>';
            }
            $import_summary .= '</ul></div>';
        }

        include MODERN_IMAGE_MANAGER_PATH . 'templates/import-page.php';

        // Inline meldingen consistent boven het formulier tonen
        if (!empty($errors)) {
            echo '<div class="mim-notice mim-notice--error"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }

        if (!empty($success)) {
            echo '<div class="mim-notice mim-notice--success"><p>' . esc_html($success) . '</p></div>';
        }

        if (!empty($import_summary)) {
            echo $import_summary;
        }
    }

    public static function handle_import(): array
    {
        global $wpdb;
        $errors = [];
        $imported = 0;
        $skipped = [];

        if (empty($_FILES['import_file']['tmp_name'])) {
            $errors[] = __('Geen bestand geüpload.', 'modern-image-manager');
            return [$errors, $imported, $skipped];
        }

        $file    = $_FILES['import_file']['tmp_name'];
        $csvData = array_map('str_getcsv', file($file));

        if (empty($csvData) || !is_array($csvData)) {
            $errors[] = __('Ongeldig CSV-bestand.', 'modern-image-manager');
            return [$errors, $imported, $skipped];
        }

        $headers = array_shift($csvData);
        if (!is_array($headers)) {
            $errors[] = __('Ongeldige CSV headers.', 'modern-image-manager');
            return [$errors, $imported, $skipped];
        }

        // VERPLICHTE KOLOMMEN - pas aan naar je wensen (let op: exact zoals in CSV)
        $required = ['Bestandsnaam'];

        foreach ($required as $col) {
            if (!in_array($col, $headers)) {
                $errors[] = sprintf(__('Verplichte kolom ontbreekt: %s', 'modern-image-manager'), $col);
                return [$errors, $imported, $skipped];
            }
        }

        // Begin transaction (rollback support)
        $wpdb->query('START TRANSACTION');
        $imported_ids = [];
        try {
            foreach ($csvData as $i => $row) {
                $rownum = $i + 2; // +2: headers + 1-based

                if (count($row) !== count($headers)) {
                    $skipped[$rownum] = __('Ongeldig rijformaat.', 'modern-image-manager');
                    continue;
                }

                $data = array_combine($headers, $row);

                // Validatie: verplichte velden niet leeg
                if (empty($data['Bestandsnaam'])) {
                    $skipped[$rownum] = sprintf(__('Lege waarde voor verplichte kolom: %s', 'modern-image-manager'), 'Bestandsnaam');
                    continue;
                }

                $newFilenameInput = isset($data['Nieuwe bestandsnaam'])
                    ? pathinfo(trim($data['Nieuwe bestandsnaam']), PATHINFO_FILENAME)
                    : '';

                $existingAttachment = self::get_attachment_by_filename($data['Bestandsnaam']);

                if ($existingAttachment) {
                    $attachmentId = (int) $existingAttachment->ID;

                    if (!empty($newFilenameInput)) {
                        self::rename_media_file($attachmentId, $newFilenameInput);
                    }

                    self::update_attachment_data($attachmentId, $data);
                    $imported_ids[] = $attachmentId;
                } else {
                    $created_id = self::create_new_attachment($data);
                    if ($created_id) {
                        $imported_ids[] = $created_id;
                    }
                }
                $imported++;
            }
            // Commit als alles lukt
            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            // Rollback bij fout
            $wpdb->query('ROLLBACK');
            $errors[] = __('Import is teruggedraaid vanwege een fout: ', 'modern-image-manager') . $e->getMessage();
            // Extra: verwijder aangemaakte bijlagen als die al zijn toegevoegd...
            return [$errors, 0, $skipped];
        }

        return [$errors, $imported, $skipped];
    }

    private static function rename_media_file(int $attachmentId, string $newFilename): void
    {
        $file_path  = get_attached_file($attachmentId);
        $upload_dir = wp_upload_dir();
        $directory  = dirname($file_path);

        if (!$file_path || !file_exists($file_path)) {
            return;
        }

        $file_ext          = pathinfo($file_path, PATHINFO_EXTENSION);
        $new_filename_clean = sanitize_file_name($newFilename) . '.' . $file_ext;
        $new_file_path      = $directory . '/' . $new_filename_clean;

        if (file_exists($new_file_path)) {
            return;
        }

        if (!rename($file_path, $new_file_path)) {
            return;
        }

        update_attached_file($attachmentId, $new_file_path);

        $metadata        = wp_get_attachment_metadata($attachmentId);
        $metadata['file'] = str_replace($upload_dir['basedir'] . '/', '', $new_file_path);
        wp_update_attachment_metadata($attachmentId, $metadata);

        $new_url = $upload_dir['baseurl'] . '/' . $new_filename_clean;

        wp_update_post([
            'ID'         => $attachmentId,
            'guid'       => $new_url,
            'post_name'  => sanitize_title($new_filename_clean),
            'post_title' => sanitize_text_field($newFilename),
        ]);
    }

    private static function update_attachment_data(int $attachmentId, array $data): void
    {
        $update_fields = ['ID' => $attachmentId];

        if (array_key_exists('Titel', $data)) {
            $update_fields['post_title'] = sanitize_text_field($data['Titel']);
        }
        if (array_key_exists('Caption', $data)) {
            $update_fields['post_excerpt'] = sanitize_text_field($data['Caption']);
        }
        if (array_key_exists('Description', $data)) {
            $update_fields['post_content'] = wp_kses_post($data['Description']);
        }

        wp_update_post($update_fields);

        if (array_key_exists('Alt-tag', $data)) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($data['Alt-tag']));
        }
    }

    private static function get_attachment_by_filename(string $filename): ?WP_Post
    {
        global $wpdb;

        $like       = '%' . $wpdb->esc_like($filename);
        $attachment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts 
                 WHERE post_type = 'attachment' 
                 AND guid LIKE %s 
                 LIMIT 1",
                $like
            )
        );

        return $attachment ? get_post($attachment->ID) : null;
    }

    private static function create_new_attachment(array $data): ?int
    {
        $mimeType = self::get_mime_type($data['Bestandsnaam'] ?? '');
        if (!$mimeType) {
            return null;
        }

        $uploadDir = wp_upload_dir();
        $filePath  = $uploadDir['basedir'] . '/' . $data['Bestandsnaam'];

        if (!file_exists($filePath)) {
            return null;
        }

        $attachment = [
            'guid'           => $uploadDir['baseurl'] . '/' . $data['Bestandsnaam'],
            'post_mime_type' => $mimeType,
            'post_title'     => $data['Titel'] ?? '',
            'post_content'   => $data['Description'] ?? '',
            'post_excerpt'   => $data['Caption'] ?? '',
            'post_status'    => 'inherit',
        ];

        $newAttachmentId = wp_insert_attachment($attachment, $filePath);

        if ($newAttachmentId) {
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachData = wp_generate_attachment_metadata($newAttachmentId, $filePath);
            wp_update_attachment_metadata($newAttachmentId, $attachData);

            if (!empty($data['Alt-tag'])) {
                update_post_meta($newAttachmentId, '_wp_attachment_image_alt', sanitize_text_field($data['Alt-tag']));
            }
        }

        return $newAttachmentId ?: null;
    }

    private static function get_mime_type(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => null,
        };
    }
}