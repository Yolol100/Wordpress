<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class Modern_Image_Manager_Zip
{
    // Render de adminpagina met de ZIP-export interface
    public static function render_zip_page(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }
        include MODERN_IMAGE_MANAGER_PATH . 'templates/zip-page.php';
    }

    // Handler voor het aanmaken en downloaden van de ZIP
    public static function handle_zip_download(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(__('Je hebt geen toestemming om deze actie uit te voeren.', 'modern-image-manager'));
        }

        check_admin_referer('mim_zip_export_nonce', 'mim_zip_export_nonce_field');

        // Haal ALLE attachments op van type afbeelding (met limiet voor performance)
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 500, // Limiet toegevoegd ivm performance en veiligheid
            'post_mime_type' => 'image', // Alleen afbeeldingen!
            'fields'         => 'ids',   // Alleen IDs voor performance
        ];
        $attachment_ids = get_posts($args);

        if (!$attachment_ids) {
            wp_die(__('Er zijn geen afbeeldingen gevonden in de mediabibliotheek.', 'modern-image-manager'));
        }

        $upload_dir = wp_upload_dir();
        $tmp_dir = trailingslashit($upload_dir['basedir']) . 'modern-image-manager-tmp';
        if (!wp_mkdir_p($tmp_dir)) {
            wp_die(__('Kon tijdelijke exportmap niet aanmaken.', 'modern-image-manager'));
        }
        $zip_file_path = trailingslashit($tmp_dir) . 'media-export-' . wp_generate_uuid4() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zip_file_path, ZipArchive::CREATE) !== true) {
            wp_die(__('Kon het ZIP-bestand niet aanmaken.', 'modern-image-manager'));
        }

        $found = 0;
        foreach ($attachment_ids as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            if ($file_path && file_exists($file_path)) {
                $filename = basename($file_path);

                // Beveiliging: controleer extensie strikt (alleen afbeeldingen)
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_extensions, true)) {
                    continue; // Sla bestand over met onveilige extensie
                }

                $archive_name = 'Plaatjes/' . (int) $attachment_id . '-' . sanitize_file_name($filename);
                $zip->addFile($file_path, $archive_name);
                $found++;
            }
        }

        $zip->close();

        if (!file_exists($zip_file_path) || $found === 0) {
            wp_die(__('Geen afbeeldingen gevonden om te zippen.', 'modern-image-manager'));
        }

        // Headers voor download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="media-export.zip"');
        header('Content-Length: ' . filesize($zip_file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (ob_get_length()) {
            ob_end_clean();
        }

        readfile($zip_file_path);

        // Verwijder tijdelijk bestand
        unlink($zip_file_path);

        exit;
    }
}

// Registreer de admin_post actie voor de download handler
add_action('admin_post_modern_image_manager_zip_export', ['Modern_Image_Manager_Zip', 'handle_zip_download']);
