<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

final class ModernImageManagerAjaxHandler
{
    public static function init(): void
    {
        add_action('wp_ajax_update_media_meta',    [self::class, 'updateMediaMetaCallback']);
        add_action('wp_ajax_delete_media',          [self::class, 'deleteMediaCallback']);
        add_action('wp_ajax_bulk_delete_media',     [self::class, 'bulkDeleteMediaCallback']);
    }

    public static function updateMediaMetaCallback(): void
    {
        error_log('DEBUG: updateMediaMetaCallback aangeroepen');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
        }

        $attachmentId   = self::sanitizeInt($_POST['attachment_id']   ?? null);
        $newTitle       = sanitize_text_field($_POST['title']         ?? '');
        $newCaption     = sanitize_text_field($_POST['caption']       ?? '');
        $newDescription = sanitize_textarea_field($_POST['description'] ?? '');
        $newAlt         = sanitize_text_field($_POST['alt']           ?? '');
        $newFilename    = sanitize_file_name($_POST['new_filename']   ?? '');

        if (! $attachmentId) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
        }

        $attachment = get_post($attachmentId);
        if (! $attachment) {
            error_log('DEBUG: Bijlage niet gevonden (raceconditie)');
            wp_send_json_error(['message' => __('Bijlage niet gevonden.', 'modern-image-manager')]);
        }

        // Strip file extension uit invoer
        $newTitle       = self::removeFileExtension($newTitle);
        $newCaption     = self::removeFileExtension($newCaption);
        $newDescription = self::removeFileExtension($newDescription);
        $newAlt         = self::removeFileExtension($newAlt);

        // Update post fields
        $result = wp_update_post([
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ], true);

        if (is_wp_error($result)) {
            error_log('DEBUG: wp_update_post error: ' . $result->get_error_message());
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

        error_log('DEBUG: updateMediaMetaCallback succesvol');
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
        if ($deleted) {
            wp_send_json_success(['message' => __('Media verwijderd.', 'modern-image-manager')]);
        } else {
            wp_send_json_error(['message' => __('Fout bij verwijderen media.', 'modern-image-manager')]);
        }
    }

    /**
     * Bulk delete handler: verwijder meerdere attachments tegelijk.
     */
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

    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        error_log("DEBUG: updateFileName voor ID {$attachmentId} -> {$newFilename}");

        $file = get_attached_file($attachmentId);
        if (! $file || ! file_exists($file)) {
            error_log("DEBUG: Bestand niet gevonden voor ID {$attachmentId}");
            return false;
        }

        $dir     = pathinfo($file, PATHINFO_DIRNAME);
        $ext     = pathinfo($file, PATHINFO_EXTENSION);
        $newFile = "{$dir}/{$newFilename}.{$ext}";

        if ($file === $newFile) {
            return true;
        }

        if (file_exists($newFile) || ! rename($file, $newFile)) {
            error_log("DEBUG: Kon niet hernoemen naar {$newFile}");
            return false;
        }

        update_attached_file($attachmentId, $newFile);

        // Regenerate metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachmentId, $newFile);
        if (! $meta) {
            return false;
        }
        wp_update_attachment_metadata($attachmentId, $meta);

        // Update post guid & name
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
        return filter_var($value, FILTER_VALIDATE_INT, ['options'=>['default'=>null]]);
    }
}

ModernImageManagerAjaxHandler::init();