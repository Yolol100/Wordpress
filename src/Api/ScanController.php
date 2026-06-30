<?php
declare(strict_types=1);

namespace ModernImageManager\Api;

use ModernImageManager\Services\ScanService;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class ScanController
{
    public function __construct(private ?ScanService $scan = null)
    {
        $this->scan = $scan ?: new ScanService();
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('modern-image-manager/v1', '/scan-duplicates', [[
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'scanDuplicates'],
            'permission_callback' => static fn(): bool => current_user_can('upload_files'),
        ]]);
    }

    public function scanDuplicates(): WP_REST_Response
    {
        $items = $this->scan->formattedDuplicates();
        return rest_ensure_response(['items' => $items, 'total' => count($items)]);
    }
}
