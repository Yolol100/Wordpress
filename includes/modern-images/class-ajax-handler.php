<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Class ModernImageManagerAjaxHandler
 *
 * Verwerkt AJAX-verzoeken voor het bijwerken van media meta,
 * en het verwerken van bulk-delete via admin-post.
 * Debug-meldingen worden gelogd naar wp-content/debug.log.
 */
class ModernImageManagerAjaxHandler
{
    /**
     * Initialiseert de AJAX handler en bulk-delete handler.
     */
    public static function init(): void
    {
        // AJAX callbacks
        add_action('wp_ajax_update_media_meta', [self::class, 'updateMediaMetaCallback']);
        add_action('wp_ajax_delete_media',      [self::class, 'deleteMediaCallback']);

        // Bulk-delete via form submit (admin-post)
        add_action('admin_post_modern_image_manager_bulk_delete', [self::class, 'bulkDeleteCallback']);
    }

    /**
     * Callback voor het bijwerken van de media meta.
     */
    public static function updateMediaMetaCallback(): void
    {
        error_log('DEBUG: updateMediaMetaCallback aangeroepen');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
            return;
        }

        $attachmentId   = self::sanitizeInt($_POST['attachment_id'] ?? null);
        $newTitle       = sanitize_text_field($_POST['title'] ?? '');
        $newCaption     = sanitize_text_field($_POST['caption'] ?? '');
        $newDescription = sanitize_textarea_field($_POST['description'] ?? '');
        $newAlt         = sanitize_text_field($_POST['alt'] ?? '');
        $newFilename    = sanitize_file_name($_POST['new_filename'] ?? '');

        if (empty($attachmentId)) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
            return;
        }

        $attachment = get_post($attachmentId);
        if (!$attachment) {
            error_log('DEBUG: Bijlage niet gevonden of nog niet volledig opgeslagen (raceconditie)');
            wp_send_json_error(['message' => __('Bijlage niet gevonden of nog niet volledig opgeslagen.', 'modern-image-manager')]);
            return;
        }

        // Verwijder bestandsextensie uit inputvelden
        $newTitle       = self::removeFileExtension($newTitle);
        $newCaption     = self::removeFileExtension($newCaption);
        $newDescription = self::removeFileExtension($newDescription);
        $newAlt         = self::removeFileExtension($newAlt);

        // Update post data
        $attachmentPost = [
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ];

        $updateResult = wp_update_post($attachmentPost, true);
        if (is_wp_error($updateResult)) {
            error_log('DEBUG: Fout bij wp_update_post: ' . $updateResult->get_error_message());
            wp_send_json_error(['message' => __('Fout bij het bijwerken van de media gegevens: ', 'modern-image-manager') . $updateResult->get_error_message()]);
            return;
        }

        if (!empty($newAlt)) {
            error_log('DEBUG: Nieuwe alt-tekst: ' . $newAlt);
            update_post_meta($attachmentId, '_wp_attachment_image_alt', $newAlt);
        }

        if (!empty($newFilename)) {
            if (!self::updateFileName($attachmentId, $newFilename)) {
                wp_send_json_error(['message' => __('Fout bij het hernoemen van het bestand.', 'modern-image-manager')]);
                return;
            }
        }

        wp_send_json_success(['message' => __('Media info succesvol bijgewerkt.', 'modern-image-manager')]);
        error_log('DEBUG: updateMediaMetaCallback succesvol afgerond');
    }

    /**
     * Callback voor het verwijderen van een enkel media-item via AJAX.
     */
    public static function deleteMediaCallback(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen toestemming.', 'modern-image-manager')]);
            return;
        }

        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (empty($attachmentId)) {
            wp_send_json_error(['message' => __('Ongeldig bijlage-ID.', 'modern-image-manager')]);
            return;
        }

        $deleted = wp_delete_attachment($attachmentId, true);
        if ($deleted) {
            wp_send_json_success(['message' => __('Media succesvol verwijderd.', 'modern-image-manager')]);
        } else {
            wp_send_json_error(['message' => __('Fout bij het verwijderen van de media.', 'modern-image-manager')]);
        }
    }

    /**
     * Callback voor bulk-delete van media-items via admin-post.
     */
    public static function bulkDeleteCallback(): void
    {
        // Controleer permissies en nonce
        if (! current_user_can('manage_options')
            || ! isset($_POST['modern_image_manager_bulk_delete_nonce'])
            || ! check_admin_referer('modern_image_manager_bulk_delete', 'modern_image_manager_bulk_delete_nonce')
        ) {
            wp_die(__('Niet toegestaan.', 'modern-image-manager'));
        }

        $toDelete = array_map('intval', $_POST['delete_ids'] ?? []);
        $count    = 0;

        foreach ($toDelete as $aid) {
            if (wp_delete_attachment($aid, true)) {
                $count++;
            }
        }

        // Redirect terug met een query-parameter voor succesmelding
        $redirect = wp_get_referer() ?: admin_url();
        $redirect = add_query_arg('mim_bulk_delete_success', $count, $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Verwijdert de bestandsextensie (zoals .png) van de bestandsnaam.
     */
    private static function removeFileExtension(string $string): string
    {
        return preg_replace('/\.[^.\s]{3,4}$/', '', $string);
    }

    /**
     * Hernoemt het bestand van de bijlage en werkt metadata, guid en post_name bij.
     */
    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        error_log(sprintf('DEBUG: updateFileName aangeroepen met attachmentId: %d en newFilename: %s', $attachmentId, $newFilename));

        $file = get_attached_file($attachmentId);
        if (!$file || ! file_exists($file)) {
            error_log('DEBUG: Bestand niet gevonden voor bijlage ID: ' . $attachmentId);
            return false;
        }

        $dir     = pathinfo($file, PATHINFO_DIRNAME);
        $ext     = pathinfo($file, PATHINFO_EXTENSION);
        $newFile = "{$dir}/{$newFilename}.{$ext}";

        if ($file === $newFile) {
            error_log('DEBUG: Bestandsnaam is al correct, geen wijziging nodig');
            return true;
        }

        if (file_exists($newFile)) {
            error_log('DEBUG: De nieuwe bestandsnaam bestaat al: ' . $newFile);
            return false;
        }

        if (! rename($file, $newFile)) {
            $err = error_get_last()['message'] ?? 'Onbekende fout';
            error_log("DEBUG: Fout bij rename: {$err}");
            return false;
        }

        update_attached_file($attachmentId, $newFile);
        sleep(1); // korte vertraging tegen racecondities

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachmentId, $newFile);
        if (! $metadata) {
            error_log('DEBUG: Metadata niet correct gegenereerd voor attachment ID ' . $attachmentId);
            return false;
        }

        delete_post_meta($attachmentId, '_wp_attachment_metadata');
        $metaResult = wp_update_attachment_metadata($attachmentId, $metadata);
        if (is_wp_error($metaResult) || ! $metaResult) {
            error_log('DEBUG: Fout bij wp_update_attachment_metadata');
            return false;
        }

        $uploadDir = wp_upload_dir();
        $newUrl    = trailingslashit($uploadDir['baseurl']) . basename($newFile);
        wp_update_post([
            'ID'        => $attachmentId,
            'guid'      => $newUrl,
            'post_name' => sanitize_title($newFilename),
        ]);

        error_log('DEBUG: updateFileName succesvol afgerond voor ' . $newFile);
        return true;
    }

    /**
     * Sanitizeert een waarde als integer.
     */
    private static function sanitizeInt($value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }
}

ModernImageManagerAjaxHandler::init();