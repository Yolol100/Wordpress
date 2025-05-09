<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

if (! current_user_can('manage_options')) {
    wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'modern-image-manager'));
}

// Check of er een scan- of delete-actie is uitgevoerd
$scan_action   = isset($_POST['scan_duplicates']) && check_admin_referer('scan_duplicates_action',    'scan_duplicates_nonce');
$delete_action = isset($_POST['delete_selected']) && check_admin_referer('delete_duplicates_action', 'delete_duplicates_nonce');

// 1) Verwerk bulk-delete als dat is aangevraagd
if ($delete_action) {
    $to_delete = array_map('intval', $_POST['delete_ids'] ?? []);
    foreach ($to_delete as $attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }

    printf(
        '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
        esc_html(
            sprintf(
                _n('%d afbeelding verwijderd.', '%d afbeeldingen verwijderd.', count($to_delete), 'modern-image-manager'),
                count($to_delete)
            )
        )
    );
}
?>

<div class="wrap mim-scan-container mim-scan-page">

    <!-- 2) Intro-scanner tonen als er nog niet is gescand -->
    <?php if (! $scan_action) : ?>
        <div id="mim-scan-intro">
            <h1 class="mim-scan-page-title">
                <?php esc_html_e('Scan Duplicate Images', 'modern-image-manager'); ?>
            </h1>
            <p class="mim-scan-page-description">
                <?php esc_html_e('Klik op de onderstaande knop om alle dubbele afbeeldingen te scannen.', 'modern-image-manager'); ?>
            </p>
            <form method="post" class="mim-scan-page-form">
                <?php wp_nonce_field('scan_duplicates_action', 'scan_duplicates_nonce'); ?>
                <button type="submit" name="scan_duplicates" class="mim-scan-page-button">
                    <?php esc_html_e('Start scan', 'modern-image-manager'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- 3) Toon resultaten zodra er is gescand -->
    <?php if ($scan_action) :
        require_once MODERN_IMAGE_MANAGER_PATH . 'includes/scan-images/scanner.php';
        $results = dius_scan_pages_for_duplicate_images();
    ?>
        <form method="post" class="mim-scan-page-form">
            <?php
            // Zorg dat beide nonces bewaard blijven
            wp_nonce_field('scan_duplicates_action',    'scan_duplicates_nonce');
            wp_nonce_field('delete_duplicates_action', 'delete_duplicates_nonce');
            ?>
            <input type="hidden" name="scan_duplicates" value="1" />

            <h2 class="mim-scan-page-title">
                <?php esc_html_e('Scan resultaten', 'modern-image-manager'); ?>
            </h2>
            <p class="mim-scan-page-description">
                <strong><?php esc_html_e('Totaal aantal dubbele afbeeldingen gevonden:', 'modern-image-manager'); ?></strong>
                <?php echo esc_html((string) count($results)); ?>
            </p>

            <div class="mim-bulk-delete-container">
                <button
                    type="submit"
                    name="delete_selected"
                    id="dius-delete-selected"
                    class="mim-scan-page-button mim-bulk-delete-btn"
                    disabled
                >
                    <?php esc_html_e('Verwijder', 'modern-image-manager'); ?>
                </button>
            </div>

            <?php dius_display_results($results); ?>
        </form>
    <?php endif; ?>

</div>