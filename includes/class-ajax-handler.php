<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Class ModernImageManagerAjaxHandler
 *
 * Verwerkt AJAX-verzoeken voor het bijwerken van media meta.
 */
class ModernImageManagerAjaxHandler
{
    /**
     * Initialiseert de AJAX handler.
     */
    public static function init(): void
    {
        add_action('wp_ajax_update_media_meta', [self::class, 'updateMediaMetaCallback']);
        // Nieuwe actie voor verwijderen toevoegen
        add_action('wp_ajax_delete_media', [self::class, 'deleteMediaCallback']);
    }

    /**
     * Callback voor het bijwerken van de media meta.
     */
    public static function updateMediaMetaCallback(): void
    {
        error_log('updateMediaMetaCallback aangeroepen'); // Debug log

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Geen toestemming.']);
            return;
        }

        $attachmentId   = self::sanitizeInt($_POST['attachment_id'] ?? null);
        $newTitle       = sanitize_text_field($_POST['title'] ?? '');
        $newCaption     = sanitize_text_field($_POST['caption'] ?? '');
        $newDescription = sanitize_textarea_field($_POST['description'] ?? '');
        $newAlt         = sanitize_text_field($_POST['alt'] ?? '');
        $newFilename    = sanitize_file_name($_POST['new_filename'] ?? '');

        if (empty($attachmentId)) {
            wp_send_json_error(['message' => 'Ongeldig bijlage-ID.']);
            return;
        }

        $attachmentPost = [
            'ID'           => $attachmentId,
            'post_title'   => $newTitle,
            'post_excerpt' => $newCaption,
            'post_content' => $newDescription,
        ];

        $updateResult = wp_update_post($attachmentPost, true);

        if (is_wp_error($updateResult)) {
            error_log('Fout bij wp_update_post: ' . $updateResult->get_error_message());
            wp_send_json_error([
                'message' => 'Fout bij het bijwerken van de media gegevens: ' . $updateResult->get_error_message(),
            ]);
            return;
        }

        if (!empty($newAlt)) {
            $altResult = update_post_meta($attachmentId, '_wp_attachment_image_alt', $newAlt);
            if ($altResult === false) {
                error_log('Fout bij update_post_meta (alt tekst)');
                wp_send_json_error(['message' => 'Fout bij het bijwerken van de alt tekst.']);
                return;
            }
        }

        if (!empty($newFilename)) {
            if (!self::updateFileName($attachmentId, $newFilename)) {
                // updateFileName handelt de foutmelding al af
                return;
            }
        }

        wp_send_json_success(['message' => 'Media info succesvol bijgewerkt.']);
        error_log('updateMediaMetaCallback succesvol afgerond');
    }

    /**
     * Callback voor het verwijderen van een media-item.
     *
     * Verwijdert de bijlage uit de database en de server.
     *
     * @return void
     */
    public static function deleteMediaCallback(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Geen toestemming.']);
            return;
        }

        $attachmentId = self::sanitizeInt($_POST['attachment_id'] ?? null);
        if (empty($attachmentId)) {
            wp_send_json_error(['message' => 'Ongeldig bijlage-ID.']);
            return;
        }

        // Probeer de bijlage definitief te verwijderen (verwijdert ook het bestand van de server)
        $deleted = wp_delete_attachment($attachmentId, true);
        if ($deleted) {
            wp_send_json_success(['message' => 'Media succesvol verwijderd.']);
        } else {
            wp_send_json_error(['message' => 'Fout bij het verwijderen van de media.']);
        }
    }

    /**
     * Hernoemt het bestand van de bijlage.
     *
     * @param int    $attachmentId
     * @param string $newFilename
     *
     * @return bool
     */
    private static function updateFileName(int $attachmentId, string $newFilename): bool
    {
        error_log(
            sprintf(
                'updateFileName aangeroepen met attachmentId: %d en newFilename: %s',
                $attachmentId,
                $newFilename
            )
        );

        $file = get_attached_file($attachmentId);

        if (!$file) {
            error_log('Bestand niet gevonden voor bijlage ID: ' . $attachmentId);
            wp_send_json_error(['message' => 'Bestand niet gevonden voor bijlage ID.']);
            return false;
        }

        $dir     = pathinfo($file, PATHINFO_DIRNAME);
        $ext     = pathinfo($file, PATHINFO_EXTENSION);
        $newFile = $dir . '/' . $newFilename . '.' . $ext;

        if (!file_exists($file)) {
            error_log('Het originele bestand is niet gevonden: ' . $file);
            wp_send_json_error(['message' => 'Het originele bestand is niet gevonden.']);
            return false;
        }

        if (file_exists($newFile)) {
            error_log('De nieuwe bestandsnaam bestaat al: ' . $newFile);
            wp_send_json_error(['message' => 'De nieuwe bestandsnaam bestaat al.']);
            return false;
        }

        if (!rename($file, $newFile)) {
            $errorMessage = error_get_last()['message'] ?? 'Onbekende fout';
            error_log(
                sprintf(
                    'Fout bij rename: %s (Oude bestand: %s, Nieuw bestand: %s)',
                    $errorMessage,
                    $file,
                    $newFile
                )
            );
            wp_send_json_error(['message' => 'Fout bij het hernoemen van het bestand.']);
            return false;
        }

        update_attached_file($attachmentId, $newFile);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachmentId, $newFile);

        if (!wp_update_attachment_metadata($attachmentId, $metadata)) {
            error_log('Fout bij wp_update_attachment_metadata.');
            wp_send_json_error(['message' => 'Fout bij het bijwerken van de metadata.']);
            return false;
        }

        error_log('updateFileName succesvol afgerond');
        return true;
    }

    /**
     * Sanitizeert een waarde als integer.
     *
     * @param mixed $value
     *
     * @return int|null
     */
    private static function sanitizeInt($value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }
}

ModernImageManagerAjaxHandler::init();