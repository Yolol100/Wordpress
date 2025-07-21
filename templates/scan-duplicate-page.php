<?php
/**
 * Template: Scan Duplicate Images (AJAX-versie, loader tijdens scan, nette melding)
 */
defined('ABSPATH') || exit;
?>
<div class="wrap mim-scan-container mim-scan-page">

  <div id="mim-scan-content">

    <!-- Intro en startknop -->
    <section id="mim-scan-intro" aria-label="<?php esc_attr_e( 'Introductie scan dubbele afbeeldingen', 'modern-image-manager' ); ?>">
      <h1 class="mim-scan-page-title"><?php esc_html_e( 'Scan Duplicate Images', 'modern-image-manager' ); ?></h1>
      <p class="mim-scan-page-description"><?php esc_html_e( 'Klik op de onderstaande knop om alle dubbele afbeeldingen te scannen, inclusief afbeeldingen uit Elementor-templates.', 'modern-image-manager' ); ?></p>
      <form method="post" class="mim-scan-page-form" action="<?php echo admin_url('admin-ajax.php'); ?>" novalidate>
        <input type="hidden" name="action" value="mim_scan_duplicates">
        <?php wp_nonce_field( 'mim_scan_duplicates', 'mim_scan_duplicates_nonce' ); ?>
        <button type="submit" class="mim-scan-page-button" aria-label="<?php esc_attr_e( 'Start scan naar dubbele afbeeldingen', 'modern-image-manager' ); ?>">
          <?php esc_html_e( 'Start scan', 'modern-image-manager' ); ?>
        </button>
      </form>
    </section>

  </div>

</div>