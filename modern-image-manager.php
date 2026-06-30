<?php
declare(strict_types=1);

/**
 * Plugin Name:       Modern Image Manager
 * Plugin URI:        https://webactueel.nl/
 * Description:       Beheer en bewerk media-informatie in WordPress met CSV-import/export, gebruiksscan en media-zip.
 * Version:           1.5.50
 * Author:            Webactueel
 * Author URI:        https://webactueel.nl
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      8.1
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Text Domain:       modern-image-manager
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

define('MODERN_IMAGE_MANAGER_VERSION', '1.5.50');
define('MODERN_IMAGE_MANAGER_FILE', __FILE__);
define('MODERN_IMAGE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('MODERN_IMAGE_MANAGER_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'ModernImageManager\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = MODERN_IMAGE_MANAGER_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain(
        'modern-image-manager',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    \ModernImageManager\Plugin::instance()->register();
});
