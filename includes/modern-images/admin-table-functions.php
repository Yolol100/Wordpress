<?php

// File: helpers.php (of functions.php)

// Helperfuncties voor Modern Image Manager

declare(strict_types=1);

defined('ABSPATH') || exit;

/**  
 * Haal gekoppelde posttitel op  
 */
function modern_image_manager_get_attached_post_title(int $attachment_id): string
{
    global $wpdb;

    $attachment = get_post($attachment_id);
    if (!$attachment) {
        return __('Niet gekoppeld', 'modern-image-manager');
    }

    if ($attachment->post_parent > 0) {
        $parent = get_post($attachment->post_parent);
        return $parent?->post_title ?? __('Niet gekoppeld', 'modern-image-manager');
    }

    // Zoek via ACF relatie
    $acf_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %d AND meta_key LIKE 'field_%%' LIMIT 1",
            $attachment_id
        )
    );

    if ($acf_data?->post_id) {
        $parent = get_post((int) $acf_data->post_id);
        return $parent?->post_title ?? __('Niet gekoppeld', 'modern-image-manager');
    }

    return __('Niet gekoppeld', 'modern-image-manager');
}

/**  
 * Haal alle media items op  
 */
function modern_image_manager_get_media_items(): WP_Query
{
    return new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    ]);
}