<?php
defined('ABSPATH') || exit;
$screen = isset($mim_screen) ? sanitize_key((string) $mim_screen) : 'media';
?>
<div class="wrap mim-admin-shell-wrap">
    <div id="modern-image-manager-admin-root" data-screen="<?php echo esc_attr($screen); ?>"></div>
</div>
