<?php
/**
 * Template voor de Scan Duplicate Images pagina.
 * Dit bestand toont het formulier om de scan te starten.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager' ) );
}
?>

<div class="wrap mim-scan-page">
    <!-- Alleen tonen als de scan nog niet is uitgevoerd -->
    <?php if ( ! isset( $_POST['scan_duplicates'] ) ) : ?>
        <div class="mim-scan-container">
            <h1 class="mim-scan-page-title"><?php echo __( 'Scan duplicate images', 'modern-image-manager' ); ?></h1>
            <p class="mim-scan-page-description"><?php echo __( 'Klik op de onderstaande knop om dubbele afbeeldingen te scannen.', 'modern-image-manager' ); ?></p>

            <!-- Formulier om de scan te starten -->
            <form method="POST" enctype="multipart/form-data" class="page-form import-page-form">
                <?php
                // Beveiligingsnonce toevoegen om beveiligingsproblemen te voorkomen
                wp_nonce_field( 'scan_duplicates_action', 'scan_duplicates_nonce' );
                ?>
                <input type="submit" name="scan_duplicates" value="<?php _e( 'Start scan', 'modern-image-manager' ); ?>" class="mim-scan-page-button" />
            </form>
        </div>
    <?php else : ?>
        <!-- Als de scan al is uitgevoerd, worden de resultaten weergegeven -->
        <?php
        // Roep de adminpagina aan om de scan uit te voeren en de resultaten te tonen
        include_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/admin-page.php';
    ?>
    <?php endif; ?>
</div>