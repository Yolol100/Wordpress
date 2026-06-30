<?php
declare(strict_types=1);

namespace ModernImageManager\Services;

use ModernImageManager\Domain\MediaItem;
use ModernImageManager\Repositories\AttachmentRepository;
use ModernImageManager\Support\AttachmentHelper;
use ModernImageManager\Support\FileSecurity;
use WP_Error;

defined('ABSPATH') || exit;

final class MediaService
{
    public function __construct(
        private ?AttachmentHelper $helper = null,
        private ?AttachmentRepository $attachments = null,
        private ?FileSecurity $files = null
    ) {
        $this->helper = $helper ?: new AttachmentHelper();
        $this->attachments = $attachments ?: new AttachmentRepository();
        $this->files = $files ?: new FileSecurity();
    }

    public function query(array $args): array
    {
        $filter = sanitize_key((string) ($args['filter'] ?? 'all'));
        if (! in_array($filter, ['all', 'linked', 'unlinked'], true)) {
            $filter = 'all';
        }

        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($args['per_page'] ?? 30)));
        $search = sanitize_text_field((string) ($args['search'] ?? ''));

        $queryArgs = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $filter === 'all' ? $perPage : -1,
            'paged' => $filter === 'all' ? $page : 1,
            'fields' => 'ids',
            'no_found_rows' => $filter !== 'all',
        ];

        if ($search !== '') {
            $queryArgs['s'] = $search;
        }

        $query = $this->attachments->queryImageIds($queryArgs);
        $ids = array_map('intval', (array) $query->posts);

        if ($filter !== 'all') {
            $ids = array_values(array_filter($ids, function (int $id) use ($filter): bool {
                $isLinked = $this->attachments->hasAttachmentContext($id);
                return $filter === 'linked' ? $isLinked : ! $isLinked;
            }));
            $total = count($ids);
            $pagedIds = array_slice($ids, ($page - 1) * $perPage, $perPage);
            $pages = max(1, (int) ceil($total / $perPage));
        } else {
            $total = (int) $query->found_posts;
            $pagedIds = $ids;
            $pages = max(1, (int) $query->max_num_pages);
        }

        $items = [];
        foreach ($pagedIds as $id) {
            $post = $this->attachments->find($id);
            if (! $post) {
                continue;
            }

            $file = $this->attachments->attachedFile($id);
            $isLinked = $this->attachments->hasAttachmentContext($id);
            $items[] = (new MediaItem(
                $id,
                pathinfo($file, PATHINFO_FILENAME),
                (string) $post->post_name,
                $this->helper->getAttachedPostTitle($id),
                $post->post_title,
                $post->post_excerpt,
                $post->post_content,
                $this->attachments->altText($id),
                $this->attachments->thumbnail($id),
                $this->attachments->url($id),
                $isLinked ? 'linked' : 'unlinked'
            ))->toArray();
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => min($page, $pages),
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }

    public function update(int $id, array $params): true|WP_Error
    {
        $post = $this->attachments->find($id);
        if (! $post || ! str_starts_with((string) get_post_mime_type($id), 'image/')) {
            return new WP_Error('mim_not_found', __('Afbeelding niet gevonden.', 'modern-image-manager'), ['status' => 404]);
        }

        $newFilename = sanitize_file_name((string) ($params['new_filename'] ?? ''));
        $newFilename = preg_replace('/\.[^.\s]{2,5}$/', '', $newFilename) ?: $newFilename;
        if ($newFilename !== '' && ! $this->renameAttachmentFile($id, $newFilename)) {
            return new WP_Error('mim_rename_failed', __('Fout bij hernoemen bestand. Metadata is niet aangepast.', 'modern-image-manager'), ['status' => 400]);
        }

        $update = ['ID' => $id];
        if (array_key_exists('title', $params)) {
            $update['post_title'] = sanitize_text_field((string) $params['title']);
        }
        if (array_key_exists('caption', $params)) {
            $update['post_excerpt'] = sanitize_text_field((string) $params['caption']);
        }
        if (array_key_exists('description', $params)) {
            $update['post_content'] = sanitize_textarea_field((string) $params['description']);
        }
        if (array_key_exists('slug', $params)) {
            $slug = sanitize_title((string) $params['slug']);
            if ($slug !== '') {
                $update['post_name'] = $slug;
            }
        }

        if (count($update) > 1) {
            $result = wp_update_post($update, true);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        if (array_key_exists('alt', $params)) {
            update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field((string) $params['alt']));
        }

        return true;
    }

    public function delete(int $id): bool
    {
        $post = $this->attachments->find($id);
        if (! $post || ! str_starts_with((string) get_post_mime_type($id), 'image/')) {
            return false;
        }

        return (bool) wp_delete_attachment($id, false);
    }

    public function bulkDelete(array $ids): int
    {
        $deleted = 0;
        foreach (array_unique(array_filter(array_map('intval', $ids))) as $id) {
            if ($this->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function renameAttachmentFile(int $attachmentId, string $newFilename): bool
    {
        $file = $this->attachments->attachedFile($attachmentId);
        if (! $file || ! $this->files->isAllowedImageFile($file)) {
            return false;
        }

        $dir = pathinfo($file, PATHINFO_DIRNAME);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $currentBase = pathinfo($file, PATHINFO_FILENAME);
        $newFilename = sanitize_file_name(pathinfo($newFilename, PATHINFO_FILENAME));
        if ($newFilename === '' || str_contains($newFilename, '..') || $ext === '') {
            return false;
        }
        if ($newFilename === $currentBase) {
            return true;
        }

        $newFile = trailingslashit($dir) . wp_unique_filename($dir, $newFilename . '.' . $ext);
        if (! $this->files->isPathInsideUploads($newFile, true)) {
            return false;
        }
        if ($file === $newFile) {
            return true;
        }
        if (! rename($file, $newFile)) {
            return false;
        }

        update_attached_file($attachmentId, $newFile);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $meta = wp_generate_attachment_metadata($attachmentId, $newFile);
        if ($meta) {
            wp_update_attachment_metadata($attachmentId, $meta);
        }
        return true;
    }
}
