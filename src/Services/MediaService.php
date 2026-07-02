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

        if ($filter !== 'all') {
            [$total, $pagedIds] = $this->filteredIds($filter, $search, $page, $perPage);
            $pages = max(1, (int) ceil($total / $perPage));
        } else {
            $queryArgs = [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => $perPage,
                'paged' => $page,
                'fields' => 'ids',
            ];

            if ($search !== '') {
                $queryArgs['s'] = $search;
            }

            $query = $this->attachments->queryImageIds($queryArgs);
            $pagedIds = array_map('intval', (array) $query->posts);
            $total = (int) $query->found_posts;
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
                strtolower(pathinfo($file, PATHINFO_EXTENSION)),
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


    private function filteredIds(string $filter, string $search, int $page, int $perPage): array
    {
        $batchSize = 250;
        $queryPage = 1;
        $total = 0;
        $pagedIds = [];
        $offsetStart = ($page - 1) * $perPage;

        do {
            $queryArgs = [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => $batchSize,
                'paged' => $queryPage,
                'fields' => 'ids',
                'no_found_rows' => true,
            ];

            if ($search !== '') {
                $queryArgs['s'] = $search;
            }

            $query = $this->attachments->queryImageIds($queryArgs);
            $ids = array_map('intval', (array) $query->posts);

            foreach ($ids as $id) {
                $isLinked = $this->attachments->hasAttachmentContext($id);
                if (($filter === 'linked' && ! $isLinked) || ($filter === 'unlinked' && $isLinked)) {
                    continue;
                }

                if ($total >= $offsetStart && count($pagedIds) < $perPage) {
                    $pagedIds[] = $id;
                }

                $total++;
            }

            $queryPage++;
        } while (count($ids) === $batchSize);

        return [$total, $pagedIds];
    }

    public function update(int $id, array $params): bool|WP_Error
    {
        $post = $this->attachments->find($id);
        if (! $post || ! str_starts_with((string) get_post_mime_type($id), 'image/')) {
            return new WP_Error('mim_not_found', __('Afbeelding niet gevonden.', 'modern-image-manager'), ['status' => 404]);
        }

        $newFilename = $this->normalizeFilenameBase((string) ($params['new_filename'] ?? ''));
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


    private function normalizeFilenameBase(string $filename): string
    {
        $filename = sanitize_file_name(basename(trim($filename)));
        if ($filename === '') {
            return '';
        }

        return sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME));
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
        $newFilename = $this->normalizeFilenameBase($newFilename);
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
