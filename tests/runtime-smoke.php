<?php

/**
 * Isolated WordPress runtime contract. Called via wp eval-file after plugin activation.
 * Does not mutate attachments or call external services.
 */

if (!defined('MODERN_IMAGE_MANAGER_VERSION')) {
    throw new RuntimeException('Modern Image Manager did not bootstrap.');
}

if (false === has_action('admin_post_mim_export_csv')) {
    throw new RuntimeException('CSV export handler is not registered.');
}
if (false === has_action('admin_post_modern_image_manager_zip_export')) {
    throw new RuntimeException('ZIP export handler is not registered.');
}

$routes = rest_get_server()->get_routes();
foreach (['/modern-image-manager/v1/media', '/modern-image-manager/v1/media/bulk-delete', '/modern-image-manager/v1/scan-duplicates'] as $route) {
    if (!isset($routes[$route])) {
        throw new RuntimeException('Expected REST route not found: ' . $route);
    }
}

wp_set_current_user(0);
foreach (['/modern-image-manager/v1/media', '/modern-image-manager/v1/scan-duplicates'] as $route) {
    $anonymous = rest_do_request(new WP_REST_Request('GET', $route));
    if ($anonymous->get_status() < 400) {
        throw new RuntimeException('Anonymous read was allowed: ' . $route);
    }
}

wp_set_current_user(1);
if (!current_user_can('upload_files')) {
    throw new RuntimeException('CI admin lacks upload_files capability.');
}
foreach (['/modern-image-manager/v1/media', '/modern-image-manager/v1/scan-duplicates'] as $route) {
    $authorized = rest_do_request(new WP_REST_Request('GET', $route));
    if ($authorized->get_status() !== 200 || !is_array($authorized->get_data())) {
        throw new RuntimeException('Authorized read failed: ' . $route . ' (' . $authorized->get_status() . ')');
    }
}

wp_set_current_user(0);
$delete = new WP_REST_Request('DELETE', '/modern-image-manager/v1/media/987654321');
$delete->set_param('id', 987654321);
if (rest_do_request($delete)->get_status() < 400) {
    throw new RuntimeException('Anonymous attachment delete was allowed.');
}

echo "Modern Image Manager REST, capability and startup smoke: OK\n";
