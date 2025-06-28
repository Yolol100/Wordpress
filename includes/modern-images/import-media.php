<?php
// File: import-media.php
// Import functionaliteit

declare(strict_types=1);
defined('ABSPATH') || exit;

final class Modern_Image_Manager_Import
{
    public static function render_import_page(): void
    {
        $notice_type = null;
        $notice_html = '';
        $imported_count = 0;
        $skipped_rows = [];
        $overwrite_empty = !empty($_POST['overwrite_empty']);

        if (isset($_POST['import_csv'])) {
            if (empty($_FILES['import_file']['tmp_name'])) {
                $notice_type = 'error';
                $notice_html = esc_html__('Geen bestand geüpload.', 'modern-image-manager');
            } else {
                list($errors, $imported_count, $skipped_rows) = self::handle_import($overwrite_empty);

                if (!empty($errors)) {
                    $notice_type = 'error';
                    $notice_html = '<ul>';
                    foreach ($errors as $error) {
                        $notice_html .= '<li>' . esc_html($error) . '</li>';
                    }
                    $notice_html .= '</ul>';
                } elseif ($imported_count == 0 && count($skipped_rows) == 0) {
                    $notice_type = 'warning';
                    $notice_html = esc_html__('Niks geïmporteerd.', 'modern-image-manager');
                } else {
                    $notice_type = 'success';
                    $skipped_count = count($skipped_rows);
                    $notice_html = '<strong>' . esc_html__('Import samenvatting', 'modern-image-manager') . '</strong><ul>';
                    $notice_html .= '<li>' . sprintf(esc_html__('%d rijen succesvol geïmporteerd.', 'modern-image-manager'), $imported_count) . '</li>';
                    $notice_html .= '<li>' . sprintf(esc_html__('%d rijen overgeslagen wegens fouten.', 'modern-image-manager'), $skipped_count) . '</li>';
                    if ($skipped_count > 0) {
                        $notice_html .= '<details><summary>' . esc_html__('Bekijk overgeslagen rijen', 'modern-image-manager') . '</summary><ul>';
                        foreach ($skipped_rows as $rownum => $reason) {
                            $notice_html .= '<li>' . sprintf(esc_html__('Rij %d: %s', 'modern-image-manager'), $rownum, esc_html($reason)) . '</li>';
                        }
                        $notice_html .= '</ul></details>';
                    }
                    $notice_html .= '</ul>';
                }
            }
        }

        // Zet variabele zodat template het vinkje kan onthouden
        $GLOBALS['mim_overwrite_empty'] = $overwrite_empty;

        include MODERN_IMAGE_MANAGER_PATH . 'templates/import-page.php';
    }

    public static function handle_import(bool $overwrite_empty = false): array
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

        // Verplichte kolommen
        $required = ['Bestandsnaam', 'Titel', 'Alt-tag'];

        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                $errors[] = sprintf(__('Verplichte kolom ontbreekt: %s', 'modern-image-manager'), $col);
                return [$errors, $imported, $skipped];
            }
        }

        $wpdb->query('START TRANSACTION');
        try {
            foreach ($csvData as $i => $row) {
                $rownum = $i + 2;

                // Controleer eerst het aantal kolommen vóór array_combine!
                if (count($headers) !== count($row)) {
                    $skipped[$rownum] = __('Aantal kolommen in deze rij komt niet overeen met de header.', 'modern-image-manager');
                    continue;
                }

                $data = array_combine($headers, $row);

                // Valideer verplichte velden
                foreach ($required as $col) {
                    if (!isset($data[$col]) || $data[$col] === '') {
                        $skipped[$rownum] = sprintf(__('Lege waarde voor verplichte kolom: %s', 'modern-image-manager'), $col);
                        continue 2;
                    }
                }

                $newFilenameInput = isset($data['Nieuwe bestandsnaam'])
                    ? pathinfo(trim($data['Nieuwe bestandsnaam']), PATHINFO_FILENAME)
                    : '';

                // Geavanceerd: zoek ALLE varianten (origineel én alle scaled versies)
                $attachments = self::get_all_scaled_attachments($data['Bestandsnaam']);

                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachmentId = (int)$attachment->ID;

                        if (!empty($newFilenameInput)) {
                            self::rename_media_file($attachmentId, $newFilenameInput);
                        }

                        self::update_attachment_data($attachmentId, $data, $overwrite_empty);
                    }
                } else {
                    self::create_new_attachment($data);
                }
                $imported++;
            }
            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $errors[] = __('Import is teruggedraaid vanwege een fout: ', 'modern-image-manager') . $e->getMessage();
            return [$errors, 0, $skipped];
        }

        return [$errors, $imported, $skipped];
    }

    private static function rename_media_file(int $attachmentId, string $newFilename): void
    {
        $file_path  = get_attached_file($attachmentId);
        $upload_dir = wp_upload_dir();

        if (!$file_path || !file_exists($file_path)) {
            return;
        }

        $file_ext          = pathinfo($file_path, PATHINFO_EXTENSION);
        $new_filename_clean = sanitize_file_name($newFilename) . '.' . $file_ext;
        $new_file_path      = $upload_dir['path'] . '/' . $new_filename_clean;

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

    private static function update_attachment_data(int $attachmentId, array $data, bool $overwrite_empty = false): void
    {
        $update_fields = [];

        if (array_key_exists('Titel', $data) && ($overwrite_empty || $data['Titel'] !== '')) {
            $update_fields['post_title'] = sanitize_text_field($data['Titel']);
        }
        if (array_key_exists('Caption', $data) && ($overwrite_empty || $data['Caption'] !== '')) {
            $update_fields['post_excerpt'] = sanitize_text_field($data['Caption']);
        }
        if (array_key_exists('Description', $data) && ($overwrite_empty || $data['Description'] !== '')) {
            $update_fields['post_content'] = wp_kses_post($data['Description']);
        }

        if (!empty($update_fields)) {
            $update_fields['ID'] = $attachmentId;
            wp_update_post($update_fields);
        }

        if (array_key_exists('Alt-tag', $data) && ($overwrite_empty || $data['Alt-tag'] !== '')) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($data['Alt-tag']));
        }

        if (array_key_exists('SEO Titel', $data) && ($overwrite_empty || $data['SEO Titel'] !== '')) {
            update_post_meta($attachmentId, '_yoast_wpseo_title', sanitize_text_field($data['SEO Titel']));
        }
        if (array_key_exists('SEO Omschrijving', $data) && ($overwrite_empty || $data['SEO Omschrijving'] !== '')) {
            update_post_meta($attachmentId, '_yoast_wpseo_metadesc', sanitize_text_field($data['SEO Omschrijving']));
        }
    }

    // Geavanceerd: vind alle bijlagen met originele én ALLE geschaalde varianten
    private static function get_all_scaled_attachments(string $filename): array
    {
        global $wpdb;

        $ext      = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = basename($filename, '.' . $ext);

        // SQL LIKE: zoek alles dat begint met basename en eindigt op .ext, ook -scaled, -scaled-1, -scaled-2 etc.
        $pattern  = $wpdb->esc_like($basename) . '%.' . $ext;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts 
                 WHERE post_type = 'attachment' 
                 AND guid LIKE %s",
                '%' . $pattern
            )
        );
        $attachments = [];
        foreach ($results as $result) {
            $post = get_post($result->ID);
            if ($post) {
                $attachments[] = $post;
            }
        }
        return $attachments;
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

            if (isset($data['Alt-tag'])) {
                update_post_meta($newAttachmentId, '_wp_attachment_image_alt', sanitize_text_field($data['Alt-tag']));
            }
            if (isset($data['SEO Titel'])) {
                update_post_meta($newAttachmentId, '_yoast_wpseo_title', sanitize_text_field($data['SEO Titel']));
            }
            if (isset($data['SEO Omschrijving'])) {
                update_post_meta($newAttachmentId, '_yoast_wpseo_metadesc', sanitize_text_field($data['SEO Omschrijving']));
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