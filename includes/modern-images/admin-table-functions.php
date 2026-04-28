<?php

// File: includes/modern-images/helpers.php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Haal gekoppelde posttitel op voor een bijlage.
 *
 * Zoekt eerst de post_parent, daarna via ACF-relatie,
 * geeft "Niet gekoppeld" terug als er geen koppeling is.
 *
 * @param int $attachment_id ID van de bijlage
 * @return string Titel van gekoppelde post of 'Niet gekoppeld'
 */
function modern_image_manager_get_attached_post_title(int $attachment_id): string
{
    global $wpdb;

    $attachment = get_post($attachment_id);
    if (!$attachment) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ModernImageManager: Bijlage met ID {$attachment_id} niet gevonden.");
        }
        return __('Niet gekoppeld', 'modern-image-manager');
    }

    // Check post_parent relatie
    if ($attachment->post_parent > 0) {
        $parent = get_post($attachment->post_parent);
        if ($parent) {
            return $parent->post_title;
        }
    }

    // Zoek via ACF relatie in postmeta (meta_key begint met 'field_')
    $acf_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %d AND meta_key LIKE 'field_%%' LIMIT 1",
            $attachment_id
        )
    );

    if ($acf_data && isset($acf_data->post_id)) {
        $parent = get_post((int) $acf_data->post_id);
        if ($parent) {
            return $parent->post_title;
        }
    }

    // Geen koppeling gevonden
    return __('Niet gekoppeld', 'modern-image-manager');
}

/**
 * Haal alle media items op.
 *
 * @return WP_Query Query object met alle media items
 */
function modern_image_manager_get_media_items(): WP_Query
{
    // Haal alle attachments op, met limiet voor performance
    $query = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1000, // Limiet toegevoegd voor performance
    ]);

    // Logging voor debugging (optioneel, uitzetten in productie)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("ModernImageManager: media items opgehaald, count: " . count($query->posts));
    }

    return $query;
}

/**
 * Haal alle afbeelding-ID's uit postmeta die verwijzen naar Elementor, ACF en WooCommerce.
 * Inclusief alle attachment_id's uit bekende velden en JSON-structuren.
 *
 * @return int[] Array met unieke attachment ID's
 */
function modern_image_manager_get_all_referenced_attachment_ids(): array
{
    global $wpdb;

    $ids = [];

    // Zoek alle mogelijke afbeelding-ID's in postmeta (Elementor, ACF, WooCommerce)
    $meta_keys = [
        // Veelvoorkomende meta_keys
        '%elementor%',          // Elementor JSON velden
        '%_thumbnail_id%',      // Uitgelichte afbeelding (ook WooCommerce producten)
        '%image%',              // Custom fields (ACF, Woo)
        '%gallery%',            // Galerijen
        '%product_image%',      // WooCommerce
        '%product_gallery%'     // WooCommerce
    ];

    foreach ($meta_keys as $key_pattern) {
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key LIKE %s",
                $key_pattern
            )
        );

        foreach ($results as $row) {
            $value = maybe_unserialize($row->meta_value);

            // Direct ID
            if (is_numeric($value)) {
                $ids[] = (int)$value;
            }
            // Array van ID's
            elseif (is_array($value)) {
                foreach ($value as $v) {
                    if (is_numeric($v)) {
                        $ids[] = (int)$v;
                    }
                }
            }
            // JSON (Elementor, complexe velden)
            elseif (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                $maybe_json = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $ids = array_merge($ids, modern_image_manager_extract_attachment_ids_from_json($maybe_json));
                }
            }
        }
    }

    // Uniek maken en alleen positieve ints
    $ids = array_unique(array_filter($ids, function ($id) {
        return is_int($id) && $id > 0;
    }));

    return $ids;
}

/**
 * Recursieve hulp voor attachment-IDs uit JSON (voor Elementor/ACF).
 *
 * @param mixed $data
 * @return int[]
 */
function modern_image_manager_extract_attachment_ids_from_json(mixed $data): array
{
    $ids = [];
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (
                (is_string($key) && (stripos($key, 'image') !== false || stripos($key, 'gallery') !== false)) ||
                (is_string($key) && stripos($key, 'attachment') !== false)
            ) {
                if (is_numeric($value)) {
                    $ids[] = (int)$value;
                }
                elseif (is_array($value)) {
                    foreach ($value as $v) {
                        if (is_numeric($v)) {
                            $ids[] = (int)$v;
                        }
                    }
                }
            }
            // Recursief verder zoeken
            if (is_array($value) || is_object($value)) {
                $ids = array_merge($ids, modern_image_manager_extract_attachment_ids_from_json($value));
            }
        }
    }
    return $ids;
}