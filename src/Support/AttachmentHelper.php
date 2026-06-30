<?php
declare(strict_types=1);

namespace ModernImageManager\Support;

defined('ABSPATH') || exit;

final class AttachmentHelper
{
    public function getAttachedPostTitle(int $attachmentId): string
    {
        global $wpdb;

        $attachment = get_post($attachmentId);
        if (! $attachment) {
            return __('Niet gekoppeld', 'modern-image-manager');
        }

        if ((int) $attachment->post_parent > 0) {
            $parent = get_post((int) $attachment->post_parent);
            if ($parent) {
                return (string) $parent->post_title;
            }
        }

        $acfData = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %d AND meta_key LIKE 'field_%%' LIMIT 1",
                $attachmentId
            )
        );

        if ($acfData && isset($acfData->post_id)) {
            $parent = get_post((int) $acfData->post_id);
            if ($parent) {
                return (string) $parent->post_title;
            }
        }

        return __('Niet gekoppeld', 'modern-image-manager');
    }
}
