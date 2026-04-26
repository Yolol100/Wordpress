<?php
// File: class-export.php
// Export functionaliteit

declare(strict_types=1);
defined('ABSPATH') || exit;

final class Modern_Image_Manager_Export
{
    // Render export pagina
    public static function render_export_page(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(__('Je hebt geen toegang tot deze pagina.', 'modern-image-manager'));
        }
        $error_message = get_transient('media_export_error_message');
        if ($error_message) {
            delete_transient('media_export_error_message');
        }
        include plugin_dir_path(__FILE__) . '../../templates/export-page.php';
    }

    // Download-handler (registreer deze via admin_post_)
    public static function handle_export_download(): void
    {
        // Opties ophalen uit $_GET, nooit uit sessie!
        $export_option = sanitize_key(wp_unslash($_GET['export_option'] ?? 'all'));
        $selected_columns = isset($_GET['export_columns']) ? array_map('sanitize_key', (array) wp_unslash($_GET['export_columns'])) : [];

        if (!current_user_can('upload_files')) {
            wp_die(__('Je hebt geen toestemming om deze export te downloaden.', 'modern-image-manager'));
        }
        check_admin_referer('mim_export_csv', 'mim_export_nonce');
        // Direct CSV genereren (zonder enige andere WP output!)
        self::generate_csv($export_option, $selected_columns);
    }

    // Genereer CSV en output naar browser
    public static function generate_csv(string $export_option = 'all', array $selected_columns = []): void
    {
        // Maak helemaal zeker dat er geen output is!
        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=media-export.csv');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: 0');

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

        $selected_columns = array_values(array_intersect(array_keys($column_headers), $selected_columns));

        if (empty($selected_columns)) {
            $selected_columns = array_keys($column_headers);
        }

        $csv_headers = array_map(
            static fn(string $column): string => $column_headers[$column] ?? '',
            $selected_columns
        );
        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(__('Kon output stream niet openen.', 'modern-image-manager'));
        }
        fputcsv($output, $csv_headers);

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'post_status'    => 'inherit',
            'posts_per_page' => 500,
            'no_found_rows'  => true,
        ];

        $media_items = get_posts($args);

        if (empty($media_items)) {
            fclose($output);
            exit;
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

            // Filter 'linked' / 'unlinked'
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

// Hook de download-handler aan admin_post_
add_action('admin_post_mim_export_csv', [Modern_Image_Manager_Export::class, 'handle_export_download']);