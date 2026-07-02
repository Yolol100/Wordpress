<?php
declare(strict_types=1);

namespace ModernImageManager\Support;

defined('ABSPATH') || exit;

final class Capabilities
{
    public const READ = 'upload_files';
    public const MANAGE = 'manage_options';

    public static function read(): string
    {
        return self::filteredCapability('modern_image_manager_read_capability', self::READ);
    }

    public static function manage(): string
    {
        return self::filteredCapability('modern_image_manager_manage_capability', self::MANAGE);
    }

    private static function filteredCapability(string $hook, string $default): string
    {
        $capability = apply_filters($hook, $default);

        return is_string($capability) && $capability !== '' ? $capability : $default;
    }
}
