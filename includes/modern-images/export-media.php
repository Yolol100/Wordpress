<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class Modern_Image_Manager_Export
{
    public static function render_export_page(): void
    {
        if (isset($_POST['export_csv'])) {
            $export_option    = $_POST['export_option'] ?? 'all';
            $selected_columns = isset($_POST['export_columns'])
                ? array_map('sanitize_text_field', $_POST['export_columns'])
                : [];

            self::generate_csv($export_option, $selected_columns);
            exit;
        }

        include plugin_dir_path(__FILE__) . '../../templates/export-page.php';
    }

    public static function generate_csv(string $export_option = 'all', array $selected_columns = []): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=media-export.csv');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(__('Kon output stream niet openen.', 'modern-image-manager'));
        }

        $column_headers = [
            'filename'     => __('Bestandsnaam', 'modern-image-manager'),
            'new_filename' => __('Nieuwe bestandsnaam', 'modern-image-manager'),
            'page'         => __('Pagina', 'modern-image-manager'),
            'title'        => __('Titel', 'modern-image-manager'),
            'caption'      => __('Caption', 'modern-image-manager'),
            'description'  => __('Description', 'modern-image-manager'),
            'alt_text'     => __('Alt-tag', 'modern-image-manager'),
            'file_url'     => __('Bestands-URL', 'modern-image-manager'),
        ];

        if (empty($selected_columns)) {
            $selected_columns = array_keys($column_headers);
        }

        $csv_headers = array_map(
            static fn(string $column): string => $column_headers[$column] ?? '',
            $selected_columns
        );
        fputcsv($output, $csv_headers);

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];

        $media_items = get_posts($args);

        if (empty($media_items)) {
            echo '<p>' . __('Geen afbeeldingen gevonden voor de geselecteerde filteroptie.', 'modern-image-manager') . '</p>';
            return;
        }

        foreach ($media_items as $media) {
            $attachment_id = $media->ID;
            $attachment    = get_post($attachment_id);

            if (!$attachment) {
                continue;
            }

            $file_path   = get_attached_file($attachment_id);
            $filename    = basename($file_path) ?: '';
            $alt_text    = get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: '';
            $caption     = $attachment->post_excerpt ?: '';
            $description = $attachment->post_content ?: '';
            $title       = $attachment->post_title ?: '';
            $file_url    = wp_get_attachment_url($attachment_id) ?: '';

            $pagina = __('Geen', 'modern-image-manager');
            if ($attachment->post_parent) {
                $parent_post = get_post($attachment->post_parent);
                if ($parent_post) {
                    $pagina = get_the_title($parent_post);
                }
            }

            if (
                ($export_option === 'linked' && $pagina === __('Geen', 'modern-image-manager')) ||
                ($export_option === 'unlinked' && $pagina !== __('Geen', 'modern-image-manager'))
            ) {
                continue;
            }

            $data = [];
            foreach ($selected_columns as $column) {
                $data[] = match ($column) {
                    'filename'      => $filename,
                    'new_filename'  => '',
                    'page'          => $pagina,
                    'title'         => $title,
                    'caption'       => $caption,
                    'description'   => $description,
                    'alt_text'      => $alt_text,
                    'file_url'      => $file_url,
                    default         => '',
                };
            }

            fputcsv($output, $data);
        }

        fclose($output);
        exit;
    }
}