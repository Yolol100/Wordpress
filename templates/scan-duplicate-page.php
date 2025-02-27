<?php
/**
 * Template voor de Scan Duplicate Images pagina.
 * Dit bestand toont het formulier om de scan te starten en verwerkt de resultaten.
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
      <h1 class="mim-scan-page-title"><?php echo __( 'Scan Duplicate Images', 'modern-image-manager' ); ?></h1>
      <p class="mim-scan-page-description"><?php echo __( 'Klik op de onderstaande knop om dubbele afbeeldingen te scannen.', 'modern-image-manager' ); ?></p>
      
      <!-- Formulier om de scan te starten -->
      <form method="POST" enctype="multipart/form-data" class="mim-scan-page-form">
        <?php wp_nonce_field( 'scan_duplicates_action', 'scan_duplicates_nonce' ); ?>
        <button type="submit" name="scan_duplicates" class="mim-scan-page-button"><?php esc_attr_e( 'Start scan', 'modern-image-manager' ); ?></button>
      </form>
    </div>
  <?php endif; ?>

  <?php
    // Controleer of de scan moet worden gestart
    if ( isset( $_POST['scan_duplicates'] ) && check_admin_referer( 'scan_duplicates_action', 'scan_duplicates_nonce' ) ) {
        require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';

        $results = dius_scan_pages_for_duplicate_images();

        if ( ! empty( $results ) ) {
            dius_display_results( $results );
        } else {
            echo '<p class="scan-no-results">' . esc_html__( 'Geen dubbele afbeeldingen gevonden.', 'modern-image-manager' ) . '</p>';
        }
    }
  ?>
</div>