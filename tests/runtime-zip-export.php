<?php

/**
 * Run only in the temporary WordPress/MySQL CI installation.
 * The ZIP bytes go to stdout, without storing any site or customer data.
 */

if (!class_exists(ZipArchive::class)) {
    throw new RuntimeException('ZipArchive is missing from the CI PHP runtime.');
}

wp_set_current_user(1);
$uploadDir = wp_upload_dir();
$filename = 'mim-ci-zip-fixture.png';
$file = trailingslashit($uploadDir['path']) . $filename;
$imageBytes = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
    true
);

if ($imageBytes === false || file_put_contents($file, $imageBytes) !== strlen($imageBytes)) {
    throw new RuntimeException('Unable to create isolated image fixture.');
}

$attachmentId = wp_insert_attachment([
    'post_mime_type' => 'image/png',
    'post_status' => 'inherit',
    'post_title' => 'CI ZIP export fixture',
], $file, 0, true);
if (is_wp_error($attachmentId) || (int) $attachmentId < 1) {
    throw new RuntimeException('Unable to register isolated image fixture.');
}
update_attached_file((int) $attachmentId, $file);

$_REQUEST['mim_zip_export_nonce_field'] = wp_create_nonce('mim_zip_export_nonce');
(new \ModernImageManager\Services\ZipService())->handleDownload();
