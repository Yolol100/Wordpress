<?php
/**
 * Template: Scan Duplicate Images (zonder bulk-delete)
 */

defined('ABSPATH') || exit;

?>
<div class="wrap mim-scan-container mim-scan-page">

  <!-- Intro en startknop -->
  <?php if (! isset($_POST['scan_duplicates'])) : ?>

    <section id="mim-scan-intro" aria-label="<?php esc_attr_e('Introductie scan dubbele afbeeldingen', 'modern-image-manager'); ?>">
      <h1 class="mim-scan-page-title">
        <?php echo esc_html__('Scan Duplicate Images', 'modern-image-manager'); ?>
      </h1>
      <p class="mim-scan-page-description">
        <?php echo esc_html__('Klik op de onderstaande knop om alle dubbele afbeeldingen te scannen, inclusief afbeeldingen uit Elementor-templates.', 'modern-image-manager'); ?>
      </p>
      <form method="post" class="mim-scan-page-form" novalidate>
        <!-- nonce veld verwijderd -->
        <button type="submit" name="scan_duplicates" class="mim-scan-page-button" aria-label="<?php esc_attr_e('Start scan naar dubbele afbeeldingen', 'modern-image-manager'); ?>">
          <?php esc_html_e('Start scan', 'modern-image-manager'); ?>
        </button>
      </form>
    </section>

  <?php endif; ?>

  <!-- Scan loader met animatie -->
  <div id="mim-scan-loader" hidden role="status" aria-live="polite" aria-busy="true">
    <div class="spinner" aria-hidden="true"></div>
    <em><?php esc_html_e('Afbeeldingen worden gescand... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
  </div>

  <!-- Toast container -->
  <div id="mim-toast" role="alert" aria-live="assertive" hidden></div>

  <?php if (isset($_POST['scan_duplicates'])) : ?>

    <?php
      require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
      $results = dius_scan_pages_for_duplicate_images();
    ?>

    <!-- Controls: summary, zoekveld, filter -->
    <section id="mim-enhanced-controls" aria-label="<?php esc_attr_e('Bestandsnaam zoeken en filteren', 'modern-image-manager'); ?>">
      <div id="mim-scan-summary" aria-live="polite"></div>
      <input
        type="search"
        id="mim-search"
        placeholder="<?php esc_attr_e('Zoek op bestandsnaam...', 'modern-image-manager'); ?>"
        aria-label="<?php esc_attr_e('Zoeken op bestandsnaam', 'modern-image-manager'); ?>"
        autocomplete="off"
      >
      <select id="filter-type" aria-label="<?php esc_attr_e('Filter type', 'modern-image-manager'); ?>" name="filter-type" aria-describedby="filter-type-help">
        <option value="all"><?php esc_html_e('Alle types', 'modern-image-manager'); ?></option>
        <option value="post_content"><?php esc_html_e('In content', 'modern-image-manager'); ?></option>
        <option value="featured_image"><?php esc_html_e('Uitgelichte afbeelding', 'modern-image-manager'); ?></option>
        <option value="acf_field"><?php esc_html_e('ACF veld', 'modern-image-manager'); ?></option>
        <option value="elementor_template"><?php esc_html_e('Elementor', 'modern-image-manager'); ?></option>
      </select>
      <small id="filter-type-help" class="screen-reader-text"><?php esc_html_e('Filter op type afbeeldinggebruik', 'modern-image-manager'); ?></small>
    </section>

    <!-- Resultaten en scan-opnieuw -->
    <section class="scan-wrapper" aria-live="polite" aria-relevant="additions removals">
      <?php dius_display_results($results); ?>

      <div style="text-align:center; margin-top:1.5rem;">
        <button id="mim-rescan-btn" class="mim-scan-page-button" type="button" aria-label="<?php esc_attr_e('Opnieuw scannen naar dubbele afbeeldingen', 'modern-image-manager'); ?>">
          <?php esc_html_e('Opnieuw scannen', 'modern-image-manager'); ?>
        </button>
      </div>
    </section>

    <?php if (! empty($results)) : ?>
      <!-- Lightbox modal alleen bij resultaten -->
      <div id="mim-lightbox" class="mim-lightbox" hidden role="dialog" aria-modal="true" aria-labelledby="mim-lightbox-title">
        <button type="button" class="mim-lightbox-close" aria-label="<?php esc_attr_e('Sluit', 'modern-image-manager'); ?>">&times;</button>
        <img src="" alt="" />
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>