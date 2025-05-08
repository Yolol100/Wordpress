<?php
/**
 * Template voor de Scan Duplicate Images pagina,
 * uitgebreid met een bulk-delete optie.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager' ) );
}

// 1) Verwerk bulk‐delete
if ( isset( $_POST['delete_selected'] ) && check_admin_referer( 'delete_duplicates_action', 'delete_duplicates_nonce' ) ) {
    $to_delete = array_map( 'intval', $_POST['delete_ids'] ?? [] );
    foreach ( $to_delete as $aid ) {
        wp_delete_attachment( $aid, true );
    }
    printf(
      '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
      esc_html( sprintf(
        _n( '%d afbeelding verwijderd.', '%d afbeeldingen verwijderd.', count( $to_delete ), 'modern-image-manager' ),
        count( $to_delete )
      ) )
    );
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
    // 2) Run scan & toon resultaten met bulk-delete
    if ( isset( $_POST['scan_duplicates'] ) && check_admin_referer( 'scan_duplicates_action', 'scan_duplicates_nonce' ) ) {
        require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
        $results = dius_scan_pages_for_duplicate_images();

        echo '<p class="mim-scan-scope">'
           . esc_html__( 'De scan omvat nu ook afbeeldingen uit Elementor-templates en toont de pagina\'s waarop ze worden geladen.', 'modern-image-manager' )
           . '</p>';

        // Form wrapper voor bulk delete
        echo '<form method="POST">';
        wp_nonce_field( 'delete_duplicates_action', 'delete_duplicates_nonce' );
        // Zorg dat de scan-flag behouden blijft bij re-submit
        echo '<input type="hidden" name="scan_duplicates" value="1">';

        // Bulk-delete knop boven de tabel
        echo '<p><button type="submit" name="delete_selected" class="button button-danger">'
           . esc_html__( 'Verwijder geselecteerde', 'modern-image-manager' )
           . '</button></p>';

        // Tabel met checkboxes
        dius_display_results( $results );

        // Bulk-delete knop onder de tabel
        echo '<p><button type="submit" name="delete_selected" class="button button-danger">'
           . esc_html__( 'Verwijder geselecteerde', 'modern-image-manager' )
           . '</button></p>';

        echo '</form>';
    }
  ?>

</div>