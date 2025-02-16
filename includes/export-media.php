<?php
declare(strict_types=1);

// Blokkeer directe toegang
defined('ABSPATH') || exit;

/**
 * Class Modern_Image_Manager_Export
 *
 * Verwerkt het exporteren van media als CSV.
 */
class Modern_Image_Manager_Export {
    /**
     * Rendert de exportpagina en verwerkt het CSV-exportproces indien nodig.
     *
     * @return void
     */
    public static function render_export_page(): void {
        if (isset($_POST['export_csv'])) {
            self::generate_csv();
            exit;
        }
        // Laad de HTML-template
        include plugin_dir_path(__FILE__) . '../templates/export-page.php';
    }

    /**
     * Genereert en verstuurt een CSV-bestand met media-informatie.
     *
     * @return void
     */
    public static function generate_csv(): void {
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
            'Nieuwe bestands naam',
            'Pagina',
            'Titel',
            'Caption',
            'Description',
            'Alt-tag'
        ]);

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];
        $media_items = new WP_Query($args);

        if ($media_items->have_posts()) {
            while ($media_items->have_posts()) {
                $media_items->the_post();
                $attachment_id = (int) get_the_ID();
                $attachment    = get_post($attachment_id);

                if (!$attachment) {
                    continue;
                }

                $file_path   = get_attached_file($attachment_id);
                $filename    = $file_path ? basename($file_path) : '';
                $alt_text    = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $caption     = (string) $attachment->post_excerpt;
                $description = (string) $attachment->post_content;
                $title       = (string) $attachment->post_title;

                $pagina = 'Geen';
                if ($attachment->post_parent) {
                    $parent_post = get_post($attachment->post_parent);
                    if ($parent_post) {
                        $pagina = get_the_title($parent_post);
                    }
                }

                fputcsv($output, [
                    $filename,
                    '',
                    $pagina,
                    $title,
                    $caption,
                    $description,
                    $alt_text,
                ]);
            }
        }

        wp_reset_postdata();
        fclose($output);
        exit;
    }
}