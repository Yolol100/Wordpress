<?php
// File: includes/modern-images/class-ajax-handler.php

declare(strict_types=1);
defined('ABSPATH') || exit;

final class ModernImageManagerAjaxHandler
{
    public static function init(): void
    {
        add_action('wp_ajax_mim_update_media_meta', [self::class, 'updateMediaMetaCallback']);
        add_action('wp_ajax_mim_delete_media', [self::class, 'deleteMediaCallback']);
        add_action('wp_ajax_mim_bulk_delete_media', [self::class, 'bulkDeleteMediaCallback']);
        add_action('wp_ajax_mim_get_export_row_count', [self::class, 'getExportRowCountCallback']);
        add_action('wp_ajax_mim_get_export_preview_data', [self::class, 'getExportPreviewDataCallback']);
        add_action('wp_ajax_mim_filter_media', [self::class, 'filterMediaCallback']);
        add_action('wp_ajax_mim_scan_all_duplicates', [self::class, 'scanAllDuplicatesCallback']);
    }

    private static function verifyAjax(string $capability = 'upload_files'): void
    {
        check_ajax_referer('modern_image_manager_ajax', 'nonce');

        if (!current_user_can($capability)) {
            wp_send_json_error(['message' => __('Je hebt geen rechten om deze actie uit te voeren.', 'modern-image-manager')], 403);
        }
    }

    public static function scanAllDuplicatesCallback(): void
    {
        self::verifyAjax('manage_options');

        $media_query = function_exists('modern_image_manager_get_media_items')
            ? modern_image_manager_get_media_items()
            : new WP_Query([
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => 500,
                'fields'         => 'ids',
            ]);

        $media_ids = wp_list_pluck($media_query->posts, 'ID');
        $media_ids = array_map('intval', $media_ids);

        $referenced_ids = function_exists('modern_image_manager_get_all_referenced_attachment_ids')
            ? modern_image_manager_get_all_referenced_attachment_ids()
            : [];
        $referenced_ids = array_map('intval', $referenced_ids);

        $count_ids = array_count_values(array_merge($media_ids, $referenced_ids));
        $duplicates = [];
        foreach ($count_ids as $id => $count) {
            if ($count > 1) {
                $duplicates[] = (int) $id;
            }
        }

        $results = [];
        foreach ($duplicates as $id) {
            $post = get_post($id);
            if (!$post || $post->post_type !== 'attachment') {
                continue;
            }

            $context_types = [];
            if (in_array($id, $media_ids, true)) {
                $context_types[] = 'media_bibliotheek';
            }
            if (in_array($id, $referenced_ids, true)) {
                $context_types = array_merge($context_types, self::getContextTypeForAttachment($id));
            }

            $results[] = [
                'ID'    => $id,
                'title' => get_the_title($id),
                'url'   => wp_get_attachment_url($id),
                'type'  => implode(', ', array_unique($context_types)) ?: 'onbekend',
            ];
        }

        wp_send_json_success(['duplicates' => $results, 'total' => count($results)]);
    }

    private static function getContextTypeForAttachment(int $attachment_id): array
    {
        global $wpdb;
        $types = [];

        $elementor = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value LIKE %s",
            '%elementor%',
            '%' . $wpdb->esc_like((string) $attachment_id) . '%'
        ));
        if ($elementor > 0) {
            $types[] = 'elementor';
        }

        $acf = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value = %d",
            'field_%',
            $attachment_id
        ));
        if ($acf > 0) {
            $types[] = 'acf_field';
        }

        $gallery = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s) AND meta_value LIKE %s",
            '%woo%',
            '%gallery%',
            '%product_image%',
            '%' . $wpdb->esc_like((string) $attachment_id) . '%'
        ));
        if ($gallery > 0) {
            $types[] = 'woocommerce';
        }

        return $types;
    }

    public static function updateMediaMetaCallback(): void
    {
        self::verifyAjax('upload_files');

        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (!$attachmentId || !current_user_can('edit_post', $attachmentId)) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID of onvoldoende rechten.', 'modern-image-manager')], 403);
        }

        $attachment = get_post($attachmentId);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            wp_send_json_error(['message' => __('Bijlage niet gevonden.', 'modern-image-manager')], 404);
        }

        $newTitle       = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $newCaption     = sanitize_text_field(wp_unslash($_POST['caption'] ?? ''));
        $newDescription = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $newAlt         = sanitize_text_field(wp_unslash($_POST['alt'] ?? ''));
        $newFilename    = sanitize_file_name(wp_unslash($_POST['new_filename'] ?? ''));
        $newFilename    = self::removeFileExtension($newFilename);

        $result = wp_update_post([
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ], true);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Fout bij bijwerken media.', 'modern-image-manager')]);
        }

        update_post_meta($attachmentId, '_wp_attachment_image_alt', $newAlt);

        if ($newFilename !== '' && !self::updateFileName($attachmentId, $newFilename)) {
            wp_send_json_error(['message' => __('Fout bij hernoemen bestand.', 'modern-image-manager')]);
        }

        wp_send_json_success(['message' => __('Media informatie succesvol bijgewerkt.', 'modern-image-manager')]);
    }

    public static function deleteMediaCallback(): void
    {
        self::verifyAjax('upload_files');

        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (!$attachmentId || !current_user_can('delete_post', $attachmentId)) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID of onvoldoende rechten.', 'modern-image-manager')], 403);
        }

        $deleted = wp_delete_attachment($attachmentId, true);
        if ($deleted) {
            wp_send_json_success(['message' => __('Media succesvol verwijderd.', 'modern-image-manager')]);
        }

        wp_send_json_error(['message' => __('Fout bij verwijderen media.', 'modern-image-manager')]);
    }

    public static function bulkDeleteMediaCallback(): void
    {
        self::verifyAjax('upload_files');

        $ids = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids'])
            ? array_map('intval', wp_unslash($_POST['attachment_ids']))
            : [];
        $ids = array_values(array_filter(array_unique($ids)));

        if (empty($ids)) {
            wp_send_json_error(['message' => __('Geen media-ID’s ontvangen.', 'modern-image-manager')]);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if (!current_user_can('delete_post', $id)) {
                continue;
            }
            if (wp_delete_attachment($id, true)) {
                $deleted++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(__('Er zijn %d media-items verwijderd.', 'modern-image-manager'), $deleted),
            'count'   => $deleted,
        ]);
    }

    public static function getExportRowCountCallback(): void
    {
        self::verifyAjax('upload_files');

        $option = sanitize_key(wp_unslash($_POST['export_option'] ?? 'all'));
        if (!in_array($option, ['all', 'linked', 'unlinked'], true)) {
            $option = 'all';
        }
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 500,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];

        if ($option === 'linked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'EXISTS'];
        } elseif ($option === 'unlinked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'NOT EXISTS'];
        }

        $ids = get_posts($query_args);
        $totalSize = 0;
        foreach ($ids as $id) {
            $file = get_attached_file((int) $id);
            if ($file && is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        wp_send_json_success(['count' => count($ids), 'total_size' => $totalSize]);
    }

    public static function getExportPreviewDataCallback(): void
    {
        self::verifyAjax('upload_files');

        $allowed_columns = ['filename', 'new_filename', 'page', 'title', 'caption', 'description', 'alt_text', 'file_url'];
        $columns = isset($_POST['columns']) && is_array($_POST['columns'])
            ? array_values(array_intersect($allowed_columns, array_map('sanitize_key', wp_unslash($_POST['columns']))))
            : [];
        $option = sanitize_key(wp_unslash($_POST['export_option'] ?? 'all'));
        if (!in_array($option, ['all', 'linked', 'unlinked'], true)) {
            $option = 'all';
        }

        if (empty($columns)) {
            wp_send_json_error(['message' => __('Geen geldige kolommen geselecteerd.', 'modern-image-manager')]);
        }

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 5,
        ];

        if ($option === 'linked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'EXISTS'];
        } elseif ($option === 'unlinked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'NOT EXISTS'];
        }

        $query = new WP_Query($query_args);
        $data = [];
        foreach ($query->posts as $post) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = match ($column) {
                    'filename'      => basename((string) get_attached_file($post->ID)),
                    'new_filename'  => '',
                    'page'          => get_post_meta($post->ID, '_wp_attachment_context', true) ?: '-',
                    'title'         => $post->post_title,
                    'caption'       => $post->post_excerpt,
                    'description'   => $post->post_content,
                    'alt_text'      => get_post_meta($post->ID, '_wp_attachment_image_alt', true),
                    'file_url'      => wp_get_attachment_url($post->ID),
                    default         => '-',
                };
            }
            $data[] = $row;
        }

        wp_send_json_success(['preview' => $data]);
    }

    public static function filterMediaCallback(): void
    {
        self::verifyAjax('upload_files');

        $search  = sanitize_text_field(wp_unslash($_POST['search'] ?? ''));
        $filter  = sanitize_key(wp_unslash($_POST['filter'] ?? 'all'));
        if (!in_array($filter, ['all', 'linked', 'unlinked'], true)) {
            $filter = 'all';
        }
        $page    = max(1, (int) ($_POST['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_POST['per_page'] ?? 20)));

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $perPage,
            'paged'          => $page,
            'fields'         => 'ids',
        ];

        if ($search !== '') {
            $args['s'] = $search;
        }

        if ($filter === 'linked') {
            $args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'EXISTS'];
        } elseif ($filter === 'unlinked') {
            $args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'NOT EXISTS'];
        }

        $query = new WP_Query($args);
        $rows = [];
        foreach ($query->posts as $id) {
            $id = (int) $id;
            $post = get_post($id);
            if (!$post) {
                continue;
            }
            $status    = get_post_meta($id, '_wp_attachment_context', true) ? 'linked' : 'unlinked';
            $filename  = esc_attr(pathinfo((string) get_attached_file($id), PATHINFO_FILENAME));
            $pageTitle = esc_html(function_exists('modern_image_manager_get_attached_post_title') ? modern_image_manager_get_attached_post_title($id) : '');
            $title     = esc_attr($post->post_title);
            $caption   = esc_attr($post->post_excerpt);
            $desc      = esc_textarea($post->post_content);
            $alt       = esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true));

            $rows[] = '<tr class="media-items-tr-item unique-tr-' . esc_attr((string) $id) . '" data-status="' . esc_attr($status) . '">'
                . '<td><input type="checkbox" class="bulk-delete-checkbox" data-attachment-id="' . esc_attr((string) $id) . '" /></td>'
                . '<td><input type="text" class="filename-input media-items-filename-input" data-attachment-id="' . esc_attr((string) $id) . '" value="' . $filename . '" readonly></td>'
                . '<td><input type="text" class="new-filename-input media-items-new-filename-input" data-attachment-id="' . esc_attr((string) $id) . '" placeholder="' . esc_attr__('Voer nieuwe bestandsnaam in', 'modern-image-manager') . '"></td>'
                . '<td>' . $pageTitle . '</td>'
                . '<td><input type="text" class="title-input media-items-title-input" data-attachment-id="' . esc_attr((string) $id) . '" value="' . $title . '" placeholder="' . esc_attr__('Voer titel in', 'modern-image-manager') . '"></td>'
                . '<td><input type="text" class="caption-input media-items-caption-input" data-attachment-id="' . esc_attr((string) $id) . '" value="' . $caption . '" placeholder="' . esc_attr__('Voer caption in', 'modern-image-manager') . '"></td>'
                . '<td><textarea class="description-input media-items-description-input" data-attachment-id="' . esc_attr((string) $id) . '" placeholder="' . esc_attr__('Voer description in', 'modern-image-manager') . '">' . $desc . '</textarea></td>'
                . '<td><input type="text" class="alt-input media-items-alt-input" data-attachment-id="' . esc_attr((string) $id) . '" value="' . $alt . '" placeholder="' . esc_attr__('Voer alt-tag in', 'modern-image-manager') . '"></td>'
                . '<td><button class="save-meta page-button media-items-save-meta" data-attachment-id="' . esc_attr((string) $id) . '">' . esc_html__('Opslaan', 'modern-image-manager') . '</button></td>'
                . '<td><button class="delete-media page-button media-items-delete-media" data-attachment-id="' . esc_attr((string) $id) . '">' . esc_html__('Verwijderen', 'modern-image-manager') . '</button></td>'
                . '</tr>';
        }

        wp_send_json_success([
            'rows'     => $rows,
            'total'    => (int) $query->found_posts,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        $file = get_attached_file($attachmentId);
        if (!$file || !is_file($file)) {
            return false;
        }

        $dir = pathinfo($file, PATHINFO_DIRNAME);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $newFilename = sanitize_file_name(self::removeFileExtension($newFilename));
        if ($newFilename === '' || str_contains($newFilename, '..')) {
            return false;
        }

        $newFile = trailingslashit($dir) . $newFilename . '.' . $ext;
        if ($file === $newFile) {
            return true;
        }
        if (file_exists($newFile) || !rename($file, $newFile)) {
            return false;
        }

        update_attached_file($attachmentId, $newFile);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachmentId, $newFile);
        if ($meta) {
            wp_update_attachment_metadata($attachmentId, $meta);
        }

        wp_update_post([
            'ID'        => $attachmentId,
            'post_name' => sanitize_title($newFilename),
        ]);

        return true;
    }

    private static function removeFileExtension(string $text): string
    {
        return preg_replace('/\.[^.\s]{2,5}$/', '', $text) ?? $text;
    }

    private static function sanitizeInt(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }
}

ModernImageManagerAjaxHandler::init();
