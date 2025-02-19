<?php
declare(strict_types=1);

// Blokkeer directe toegang
defined('ABSPATH') || exit;

/**
 * Class Modern_Image_Manager_Export
 *
 * Exporteert media als CSV.
 */
class Modern_Image_Manager_Export {
    /**
     * Rendert de exportpagina en verwerkt het CSV-exportproces indien nodig.
     *
     * @return void
     */
    public static function render_export_page(): void {
        if (isset($_POST['export_csv'])) {
            $export_option = $_POST['export_option'] ?? 'all';
            self::generate_csv($export_option);
            exit;
        }
        // Laad de HTML-template
        include plugin_dir_path(__FILE__) . '../templates/export-page.php';
    }

    /**
     * Genereert en verstuurt een CSV-bestand met media-informatie.
     *
     * @param string $export_option Export-optie (all, linked, unlinked)
     * @return void
     */
    public static function generate_csv(string $export_option = 'all'): void {
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=media-export.csv');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die('Kon output stream niet openen.');
        }

        // Schrijf de kolomheaders naar het CSV-bestand
        fputcsv($output, [
            'Bestandsnaam',
            'Nieuwe bestandsnaam',
            'Pagina',
            'Titel',
            'Caption',
            'Description',
            'Alt-tag',
            'Bestands-URL'
        ]);

        // Query alle media bijlagen
        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];
        $media_items = get_posts($args);

        foreach ($media_items as $media) {
            $attachment_id = $media->ID;
            $attachment    = get_post($attachment_id);

            if (!$attachment) {
                continue;
            }

            $file_path   = get_attached_file($attachment_id);
            $filename    = $file_path ? basename($file_path) : '';
            $alt_text    = get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: '';
            $caption     = $attachment->post_excerpt ?: '';
            $description = $attachment->post_content ?: '';
            $title       = $attachment->post_title ?: '';
            $file_url    = wp_get_attachment_url($attachment_id) ?: '';

            // Bepaal of de afbeelding gelinkt is aan een pagina
            $pagina = 'Geen';
            if ($attachment->post_parent) {
                $parent_post = get_post($attachment->post_parent);
                if ($parent_post) {
                    $pagina = get_the_title($parent_post);
                }
            }

            // Filteren op basis van de gekozen export-optie
            if ($export_option === 'linked' && $pagina === 'Geen') {
                continue;
            } elseif ($export_option === 'unlinked' && $pagina !== 'Geen') {
                continue;
            }

            // Schrijf de gegevens naar de CSV
            fputcsv($output, [
                $filename,
                '',
                $pagina,
                $title,
                $caption,
                $description,
                $alt_text,
                $file_url
            ]);
        }

        fclose($output);
        exit;
    }
}