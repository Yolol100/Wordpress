<?php
/**
 * Template: Scan Duplicate Images (zonder bulk-delete)
 */

defined('ABSPATH') || exit;

if ( ! current_user_can('manage_options') ) {
    wp_die( esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager') );
}
?>

<div class="wrap mim-scan-container mim-scan-page">

  <div id="mim-scan-loader" hidden style="text-align:center; margin-top: 1rem;">
    🔄 <em><?php esc_html_e('Afbeeldingen worden gescand... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
  </div>

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

  <?php if (
    isset($_POST['scan_duplicates']) &&
    check_admin_referer('scan_duplicates_action', 'scan_duplicates_nonce')
  ) : ?>

    <?php
      require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
      $results = dius_scan_pages_for_duplicate_images();

      if ( ! empty( $results ) ) {
          echo '<div id="mim-enhanced-controls" style="margin-bottom: 1rem;">';
          echo '<div id="mim-scan-summary" aria-live="polite" style="font-weight: bold; margin-bottom: 0.5rem;"></div>';
          echo '<input type="search" id="mim-search" placeholder="Zoek op bestandsnaam..." oninput="filterScanResults()" style="width: 100%; padding: 8px; max-width: 400px;">';
          echo '</div>';

          echo '<div class="scan-wrapper">';
          echo '<div class="mim-scan-filter" style="margin: 1rem 0;">';
          echo '<label for="filter-type">' . esc_html__('Filter op type:', 'modern-image-manager') . '</label> ';
          echo '<select id="filter-type" onchange="filterTableByType(this.value)">';
          echo '<option value="all">' . esc_html__('Alle types', 'modern-image-manager') . '</option>';
          echo '<option value="post_content">' . esc_html__('In content', 'modern-image-manager') . '</option>';
          echo '<option value="featured_image">' . esc_html__('Uitgelichte afbeelding', 'modern-image-manager') . '</option>';
          echo '<option value="acf_field">' . esc_html__('ACF veld', 'modern-image-manager') . '</option>';
          echo '<option value="elementor_template">' . esc_html__('Elementor', 'modern-image-manager') . '</option>';
          echo '</select>';
          echo '</div>';
      }

      dius_display_results( $results );

      if ( ! empty( $results ) ) {
          echo '</div>'; // sluit .scan-wrapper
      }
    ?>
  <?php endif; ?>
</div>