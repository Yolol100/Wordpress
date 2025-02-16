<?php
declare(strict_types=1);

// Blokkeer directe toegang
defined('ABSPATH') || exit;

/**
 * Class Modern_Image_Manager_Import
 *
 * Verwerkt de import van CSV-bestanden voor media.
 */
class Modern_Image_Manager_Import {

    /**
     * Rendert de importpagina en handelt de import af indien nodig.
     *
     * @return void
     */
    public static function render_import_page(): void {
        if (isset($_POST['import_csv'])) {
            self::handle_import();
        }
        include MODERN_IMAGE_MANAGER_PATH . 'templates/import-page.php';
    }

    /**
     * Verwerkt het geüploade CSV-bestand en werkt bestaande of nieuwe bijlagen bij.
     *
     * @return void
     */
    public static function handle_import(): void {
        if (empty($_FILES['import_file']['tmp_name'])) {
            wp_die('Geen bestand geüpload.');
        }

        $file = $_FILES['import_file']['tmp_name'];
        $csvData = array_map('str_getcsv', file($file));

        if (empty($csvData) || !is_array($csvData)) {
            wp_die('Ongeldig CSV-bestand.');
        }

        $headers = array_shift($csvData);
        if (!is_array($headers)) {
            wp_die('Ongeldige CSV headers.');
        }

        foreach ($csvData as $row) {
            $data = array_combine($headers, $row);
            if (empty($data['Bestandsnaam'])) {
                continue;
            }

            $newFilenameInput = isset($data['Nieuwe bestands naam']) ? trim($data['Nieuwe bestands naam']) : '';
            if (!empty($newFilenameInput)) {
                $newFilenameInput = pathinfo($newFilenameInput, PATHINFO_FILENAME);
            }

            $existingAttachment = self::get_attachment_by_filename($data['Bestandsnaam']);

            if ($existingAttachment) {
                $attachmentId = (int) $existingAttachment->ID;

                if (!empty($newFilenameInput)) {
                    $currentFile = get_attached_file($attachmentId);
                    $pathInfo = pathinfo($currentFile);
                    $extension = $pathInfo['extension'] ?? '';
                    $newFile = $pathInfo['dirname'] . '/' . $newFilenameInput . '.' . $extension;

                    if (file_exists($currentFile) && rename($currentFile, $newFile)) {
                        update_attached_file($attachmentId, $newFile);

                        $uploadDir = wp_upload_dir();
                        $newUrl = $uploadDir['baseurl'] . '/' . $newFilenameInput . '.' . $extension;
                        wp_update_post([
                            'ID'   => $attachmentId,
                            'guid' => $newUrl,
                        ]);
                    }
                }

                self::update_attachment_data($attachmentId, $data);
            } else {
                self::create_new_attachment($data);
            }
        }

        echo '<div class="notice notice-success"><p>Import voltooid en alle waarden overschreven!</p></div>';
    }

    /**
     * Werkt de gegevens van een bestaande bijlage bij.
     *
     * @param int   $attachmentId
     * @param array $data
     *
     * @return void
     */
    private static function update_attachment_data(int $attachmentId, array $data): void {
        if (isset($data['Titel'])) {
            wp_update_post(['ID' => $attachmentId, 'post_title' => sanitize_text_field($data['Titel'])]);
        }
        if (isset($data['Caption'])) {
            wp_update_post(['ID' => $attachmentId, 'post_excerpt' => sanitize_text_field($data['Caption'])]);
        }
        if (isset($data['Description'])) {
            wp_update_post(['ID' => $attachmentId, 'post_content' => wp_kses_post($data['Description'])]);
        }
        if (isset($data['Alt-tag'])) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($data['Alt-tag']));
        }
    }

    /**
     * Maakt een nieuwe bijlage aan op basis van de CSV-gegevens.
     *
     * @param array $data
     *
     * @return void
     */
    private static function create_new_attachment(array $data): void {
        $mimeType = self::get_mime_type($data['Bestandsnaam']);
        if (!$mimeType) {
            return;
        }

        $uploadDir = wp_upload_dir();
        $filePath = $uploadDir['basedir'] . '/' . $data['Bestandsnaam'];

        if (!file_exists($filePath)) {
            return;
        }

        $attachment = [
            'guid'           => $uploadDir['baseurl'] . '/' . $data['Bestandsnaam'],
            'post_mime_type' => $mimeType,
            'post_title'     => $data['Titel'] ?? '',
            'post_content'   => $data['Description'] ?? '',
            'post_excerpt'   => $data['Caption'] ?? '',
            'post_status'    => 'inherit'
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

    /**
     * Haalt een bestaande bijlage op basis van de bestandsnaam op.
     *
     * @param string $filename
     *
     * @return WP_Post|null
     */
    private static function get_attachment_by_filename(string $filename): ?WP_Post {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($filename);
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

    /**
     * Bepaalt de MIME-type op basis van de bestandsextensie.
     *
     * @param string $filename
     *
     * @return string|null
     */
    private static function get_mime_type(string $filename): ?string {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
        ];

        return $mimeTypes[strtolower($extension)] ?? null;
    }
}