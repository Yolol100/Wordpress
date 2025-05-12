<?php
/**
 * Template voor de Scan Duplicate Images pagina,
 * (aangepast: bulk-delete optie verwijderd)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager' ) );
}
?>

<div class="wrap mim-scan-container mim-scan-page">

  <?php if ( ! isset( $_POST['scan_duplicates'] ) || ! check_admin_referer( 'scan_duplicates_action', 'scan_duplicates_nonce' ) ) : ?>
    <div id="mim-scan-intro">
      <h1 class="mim-scan-page-title">
        <?php echo esc_html__( 'Scan Duplicate Images', 'modern-image-manager' ); ?>
      </h1>
      <p class="mim-scan-page-description">
        <?php
          echo esc_html__( 'Klik op de onderstaande knop om alle dubbele afbeeldingen te scannen, inclusief afbeeldingen uit Elementor-templates.', 'modern-image-manager' );
        ?>
      </p>

      <form method="POST" class="mim-scan-page-form">
        <?php wp_nonce_field( 'scan_duplicates_action', 'scan_duplicates_nonce' ); ?>
        <button type="submit" name="scan_duplicates" class="mim-scan-page-button">
          <?php esc_html_e( 'Start scan', 'modern-image-manager' ); ?>
        </button>
      </form>
    </div>
  <?php endif; ?>

  <?php
    // Scan uitvoeren en resultaten tonen (zonder bulk-delete)
    if ( isset( $_POST['scan_duplicates'] ) && check_admin_referer( 'scan_duplicates_action', 'scan_duplicates_nonce' ) ) {
        require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
        $results = dius_scan_pages_for_duplicate_images();

        echo '<p class="mim-scan-scope">'
           . esc_html__( 'De scan omvat nu ook afbeeldingen uit Elementor-templates en toont de pagina\'s waarop ze worden geladen.', 'modern-image-manager' )
           . '</p>';

        // Resultaten direct tonen, zonder checkboxes of verwijderknoppen
        dius_display_results( $results );
    }
  ?>

</div>