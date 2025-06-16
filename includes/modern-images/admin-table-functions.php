<?php

// File: includes/modern-images/helpers.php (of functions.php)

// Helperfuncties voor Modern Image Manager

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
    // Haal alle attachments op, zonder limiet
    $query = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    ]);

    // Logging voor debugging (optioneel, uitzetten in productie)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("ModernImageManager: media items opgehaald, count: " . count($query->posts));
    }

    return $query;
}