<?php
// File: includes/modern-images/class-ajax-handler.php

declare(strict_types=1);
defined('ABSPATH') || exit;

final class ModernImageManagerAjaxHandler
{
    // Registreer AJAX hooks
    public static function init(): void
    {
        add_action('wp_ajax_update_media_meta',       [self::class, 'updateMediaMetaCallback']);
        add_action('wp_ajax_delete_media',            [self::class, 'deleteMediaCallback']);
        add_action('wp_ajax_bulk_delete_media',       [self::class, 'bulkDeleteMediaCallback']);
        add_action('wp_ajax_get_export_row_count',    [self::class, 'getExportRowCountCallback']);
        add_action('wp_ajax_get_export_preview_data', [self::class, 'getExportPreviewDataCallback']);
        add_action('wp_ajax_filter_media',            [self::class, 'filterMediaCallback']);
        // Nieuw: scan alles uit alle bronnen
        add_action('wp_ajax_scan_all_duplicates',     [self::class, 'scanAllDuplicatesCallback']);
    }

    /**
     * Scan alle dubbele afbeeldingen in media, Elementor, ACF en WooCommerce
     */
    public static function scanAllDuplicatesCallback(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen rechten om te scannen.', 'modern-image-manager')]);
        }

        require_once __DIR__ . '/helpers.php';

        // Haal ALLE attachment-IDs uit media, ACF, Elementor, WooCommerce, enz.
        $media_query = modern_image_manager_get_media_items();
        $media_ids   = wp_list_pluck($media_query->posts, 'ID');

        $referenced_ids = modern_image_manager_get_all_referenced_attachment_ids();

        // Combineer en tel
        $all_ids = array_merge($media_ids, $referenced_ids);

        // Tel dubbele IDs
        $count_ids = array_count_values($all_ids);

        // Dubbele: alle ID's die meer dan 1x voorkomen
        $duplicates = [];
        foreach ($count_ids as $id => $count) {
            if ($count > 1) {
                $duplicates[] = (int)$id;
            }
        }

        // Resultaat ophalen: bestandsinfo, context etc.
        $results = [];
        foreach ($duplicates as $id) {
            $post = get_post($id);
            if (!$post) {
                continue;
            }

            // Herkomst bepalen
            $context_types = [];
            // Media
            if (in_array($id, $media_ids)) {
                $context_types[] = 'media_bibliotheek';
            }
            // Elementor/ACF/Woo via meta
            if (in_array($id, $referenced_ids)) {
                $meta_context = self::getContextTypeForAttachment($id);
                if ($meta_context) {
                    $context_types = array_merge($context_types, $meta_context);
                }
            }
            $context_types = array_unique($context_types);

            $results[] = [
                'ID'       => $id,
                'title'    => get_the_title($id),
                'url'      => wp_get_attachment_url($id),
                'type'     => implode(', ', $context_types) ?: 'onbekend',
            ];
        }

        wp_send_json_success([
            'duplicates' => $results,
            'total'      => count($results),
        ]);
    }

    /**
     * Herken context/type van een attachment (Elementor, ACF, WooCommerce)
     * @return string[]
     */
    private static function getContextTypeForAttachment(int $attachment_id): array
    {
        global $wpdb;
        $types = [];

        // Elementor
        $elementor = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE %s AND meta_value LIKE %s",
                '%elementor%',
                '%' . $attachment_id . '%'
            )
        );
        if ($elementor > 0) {
            $types[] = 'elementor';
        }

        // ACF
        $acf = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE %s AND meta_value = %d",
                'field_%',
                $attachment_id
            )
        );
        if ($acf > 0) {
            $types[] = 'acf_field';
        }

        // WooCommerce
        $woo = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE %s AND meta_value LIKE %s",
                '%woo%',
                '%' . $attachment_id . '%'
            )
        );
        // Ook op veelvoorkomende Woo keys
        $gallery = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE (meta_key LIKE %s OR meta_key LIKE %s) AND meta_value LIKE %s",
                '%gallery%',
                '%product_image%',
                '%' . $attachment_id . '%'
            )
        );
        if ($woo > 0 || $gallery > 0) {
            $types[] = 'woocommerce';
        }

        return $types;
    }

    /**
     * Update media metadata via AJAX
     */
    public static function updateMediaMetaCallback(): void
    {
        $attachmentId   = self::sanitizeInt($_POST['attachment_id'] ?? null);
        $newTitle       = sanitize_text_field($_POST['title'] ?? '');
        $newCaption     = sanitize_text_field($_POST['caption'] ?? '');
        $newDescription = sanitize_textarea_field($_POST['description'] ?? '');
        $newAlt         = sanitize_text_field($_POST['alt'] ?? '');
        $newFilename    = sanitize_file_name($_POST['new_filename'] ?? '');

        if (!$attachmentId) {
            self::logError('Ongeldig bijlage-ID bij updateMediaMetaCallback');
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
        }

        $attachment = get_post($attachmentId);
        if (!$attachment) {
            self::logError("Bijlage niet gevonden bij ID {$attachmentId} in updateMediaMetaCallback");
            wp_send_json_error(['message' => __('Bijlage niet gevonden.', 'modern-image-manager')]);
        }

        // Verwijder bestandsextensie van nieuwe bestandsnaam
        $newFilename = self::removeFileExtension($newFilename);

        $result = wp_update_post([
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ], true);

        if (is_wp_error($result)) {
            self::logError("Fout bij bijwerken media ID {$attachmentId}: " . $result->get_error_message());
            wp_send_json_error(['message' => __('Fout bij bijwerken media: ', 'modern-image-manager') . $result->get_error_message()]);
        }

        // Update alt-tekst alleen indien gewijzigd en niet leeg
        $currentAlt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        if ($newAlt !== '' && $newAlt !== $currentAlt) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', $newAlt);
        }

        // Wijzig bestandsnaam indien opgegeven
        if ($newFilename !== '' && !self::updateFileName($attachmentId, $newFilename)) {
            self::logError("Fout bij hernoemen bestand voor media ID {$attachmentId}");
            wp_send_json_error(['message' => __('Fout bij hernoemen bestand.', 'modern-image-manager')]);
        }

        wp_send_json_success(['message' => __('Media informatie succesvol bijgewerkt.', 'modern-image-manager')]);
    }

    /**
     * Verwijder media via AJAX
     */
    public static function deleteMediaCallback(): void
    {
        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (!$attachmentId) {
            self::logError('Ongeldig bijlage-ID bij deleteMediaCallback');
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
        }

        $deleted = wp_delete_attachment($attachmentId, true);

        if ($deleted) {
            wp_send_json_success(['message' => __('Media succesvol verwijderd.', 'modern-image-manager')]);
        } else {
            self::logError("Fout bij verwijderen media ID {$attachmentId}");
            wp_send_json_error(['message' => __('Fout bij verwijderen media.', 'modern-image-manager')]);
        }
    }

    /**
     * Bulk verwijder media via AJAX
     */
    public static function bulkDeleteMediaCallback(): void
    {
        $ids = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids'])
            ? array_map('intval', $_POST['attachment_ids'])
            : [];

        if (empty($ids)) {
            self::logError('Geen media-ID’s ontvangen bij bulkDeleteMediaCallback');
            wp_send_json_error(['message' => __('Geen media-ID’s ontvangen.', 'modern-image-manager')]);
        }

        foreach ($ids as $id) {
            wp_delete_attachment($id, true);
        }

        wp_send_json_success(['message' => __('Geselecteerde media succesvol verwijderd.', 'modern-image-manager')]);
    }

    /**
     * Tel aantal rijen en totale bestandsgrootte voor export
     */
    public static function getExportRowCountCallback(): void
    {
        $option = sanitize_text_field($_POST['export_option'] ?? 'all');

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ($option === 'linked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'EXISTS'];
        } elseif ($option === 'unlinked') {
            $query_args['meta_query'][] = ['key' => '_wp_attachment_context', 'compare' => 'NOT EXISTS'];
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

        wp_send_json_success(['count' => count($ids), 'total_size' => $totalSize]);
    }

    /**
     * Preview export data (limiet 5 items)
     */
    public static function getExportPreviewDataCallback(): void
    {
        $columns = isset($_POST['columns']) && is_array($_POST['columns'])
            ? array_map('sanitize_text_field', $_POST['columns'])
            : [];

        $option = sanitize_text_field($_POST['export_option'] ?? 'all');

        if (empty($columns)) {
            self::logError('Geen kolommen geselecteerd bij getExportPreviewDataCallback');
            wp_send_json_error(['message' => __('Geen kolommen geselecteerd.', 'modern-image-manager')]);
        }

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
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
                    'filename'      => basename(get_attached_file($post->ID)),
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

    /**
     * Filter media items via AJAX
     * Ondersteunt paginatie (optie: page & per_page POST parameters)
     */
    public static function filterMediaCallback(): void
    {
        $search = sanitize_text_field($_POST['search'] ?? '');
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_POST['per_page'] ?? 20))); // Beperk tot 10-100 items per pagina

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $perPage,
            'paged'          => $page,
            'fields'         => 'ids',
        ];

        if ($search !== '') {
            $args['s'] = $search;
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => '_wp_attachment_image_alt', 'value' => $search, 'compare' => 'LIKE'],
            ];

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

        // Haal totaal aantal resultaten zonder paginatie voor paginatie info
        $totalQueryArgs = $args;
        $totalQueryArgs['posts_per_page'] = -1;
        unset($totalQueryArgs['paged']);
        $totalQuery = new WP_Query($totalQueryArgs);
        $totalFound = $totalQuery->found_posts;

        $rows  = [];
        foreach ($query->posts as $id) {
            $status   = get_post_meta($id, '_wp_attachment_context', true) ? 'linked' : 'unlinked';
            $filename = esc_html(pathinfo(get_attached_file($id), PATHINFO_FILENAME));
            $pageTitle= esc_html(modern_image_manager_get_attached_post_title($id));
            $title    = esc_attr(get_post($id)->post_title);
            $caption  = esc_attr(get_post($id)->post_excerpt);
            $desc     = esc_textarea(get_post($id)->post_content);
            $alt      = esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true));

            $tr  = "<tr class=\"media-items-tr-item unique-tr-{$id}\" data-status=\"{$status}\">";
            $tr .= "<td><input type=\"checkbox\" class=\"bulk-delete-checkbox\" data-attachment-id=\"{$id}\" /></td>";
            $tr .= "<td><input type=\"text\" class=\"filename-input media-items-filename-input\" data-attachment-id=\"{$id}\" value=\"{$filename}\" readonly></td>";
            $tr .= "<td><input type=\"text\" class=\"new-filename-input media-items-new-filename-input\" data-attachment-id=\"{$id}\" placeholder=\"" . esc_attr__('Voer nieuwe bestandsnaam in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td>{$pageTitle}</td>";
            $tr .= "<td><input type=\"text\" class=\"title-input media-items-title-input\" data-attachment-id=\"{$id}\" value=\"{$title}\" placeholder=\"" . esc_attr__('Voer titel in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><input type=\"text\" class=\"caption-input media-items-caption-input\" data-attachment-id=\"{$id}\" value=\"{$caption}\" placeholder=\"" . esc_attr__('Voer caption in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><textarea class=\"description-input media-items-description-input\" data-attachment-id=\"{$id}\" placeholder=\"" . esc_attr__('Voer description in', 'modern-image-manager') . "\">{$desc}</textarea></td>";
            $tr .= "<td><input type=\"text\" class=\"alt-input media-items-alt-input\" data-attachment-id=\"{$id}\" value=\"{$alt}\" placeholder=\"" . esc_attr__('Voer alt-tag in', 'modern-image-manager') . "\"></td>";
            $tr .= "<td><button class=\"save-meta page-button media-items-save-meta\" data-attachment-id=\"{$id}\">" . esc_html__('Opslaan', 'modern-image-manager') . "</button></td>";
            $tr .= "<td><button class=\"delete-media page-button media-items-delete-media\" data-attachment-id=\"{$id}\">" . esc_html__('Verwijderen', 'modern-image-manager') . "</button></td>";
            $tr .= '</tr>';

            $rows[] = $tr;
        }

        wp_send_json_success([
            'rows' => $rows,
            'total' => $totalFound,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Update bestandsnaam van bijlage
     */
    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        $file = get_attached_file($attachmentId);
        if (!$file || !file_exists($file)) {
            return false;
        }

        $dir     = pathinfo($file, PATHINFO_DIRNAME);
        $ext     = pathinfo($file, PATHINFO_EXTENSION);
        $newFile = "{$dir}/{$newFilename}.{$ext}";

        if ($file === $newFile) {
            return true;
        }

        if (file_exists($newFile) || !rename($file, $newFile)) {
            return false;
        }

        update_attached_file($attachmentId, $newFile);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachmentId, $newFile);
        if (!$meta) {
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

    /**
     * Verwijder bestandsextensie van tekst
     */
    private static function removeFileExtension(string $text): string
    {
        return preg_replace('/\.[^.\s]{2,5}$/', '', $text) ?? $text;
    }

    /**
     * Valideer integer waarde of retourneer null
     * (nu PHP 8.2: typehint 'mixed $value')
     */
    private static function sanitizeInt(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }

    /**
     * Log een foutmelding naar debug.log (alleen als WP_DEBUG aan staat)
     */
    private static function logError(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[ModernImageManager] ' . $message);
        }
    }
}

ModernImageManagerAjaxHandler::init();