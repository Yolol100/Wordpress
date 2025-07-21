<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_mim_scan_duplicates', 'mim_ajax_scan_duplicates');

function mim_ajax_scan_duplicates() {
  check_ajax_referer('mim_scan_duplicates', 'mim_scan_duplicates_nonce');

  $results = function_exists('dius_scan_pages_for_duplicate_images')
    ? dius_scan_pages_for_duplicate_images()
    : [];

  if (empty($results)) {
    echo '<div class="mim-scan-loader-centered" style="margin-bottom:1.5rem;">';
    echo '<em>' . esc_html__('Geen dubbele afbeeldingen gevonden.', 'modern-image-manager') . '</em>';
    echo '</div>';
    echo '<div style="text-align:center; margin-top:1.5rem;">';
    echo '<button id="mim-rescan-btn" class="mim-scan-page-button" type="button" aria-label="' . esc_attr__('Opnieuw scannen', 'modern-image-manager') . '">' . esc_html__('Opnieuw scannen', 'modern-image-manager') . '</button>';
    echo '</div>';
  } else {
    ?>
    <section id="mim-enhanced-controls" aria-label="<?php esc_attr_e( 'Bestandsnaam zoeken en filteren', 'modern-image-manager' ); ?>">
      <div id="mim-scan-summary" aria-live="polite"></div>
      <input type="search" id="mim-search" placeholder="<?php esc_attr_e( 'Zoek op bestandsnaam...', 'modern-image-manager' ); ?>" aria-label="<?php esc_attr_e( 'Zoeken op bestandsnaam', 'modern-image-manager' ); ?>" autocomplete="off" />
      <select id="filter-type" aria-label="<?php esc_attr_e( 'Filter type', 'modern-image-manager' ); ?>" name="filter-type" aria-describedby="filter-type-help">
        <option value="all"><?php esc_html_e( 'Alle types', 'modern-image-manager' ); ?></option>
        <option value="post_content"><?php esc_html_e( 'In content', 'modern-image-manager' ); ?></option>
        <option value="featured_image"><?php esc_html_e( 'Uitgelichte afbeelding', 'modern-image-manager' ); ?></option>
        <option value="acf_field"><?php esc_html_e( 'ACF veld', 'modern-image-manager' ); ?></option>
        <option value="elementor"><?php esc_html_e( 'Elementor', 'modern-image-manager' ); ?></option>
      </select>
      <small id="filter-type-help" class="screen-reader-text"><?php esc_html_e( 'Filter op type afbeeldinggebruik', 'modern-image-manager' ); ?></small>
    </section>

    <section class="scan-wrapper" aria-live="polite" aria-relevant="additions removals">
      <?php dius_display_results( $results ); ?>
      <div style="text-align:center; margin-top:1.5rem;">
        <button id="mim-rescan-btn" class="mim-scan-page-button" type="button" aria-label="<?php esc_attr_e( 'Opnieuw scannen naar dubbele afbeeldingen', 'modern-image-manager' ); ?>">
          <?php esc_html_e( 'Opnieuw scannen', 'modern-image-manager' ); ?>
        </button>
      </div>
    </section>
    <?php
  }

  wp_die();
}