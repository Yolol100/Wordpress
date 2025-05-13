<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class Modern_Image_Manager_Import
{
    public static function render_import_page(): void
    {
        if (isset($_POST['import_csv'])) {
            self::handle_import();
        }

        include MODERN_IMAGE_MANAGER_PATH . 'templates/import-page.php';
    }

    public static function handle_import(): void
    {
        if (empty($_FILES['import_file']['tmp_name'])) {
            wp_die(__('Geen bestand geüpload.', 'modern-image-manager'));
        }

        $file    = $_FILES['import_file']['tmp_name'];
        $csvData = array_map('str_getcsv', file($file));

        if (empty($csvData) || !is_array($csvData)) {
            wp_die(__('Ongeldig CSV-bestand.', 'modern-image-manager'));
        }

        $headers = array_shift($csvData);
        if (!is_array($headers)) {
            wp_die(__('Ongeldige CSV headers.', 'modern-image-manager'));
        }

        foreach ($csvData as $row) {
            $data = array_combine($headers, $row);
            if (empty($data['Bestandsnaam'])) {
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
            } else {
                self::create_new_attachment($data);
            }
        }

        echo '<div class="notice notice-success"><p>' . __('Import voltooid en alle waarden overschreven!', 'modern-image-manager') . '</p></div>';
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

    private static function update_attachment_data(int $attachmentId, array $data): void
    {
        $update_fields = [];

        if (!empty($data['Titel'])) {
            $update_fields['post_title'] = sanitize_text_field($data['Titel']);
        }
        if (!empty($data['Caption'])) {
            $update_fields['post_excerpt'] = sanitize_text_field($data['Caption']);
        }
        if (!empty($data['Description'])) {
            $update_fields['post_content'] = wp_kses_post($data['Description']);
        }

        if (!empty($update_fields)) {
            $update_fields['ID'] = $attachmentId;
            wp_update_post($update_fields);
        }

        if (!empty($data['Alt-tag'])) {
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

    private static function create_new_attachment(array $data): void
    {
        $mimeType = self::get_mime_type($data['Bestandsnaam'] ?? '');
        if (!$mimeType) {
            return;
        }

        $uploadDir = wp_upload_dir();
        $filePath  = $uploadDir['basedir'] . '/' . $data['Bestandsnaam'];

        if (!file_exists($filePath)) {
            return;
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