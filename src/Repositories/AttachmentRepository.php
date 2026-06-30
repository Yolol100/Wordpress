<?php
declare(strict_types=1);

namespace ModernImageManager\Repositories;

use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

final class AttachmentRepository
{
    public function queryImageIds(array $queryArgs): WP_Query
    {
        return new WP_Query(array_merge([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'fields' => 'ids',
        ], $queryArgs));
    }

    public function find(int $attachmentId): ?WP_Post
    {
        $post = get_post($attachmentId);
        return $post instanceof WP_Post && $post->post_type === 'attachment' ? $post : null;
    }

    public function attachedFile(int $attachmentId): string
    {
        return (string) get_attached_file($attachmentId);
    }

    public function url(int $attachmentId): string
    {
        return wp_get_attachment_url($attachmentId) ?: '';
    }

    public function thumbnail(int $attachmentId): string
    {
        return wp_get_attachment_image_url($attachmentId, 'thumbnail') ?: '';
    }

    public function altText(int $attachmentId): string
    {
        return (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
    }

    public function context(int $attachmentId): string
    {
        return (string) get_post_meta($attachmentId, '_wp_attachment_context', true);
    }

    public function hasAttachmentContext(int $attachmentId): bool
    {
        $post = $this->find($attachmentId);
        if (! $post) {
            return false;
        }

        if ((int) $post->post_parent > 0) {
            return true;
        }

        return $this->context($attachmentId) !== '';
    }
}
