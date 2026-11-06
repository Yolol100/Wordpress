<?php

/**
 * Isolated WordPress runtime contract. Called via wp eval-file after plugin activation.
 * Only creates a temporary attachment in isolated CI; no external requests.
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

// Deliberately exercise a page that no longer exists after media deletion.
wp_set_current_user(1);
$fixtureId = wp_insert_attachment([
    'post_mime_type' => 'image/png',
    'post_status' => 'inherit',
    'post_title' => 'Isolated pagination test image',
], '', 0, true);
if (is_wp_error($fixtureId) || (int) $fixtureId < 1) {
    throw new RuntimeException('Could not create isolated pagination fixture');
}

try {
    foreach (['all', 'unlinked'] as $filter) {
        $firstPage = new WP_REST_Request('GET', '/modern-image-manager/v1/media');
        $firstPage->set_param('filter', $filter);
        $firstPage->set_param('page', 1);
        $firstResponse = rest_do_request($firstPage);
        $firstData = $firstResponse->get_data();
        $firstIds = array_map('intval', array_column((array) ($firstData['items'] ?? []), 'id'));
        if (!in_array((int) $fixtureId, $firstIds, true)) {
            throw new RuntimeException(
                "Pagination fixture missing from first page ({$filter}): "
                . wp_json_encode([
                    'fixture_id' => (int) $fixtureId,
                    'status' => get_post_status($fixtureId),
                    'mime' => get_post_mime_type($fixtureId),
                    'total' => $firstData['total'] ?? null,
                    'pages' => $firstData['pages'] ?? null,
                ])
            );
        }

        $request = new WP_REST_Request('GET', '/modern-image-manager/v1/media');
        $request->set_param('filter', $filter);
        $request->set_param('page', 999);
        $response = rest_do_request($request);
        $data = $response->get_data();

        if ($response->get_status() !== 200 || !is_array($data)) {
            throw new RuntimeException("Pagination request failed: {$filter}");
        }
        if ((int) ($data['page'] ?? 0) !== 1 || (int) ($data['pages'] ?? 0) !== 1) {
            throw new RuntimeException("Incorrect last-page metadata: {$filter}");
        }
        $returnedIds = array_column((array) ($data['items'] ?? []), 'id');
        if (!in_array((int) $fixtureId, array_map('intval', $returnedIds), true)) {
            throw new RuntimeException("Out-of-range pagination returned an empty last page: {$filter}");
        }
    }
} finally {
    wp_delete_attachment((int) $fixtureId, true);
}

echo "Modern Image Manager REST, capability and startup smoke: OK\n";
