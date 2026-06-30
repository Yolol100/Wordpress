<?php
declare(strict_types=1);

namespace ModernImageManager\Api;

use ModernImageManager\Services\MediaService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class MediaController
{
    private string $namespace = 'modern-image-manager/v1';

    public function __construct(private ?MediaService $media = null)
    {
        $this->media = $media ?: new MediaService();
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route($this->namespace, '/media', [[
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'getMedia'],
            'permission_callback' => static fn(): bool => current_user_can('upload_files'),
            'args' => [
                'search' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => '',
                ],
                'filter' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static fn($value): bool => in_array($value, ['all', 'linked', 'unlinked'], true),
                    'default' => 'all',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn($value): bool => (int) $value >= 1,
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 10,
                    'maximum' => 100,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn($value): bool => (int) $value >= 10 && (int) $value <= 100,
                ],
            ],
        ]]);

        register_rest_route($this->namespace, '/media/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'updateMedia'],
                'permission_callback' => [$this, 'canEditMedia'],
                'args' => array_merge($this->idArg(), $this->updateArgs()),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'deleteMedia'],
                'permission_callback' => [$this, 'canDeleteMedia'],
                'args' => $this->idArg(),
            ],
        ]);

        register_rest_route($this->namespace, '/media/bulk-delete', [[
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'bulkDeleteMedia'],
            'permission_callback' => [$this, 'canBulkDeleteMedia'],
            'args' => [
                'ids' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                        'minimum' => 1,
                    ],
                    'required' => true,
                    'sanitize_callback' => static fn($value): array => is_array($value) ? array_values(array_unique(array_filter(array_map('absint', $value)))) : [],
                    'validate_callback' => static fn($value): bool => is_array($value) && count($value) > 0 && count($value) <= 100,
                ],
            ],
        ]]);
    }


    private function idArg(): array
    {
        return [
            'id' => [
                'type' => 'integer',
                'required' => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => static fn($value): bool => (int) $value > 0,
            ],
        ];
    }

    private function updateArgs(): array
    {
        $textArg = [
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ];

        return [
            'title' => $textArg,
            'caption' => $textArg,
            'description' => [
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'alt' => $textArg,
            'new_filename' => [
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_file_name',
            ],
            'slug' => [
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_title',
            ],
        ];
    }

    public function canEditMedia(WP_REST_Request $request): bool
    {
        $id = (int) $request['id'];

        return $this->isImageAttachment($id) && current_user_can('edit_post', $id);
    }

    public function canDeleteMedia(WP_REST_Request $request): bool
    {
        $id = (int) $request['id'];

        return $this->isImageAttachment($id) && current_user_can('delete_post', $id);
    }

    public function canBulkDeleteMedia(WP_REST_Request $request): bool
    {
        if (! current_user_can('upload_files')) {
            return false;
        }

        $ids = $this->normalizeIds($request->get_param('ids'));
        if ($ids === [] || count($ids) > 100) {
            return false;
        }

        foreach ($ids as $id) {
            if (! $this->isImageAttachment($id) || ! current_user_can('delete_post', $id)) {
                return false;
            }
        }

        return true;
    }

    private function isImageAttachment(int $id): bool
    {
        $post = get_post($id);
        if (! $post || $post->post_type !== 'attachment') {
            return false;
        }

        return str_starts_with((string) get_post_mime_type($id), 'image/');
    }

    public function getMedia(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->media->query([
            'search' => $request->get_param('search'),
            'filter' => $request->get_param('filter'),
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
        ]));
    }

    public function updateMedia(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $params = array_intersect_key($request->get_params(), $this->updateArgs());
        $result = $this->media->update((int) $request['id'], $params);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(['message' => __('Media informatie succesvol bijgewerkt.', 'modern-image-manager')]);
    }

    public function deleteMedia(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (! $this->media->delete((int) $request['id'])) {
            return new WP_Error('mim_delete_failed', __('Fout bij verwijderen media.', 'modern-image-manager'), ['status' => 400]);
        }

        return rest_ensure_response(['message' => __('Media succesvol verwijderd.', 'modern-image-manager')]);
    }

    public function bulkDeleteMedia(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response(['count' => $this->media->bulkDelete($this->normalizeIds($request->get_param('ids')))]);
    }

    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }
}
