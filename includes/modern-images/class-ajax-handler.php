<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class ModernImageManagerAjaxHandler
{
    public static function init(): void
    {
        add_action('wp_ajax_update_media_meta',       [self::class, 'updateMediaMetaCallback']);
        add_action('wp_ajax_delete_media',            [self::class, 'deleteMediaCallback']);
        add_action('wp_ajax_bulk_delete_media',       [self::class, 'bulkDeleteMediaCallback']);
        add_action('wp_ajax_get_export_row_count',    [self::class, 'getExportRowCountCallback']);
        add_action('wp_ajax_get_export_preview_data', [self::class, 'getExportPreviewDataCallback']);
        add_action('wp_ajax_filter_media',            [self::class, 'filterMediaCallback']);
    }

    public static function updateMediaMetaCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $attachmentId   = self::sanitizeInt($_POST['attachment_id'] ?? null);
        $newTitle       = sanitize_text_field($_POST['title'] ?? '');
        $newCaption     = sanitize_text_field($_POST['caption'] ?? '');
        $newDescription = sanitize_textarea_field($_POST['description'] ?? '');
        $newAlt         = sanitize_text_field($_POST['alt'] ?? '');
        $newFilename    = sanitize_file_name($_POST['new_filename'] ?? '');

        if (! $attachmentId) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
        }

        $attachment = get_post($attachmentId);
        if (! $attachment) {
            wp_send_json_error(['message' => __('Bijlage niet gevonden.', 'modern-image-manager')]);
        }

        $newTitle       = self::removeFileExtension($newTitle);
        $newCaption     = self::removeFileExtension($newCaption);
        $newDescription = self::removeFileExtension($newDescription);
        $newAlt         = self::removeFileExtension($newAlt);

        $result = wp_update_post([
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ], true);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => __('Fout bij bijwerken media: ', 'modern-image-manager')
                    . $result->get_error_message()
            ]);
        }

        if ($newAlt !== '') {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', $newAlt);
        }

        if ($newFilename !== '' && ! self::updateFileName($attachmentId, $newFilename)) {
            wp_send_json_error(['message' => __('Fout bij hernoemen bestand.', 'modern-image-manager')]);
        }

        wp_send_json_success(['message' => __('Media info bijgewerkt.', 'modern-image-manager')]);
    }

    public static function deleteMediaCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (! $attachmentId) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
        }

        $deleted = wp_delete_attachment($attachmentId, true);
        $deleted
            ? wp_send_json_success(['message' => __('Media verwijderd.', 'modern-image-manager')])
            : wp_send_json_error(['message' => __('Fout bij verwijderen media.', 'modern-image-manager')]);
    }

    public static function bulkDeleteMediaCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $ids = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids'])
            ? array_map('intval', $_POST['attachment_ids'])
            : [];

        if (empty($ids)) {
            wp_send_json_error(['message' => __('Geen media-ID’s ontvangen.', 'modern-image-manager')]);
        }

        foreach ($ids as $id) {
            wp_delete_attachment($id, true);
        }

        wp_send_json_success(['message' => __('Geselecteerde media verwijderd.', 'modern-image-manager')]);
    }

    public static function getExportRowCountCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $option = sanitize_text_field($_POST['export_option'] ?? 'all');

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ($option === 'linked') {
            $query_args['meta_query'][] = [
                'key'     => '_wp_attachment_context',
                'compare' => 'EXISTS',
            ];
        } elseif ($option === 'unlinked') {
            $query_args['meta_query'][] = [
                'key'     => '_wp_attachment_context',
                'compare' => 'NOT EXISTS',
            ];
        }

        $query = new WP_Query($query_args);
        $ids = $query->posts;
        $totalSize = 0;

        foreach ($ids as $id) {
            $file = get_attached_file($id);
            if ($file && file_exists($file)) {
                $totalSize += filesize($file);
            }
        }

        wp_send_json_success([
            'count'      => count($ids),
            'total_size' => $totalSize,
        ]);
    }

    public static function getExportPreviewDataCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $columns = isset($_POST['columns']) && is_array($_POST['columns'])
            ? array_map('sanitize_text_field', $_POST['columns'])
            : [];

        $option = sanitize_text_field($_POST['export_option'] ?? 'all');

        if (empty($columns)) {
            wp_send_json_error(['message' => __('Geen kolommen geselecteerd.', 'modern-image-manager')]);
        }

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 5,
        ];

        if ($option === 'linked') {
            $query_args['meta_query'][] = [
                'key'     => '_wp_attachment_context',
                'compare' => 'EXISTS',
            ];
        } elseif ($option === 'unlinked') {
            $query_args['meta_query'][] = [
                'key'     => '_wp_attachment_context',
                'compare' => 'NOT EXISTS',
            ];
        }

        $query = new WP_Query($query_args);
        $data = [];

        foreach ($query->posts as $post) {
            $row = [];

            foreach ($columns as $column) {
                switch ($column) {
                    case 'filename':
                        $row['filename'] = basename(get_attached_file($post->ID));
                        break;
                    case 'new_filename':
                        $row['new_filename'] = '';
                        break;
                    case 'page':
                        $row['page'] = get_post_meta($post->ID, '_wp_attachment_context', true) ?: '-';
                        break;
                    case 'title':
                        $row['title'] = $post->post_title;
                        break;
                    case 'caption':
                        $row['caption'] = $post->post_excerpt;
                        break;
                    case 'description':
                        $row['description'] = $post->post_content;
                        break;
                    case 'alt_text':
                        $row['alt_text'] = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
                        break;
                    case 'file_url':
                        $row['file_url'] = wp_get_attachment_url($post->ID);
                        break;
                    default:
                        $row[$column] = '-';
                }
            }

            $data[] = $row;
        }

        wp_send_json_success(['preview' => $data]);
    }

    public static function filterMediaCallback(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ($search !== '') {
            // Zoek in post_title (Titel), post_excerpt (Caption), post_content (Description)
            $args['s'] = $search;

            // Zoek in alt-tag
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];

            // Zoek in bestandsnaam (guid)
            add_filter('posts_where', function ($where, $query) use ($search) {
                global $wpdb;
                $where .= " OR {$wpdb->posts}.guid LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%'";
                return $where;
            }, 10, 2);
        }

        if ($filter === 'linked') {
            $args['meta_query'][] = [
                'relation' => 'OR',
                ['key' => '_wp_attachment_context', 'compare' => 'EXISTS'],
                ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
            ];
            $args['post_parent__not_in'] = [0];
        } elseif ($filter === 'unlinked') {
            $args['meta_query'][] = [
                'relation' => 'AND',
                ['key' => '_wp_attachment_context', 'compare' => 'NOT EXISTS'],
                ['key' => '_thumbnail_id', 'compare' => 'NOT EXISTS'],
            ];
            $args['post_parent'] = 0;
        }

        $query = new WP_Query($args);
        $rows  = [];

        foreach ($query->posts as $id) {
            $status   = get_post_meta($id, '_wp_attachment_context', true) ? 'linked' : 'unlinked';
            $filename = esc_html(pathinfo(get_attached_file($id), PATHINFO_FILENAME));
            $page     = esc_html(modern_image_manager_get_attached_post_title($id));
            $title    = esc_attr(get_post($id)->post_title);
            $caption  = esc_attr(get_post($id)->post_excerpt);
            $desc     = esc_textarea(get_post($id)->post_content);
            $alt      = esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true));

            $tr  = "<tr class=\"media-items-tr-item unique-tr-{$id}\" data-status=\"{$status}\">";
            $tr .= "<td><input type=\"checkbox\" class=\"bulk-delete-checkbox\" data-attachment-id=\"{$id}\" /></td>";
            $tr .= "<td><input type=\"text\" class=\"filename-input media-items-filename-input\" data-attachment-id=\"{$id}\" value=\"{$filename}\" readonly></td>";
            $tr .= "<td><input type=\"text\" class=\"new-filename-input media-items-new-filename-input\" data-attachment-id=\"{$id}\" placeholder=\"" . esc_attr__('Voer nieuwe bestandsnaam in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td>{$page}</td>";
            $tr .= "<td><input type=\"text\" class=\"title-input media-items-title-input\" data-attachment-id=\"{$id}\" value=\"{$title}\" placeholder=\"" . esc_attr__('Voer titel in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><input type=\"text\" class=\"caption-input media-items-caption-input\" data-attachment-id=\"{$id}\" value=\"{$caption}\" placeholder=\"" . esc_attr__('Voer caption in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><textarea class=\"description-input media-items-description-input\" data-attachment-id=\"{$id}\" placeholder=\"" . esc_attr__('Voer description in', 'modern-image-manager') . "\">{$desc}</textarea></td>";
            $tr .= "<td><input type=\"text\" class=\"alt-input media-items-alt-input\" data-attachment-id=\"{$id}\" value=\"{$alt}\" placeholder=\"" . esc_attr__('Voer alt-tag in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><button class=\"save-meta page-button media-items-save-meta\" data-attachment-id=\"{$id}\">" . esc_html__('Opslaan', 'modern-image-manager') . "</button></td>";
            $tr .= "<td><button class=\"delete-media page-button media-items-delete-media\" data-attachment-id=\"{$id}\">" . esc_html__('Verwijderen', 'modern-image-manager') . "</button></td>";
            $tr .= '</tr>';

            $rows[] = $tr;
        }

        wp_send_json_success(['rows' => $rows]);
    }

    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        $file = get_attached_file($attachmentId);
        if (! $file || ! file_exists($file)) {
            return false;
        }

        $dir     = pathinfo($file, PATHINFO_DIRNAME);
        $ext     = pathinfo($file, PATHINFO_EXTENSION);
        $newFile = "{$dir}/{$newFilename}.{$ext}";

        if ($file === $newFile) {
            return true;
        }

        if (file_exists($newFile) || ! rename($file, $newFile)) {
            return false;
        }

        update_attached_file($attachmentId, $newFile);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachmentId, $newFile);
        if (! $meta) {
            return false;
        }
        wp_update_attachment_metadata($attachmentId, $meta);

        $url = wp_upload_dir()['baseurl'] . '/' . basename($newFile);
        wp_update_post([
            'ID'        => $attachmentId,
            'guid'      => $url,
            'post_name' => sanitize_title($newFilename),
        ]);

        return true;
    }

    private static function removeFileExtension(string $text): string
    {
        return preg_replace('/\.[^.\s]{2,5}$/', '', $text) ?? $text;
    }

    private static function sanitizeInt($value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }
}

ModernImageManagerAjaxHandler::init();