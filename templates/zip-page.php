<?php
defined('ABSPATH') || exit;
?>
<div class="wrap mim-zip-container">
    <h1 class="mim-zip-title"><?php esc_html_e('Media ZIP Export', 'modern-image-manager'); ?></h1>

    <form id="mim-zip-export-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('mim_zip_export_nonce', 'mim_zip_export_nonce_field'); ?>
        <input type="hidden" name="action" value="modern_image_manager_zip_export">

        <p class="mim-zip-description">
            <?php esc_html_e('Maak een ZIP-bestand aan van alle mediabestanden in je WordPress mediabibliotheek.', 'modern-image-manager'); ?>
        </p>

        <div class="mim-zip-options">
            <p>
                <label>
                    <input type="checkbox" name="include_images" value="1" checked disabled>
                    <?php esc_html_e('Alle afbeeldingen meenemen', 'modern-image-manager'); ?>
                </label>
            </p>
            <p class="mim-zip-options-description">
                <?php esc_html_e('Deze optie is standaard ingeschakeld en kan niet worden uitgeschakeld.', 'modern-image-manager'); ?>
            </p>
        </div>

        <p>
            <button type="submit" class="mim-zip-button" id="mim-zip-export-button">
                <?php esc_html_e('Genereer ZIP-bestand en download', 'modern-image-manager'); ?>
            </button>
            <span class="mim-zip-spinner" id="mim-zip-export-spinner" style="float:none; vertical-align: middle;"></span>
        </p>

        <div id="mim-zip-export-result" class="mim-zip-result" aria-live="polite"></div>
    </form>
</div>