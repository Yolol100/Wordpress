<?php
declare(strict_types=1);

namespace ModernImageManager\Services;

defined('ABSPATH') || exit;

final class ScanService
{
    private const SCAN_ROW_LIMIT = 5000;

    public function duplicates(): array
    {
        $all = $this->mergeUsageMaps(
            $this->imagesFromPostsPages(),
            $this->featuredImages(),
            $this->acfImages(),
            $this->elementorImages()
        );

        $duplicates = [];
        foreach ($all as $key => $usages) {
            if (! is_numeric($key)) {
                continue;
            }
            $id = (int) $key;
            $post = get_post($id);
            if (! $post || $post->post_type !== 'attachment' || ! str_starts_with((string) get_post_mime_type($id), 'image/') || ! wp_get_attachment_url($id)) {
                continue;
            }
            if (count((array) $usages) > 1) {
                $duplicates[$id] = (array) $usages;
            }
        }

        return $duplicates;
    }

    private function mergeUsageMaps(array ...$maps): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $attachmentId => $usages) {
                if (! is_numeric($attachmentId)) {
                    continue;
                }

                $id = (int) $attachmentId;
                foreach ((array) $usages as $usage) {
                    if (is_array($usage)) {
                        $merged[$id][] = $usage;
                    }
                }
            }
        }

        return $merged;
    }

    public function formattedDuplicates(): array
    {
        $items = [];
        foreach ($this->duplicates() as $id => $usages) {
            $url = wp_get_attachment_url((int) $id) ?: '';
            $posts = [];
            $sources = [];

            foreach ((array) $usages as $usage) {
                if (! empty($usage['post_id']) && is_numeric($usage['post_id'])) {
                    $postId = (int) $usage['post_id'];
                    $posts[$postId] = [
                        'title' => get_the_title($postId) ?: __('Onbekend', 'modern-image-manager'),
                        'editUrl' => get_edit_post_link($postId, 'raw') ?: '',
                    ];
                }
                if (! empty($usage['type'])) {
                    $sources[] = (string) $usage['type'];
                } elseif (! empty($usage['context'])) {
                    $sources[] = (string) $usage['context'];
                }
            }

            $uniqueSources = array_values(array_unique($sources));
            $items[] = [
                'id' => (int) $id,
                'filename' => $url ? basename((string) parse_url($url, PHP_URL_PATH)) : (string) $id,
                'thumb' => wp_get_attachment_image_url((int) $id, 'thumbnail') ?: $url,
                'posts' => array_values($posts),
                'sources' => $uniqueSources,
                'sourceLabels' => array_map([$this, 'sourceLabel'], $uniqueSources),
                'usageCount' => count((array) $usages),
            ];
        }

        return $items;
    }


    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'post_content' => __('Content', 'modern-image-manager'),
            'featured_image' => __('Uitgelichte afbeelding', 'modern-image-manager'),
            'acf_field' => __('ACF-veld', 'modern-image-manager'),
            'elementor_widget' => __('Elementor-widget', 'modern-image-manager'),
            'elementor_setting' => __('Elementor-instelling', 'modern-image-manager'),
            default => sanitize_text_field($source),
        };
    }

    private function imagesFromPostsPages(): array
    {
        global $wpdb;

        $postTypes = $this->publicPostTypes();
        if ($postTypes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postTypes), '%s'));
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_content
                 FROM {$wpdb->posts}
                 WHERE post_type IN ($placeholders)
                   AND post_status = %s
                   AND post_content LIKE %s
                 LIMIT %d",
                array_merge($postTypes, ['publish', '%<img%', self::SCAN_ROW_LIMIT])
            )
        );
        $images = [];

        foreach ($rows as $row) {
            $postId = (int) $row->ID;
            $content = (string) $row->post_content;
            if (preg_match_all('/<img[^>]+src=[\'\"]([^\'\"]+)[\'\"]/i', $content, $matches)) {
                foreach ($matches[1] as $src) {
                    $attachmentId = attachment_url_to_postid($src);
                    if ($attachmentId) {
                        $images[$attachmentId][] = ['post_id' => $postId, 'type' => 'post_content'];
                    }
                }
            }
        }

        return $images;
    }

    private function featuredImages(): array
    {
        global $wpdb;

        $postTypes = $this->publicPostTypes();
        if ($postTypes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postTypes), '%s'));
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_thumbnail_id'
                   AND p.post_type IN ($placeholders)
                   AND p.post_status NOT IN ('trash', 'auto-draft')
                 LIMIT %d",
                array_merge($postTypes, [self::SCAN_ROW_LIMIT])
            )
        );
        $images = [];

        foreach ($rows as $row) {
            $attachmentId = (int) $row->meta_value;
            if (str_starts_with((string) get_post_mime_type($attachmentId), 'image/')) {
                $images[$attachmentId][] = ['post_id' => (int) $row->post_id, 'type' => 'featured_image'];
            }
        }

        return $images;
    }

    private function acfImages(): array
    {
        global $wpdb;

        $postTypes = $this->publicPostTypes();
        if ($postTypes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postTypes), '%s'));
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_key, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_value REGEXP '^[0-9]+$'
                   AND pm.meta_key NOT IN ('_thumbnail_id')
                   AND p.post_type IN ($placeholders)
                   AND p.post_status NOT IN ('trash', 'auto-draft')
                 LIMIT %d",
                array_merge($postTypes, [self::SCAN_ROW_LIMIT])
            )
        );
        $images = [];

        foreach ($rows as $row) {
            $attachmentId = (int) $row->meta_value;
            if (str_starts_with((string) get_post_mime_type($attachmentId), 'image/')) {
                $images[$attachmentId][] = ['post_id' => (int) $row->post_id, 'type' => 'acf_field', 'acf_field' => (string) $row->meta_key];
            }
        }

        return $images;
    }

    private function elementorImages(): array
    {
        global $wpdb;

        $postTypes = $this->publicPostTypes();
        if ($postTypes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postTypes), '%s'));
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_elementor_data'
                   AND pm.meta_value != ''
                   AND p.post_type IN ($placeholders)
                   AND p.post_status NOT IN ('trash', 'auto-draft')
                 LIMIT %d",
                array_merge($postTypes, [self::SCAN_ROW_LIMIT])
            )
        );
        $images = [];

        foreach ($rows as $row) {
            $data = json_decode((string) $row->meta_value, true);
            if (is_array($data)) {
                $this->extractElementorImages($data, $images, (int) $row->post_id);
            }
        }

        return $images;
    }

    private function extractElementorImages(array $data, array &$images, int $postId): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['id']) && is_numeric($value['id']) && str_starts_with((string) get_post_mime_type((int) $value['id']), 'image/')) {
                    $images[(int) $value['id']][] = ['post_id' => $postId, 'type' => 'elementor_widget'];
                }
                $this->extractElementorImages($value, $images, $postId);
            } elseif ((is_string($key) && str_contains(strtolower($key), 'image')) && is_numeric($value) && str_starts_with((string) get_post_mime_type((int) $value), 'image/')) {
                $images[(int) $value][] = ['post_id' => $postId, 'type' => 'elementor_setting'];
            }
        }
    }

    private function publicPostTypes(): array
    {
        return array_values(array_filter(get_post_types(['public' => true], 'names')));
    }
}
