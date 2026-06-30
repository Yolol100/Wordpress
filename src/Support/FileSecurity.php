<?php
declare(strict_types=1);

namespace ModernImageManager\Support;

defined('ABSPATH') || exit;

final class FileSecurity
{
    /** @var string[] */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    public function isPathInsideUploads(string $path, bool $allowMissingFile = false): bool
    {
        $uploadDir = wp_upload_dir();
        $baseDir = realpath((string) ($uploadDir['basedir'] ?? ''));
        if (! $baseDir) {
            return false;
        }

        $target = realpath($path);
        if (! $target && $allowMissingFile) {
            $parent = realpath(dirname($path));
            if ($parent) {
                $target = $parent . DIRECTORY_SEPARATOR . basename($path);
            }
        }

        if (! $target) {
            return false;
        }

        $base = trailingslashit(wp_normalize_path($baseDir));
        $normalizedTarget = wp_normalize_path($target);

        return $normalizedTarget === untrailingslashit($base) || str_starts_with($normalizedTarget, $base);
    }

    public function isAllowedImageFile(string $path): bool
    {
        if (! is_file($path) || ! $this->isPathInsideUploads($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return false;
        }

        $filetype = wp_check_filetype(basename($path));
        if (empty($filetype['type'])) {
            return false;
        }

        return str_starts_with((string) $filetype['type'], 'image/');
    }
}
