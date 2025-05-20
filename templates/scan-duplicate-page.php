<?php
/**
 * Template: Scan Duplicate Images (zonder bulk-delete)
 */

defined('ABSPATH') || exit;

if (! current_user_can('manage_options')) {
    wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager'));
}

?>
<div class="wrap mim-scan-container mim-scan-page">

  <!-- Intro en startknop -->
  <?php if (
    ! isset($_POST['scan_duplicates']) ||
    ! check_admin_referer('scan_duplicates_action', 'scan_duplicates_nonce')
  ) : ?>

    <div id="mim-scan-intro">
      <h1 class="mim-scan-page-title">
        <?php echo esc_html__('Scan Duplicate Images', 'modern-image-manager'); ?>
      </h1>
      <p class="mim-scan-page-description">
        <?php echo esc_html__('Klik op de onderstaande knop om alle dubbele afbeeldingen te scannen, inclusief afbeeldingen uit Elementor-templates.', 'modern-image-manager'); ?>
      </p>
      <form method="post" class="mim-scan-page-form">
        <?php wp_nonce_field('scan_duplicates_action', 'scan_duplicates_nonce'); ?>
        <button type="submit" name="scan_duplicates" class="mim-scan-page-button">
          <?php esc_html_e('Start scan', 'modern-image-manager'); ?>
        </button>
      </form>
    </div>

  <?php endif; ?>

  <!-- Scan loader met animatie -->
  <div id="mim-scan-loader" hidden>
    <div class="spinner"></div>
    <em><?php esc_html_e('Afbeeldingen worden gescand... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
  </div>

  <!-- Toast container -->
  <div id="mim-toast" role="alert" aria-live="assertive" hidden></div>

  <?php if (
    isset($_POST['scan_duplicates']) &&
    check_admin_referer('scan_duplicates_action', 'scan_duplicates_nonce')
  ) : ?>

    <?php
      require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
      $results = dius_scan_pages_for_duplicate_images();
    ?>

    <!-- Controls: summary, zoekveld, filter -->
    <div id="mim-enhanced-controls">
      <div id="mim-scan-summary" aria-live="polite"></div>
      <input
        type="search"
        id="mim-search"
        placeholder="<?php esc_attr_e('Zoek op bestandsnaam...', 'modern-image-manager'); ?>"
      >
      <select id="filter-type">
        <option value="all"><?php esc_html_e('Alle types', 'modern-image-manager'); ?></option>
        <option value="post_content"><?php esc_html_e('In content', 'modern-image-manager'); ?></option>
        <option value="featured_image"><?php esc_html_e('Uitgelichte afbeelding', 'modern-image-manager'); ?></option>
        <option value="acf_field"><?php esc_html_e('ACF veld', 'modern-image-manager'); ?></option>
        <option value="elementor_template"><?php esc_html_e('Elementor', 'modern-image-manager'); ?></option>
      </select>
    </div>

    <!-- Resultaten en scan-opnieuw -->
    <div class="scan-wrapper">
      <?php dius_display_results($results); ?>

      <div style="text-align:center; margin-top:1.5rem;">
        <button id="mim-rescan-btn" class="mim-scan-page-button">
          <?php esc_html_e('Opnieuw scannen', 'modern-image-manager'); ?>
        </button>
      </div>
    </div>

    <?php if (! empty($results)) : ?>
      <!-- Lightbox modal alleen bij resultaten -->
      <div id="mim-lightbox" class="mim-lightbox" hidden>
        <span class="mim-lightbox-close">&times;</span>
        <img src="" alt="">
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>