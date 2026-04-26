<?php
// File: export-page.php
declare(strict_types=1);
defined('ABSPATH') || exit;

// Haal foutmelding op via transient
$error_message = get_transient('media_export_error_message');
if ($error_message) {
    delete_transient('media_export_error_message');
}

// Tooltip-uitleg per kolom
$column_tooltips = [
    'filename'     => 'De originele naam van het bestand in de media bibliotheek.',
    'new_filename' => 'De aangepaste bestandsnaam die je hebt ingevoerd (indien beschikbaar).',
    'page'         => 'De pagina waarmee het mediabestand is gekoppeld (indien beschikbaar).',
    'title'        => 'De titel van het bestand zoals ingesteld in de mediabibliotheek.',
    'caption'      => 'De korte beschrijving die onder het bestand wordt weergegeven.',
    'description'  => 'De volledige beschrijving van het bestand.',
    'alt_text'     => 'De alternatieve tekst voor SEO en toegankelijkheid.',
    'file_url'     => 'De volledige URL naar het bestand.',
];
?>

<div class="data-page">
  <div class="page-container export-page-container">

    <h1 class="page-title export-page-title">
      <?php echo esc_html__('Exporteren', 'modern-image-manager'); ?>
    </h1>

    <?php if (! empty($error_message)) : ?>
      <div class="mim-notice mim-notice--error" role="alert" aria-live="assertive">
        <p><?php echo esc_html($error_message); ?></p>
      </div>
    <?php endif; ?>

    <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="page-form export-page-form" novalidate>
      <input type="hidden" name="action" value="mim_export_csv">
      <?php wp_nonce_field('mim_export_csv', 'mim_export_nonce'); ?>

      <!-- 1) Exportoptie dropdown -->
      <div class="form-group export-option-group">
        <label for="export-option" class="export-option-label">
          <?php echo esc_html__('Kies een exportoptie:', 'modern-image-manager'); ?>
        </label>
        <select
          name="export_option"
          id="export-option"
          class="custom-dropdown export-option-dropdown"
        >
          <option value="all"><?php echo esc_html__('Alle pagina’s', 'modern-image-manager'); ?></option>
          <option value="linked"><?php echo esc_html__('Alleen gekoppelde rijen', 'modern-image-manager'); ?></option>
          <option value="unlinked"><?php echo esc_html__('Alleen niet-gekoppelde rijen', 'modern-image-manager'); ?></option>
        </select>
      </div>

      <!-- 2) Verborgen veld met standaard bestandsnaam -->
      <input type="hidden" name="export_filename" value="Export" />

      <!-- 3) Kolommen selectie -->
      <div class="form-group columns-select-group">
        <label class="columns-select-label" id="export-columns-label">
          <?php echo esc_html__('Selecteer kolommen', 'modern-image-manager'); ?>
        </label>
        <div class="checkbox-group" role="group" aria-labelledby="export-columns-label">
          <?php
          $columns = [
            'filename'     => __('Bestandsnaam', 'modern-image-manager'),
            'new_filename' => __('Nieuwe bestandsnaam', 'modern-image-manager'),
            'page'         => __('Pagina', 'modern-image-manager'),
            'title'        => __('Titel', 'modern-image-manager'),
            'caption'      => __('Caption', 'modern-image-manager'),
            'description'  => __('Beschrijving', 'modern-image-manager'),
            'alt_text'     => __('Alt-tag', 'modern-image-manager'),
            'file_url'     => __('Bestands-URL', 'modern-image-manager'),
          ];
          foreach ($columns as $value => $label) {
              $tooltip = isset($column_tooltips[$value]) ? esc_attr($column_tooltips[$value]) : '';
              printf(
                  '<label class="checkbox-item" title="%s"><input type="checkbox" name="export_columns[]" value="%s" checked> %s</label>',
                  $tooltip,
                  esc_attr($value),
                  esc_html($label)
              );
          }
          ?>
        </div>
      </div>

      <!-- 4) Exporteerknop -->
      <button
        type="submit"
        class="page-button export-button"
        aria-label="<?php esc_attr_e('Exporteer geselecteerde kolommen naar CSV', 'modern-image-manager'); ?>"
      >
        <?php echo esc_html__('Exporteer naar CSV', 'modern-image-manager'); ?>
      </button>
    </form>
  </div>
</div>