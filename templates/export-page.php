<?php
// Haal foutmelding op via transient
$error_message = get_transient('media_export_error_message');
if ($error_message) {
    delete_transient('media_export_error_message');
}
?>

<body class="data-page">
  <div class="page-container export-page-container">

    <h1 class="page-title export-page-title">
      <?php echo esc_html__('Exporteren', 'modern-image-manager'); ?>
    </h1>

    <p class="page-description export-page-description">
      <?php echo esc_html__('Selecteer de exportoptie en kies welke kolommen je wilt exporteren. Klik vervolgens op de knop om de CSV te genereren.', 'modern-image-manager'); ?>
    </p>

    <?php if (!empty($error_message)): ?>
      <div class="mim-notice mim-notice--error">
        <p><?php echo esc_html($error_message); ?></p>
      </div>
    <?php endif; ?>

    <form method="post" class="page-form export-page-form">

      <!-- Exportoptie dropdown -->
      <div class="form-group export-option-group">
        <label for="export-option" class="export-option-label">
          <?php echo esc_html__('Kies een exportoptie:', 'modern-image-manager'); ?>
        </label>
        <select name="export_option" id="export-option" class="custom-dropdown export-option-dropdown">
          <option value="all"><?php echo esc_html__('Alle pagina’s', 'modern-image-manager'); ?></option>
          <option value="linked"><?php echo esc_html__('Alleen gekoppelde rijen', 'modern-image-manager'); ?></option>
          <option value="unlinked"><?php echo esc_html__('Alleen niet-gekoppelde rijen', 'modern-image-manager'); ?></option>
        </select>
      </div>

      <!-- Kolommen selectie -->
      <div class="form-group columns-select-group">
        <label for="exportColumns" class="columns-select-label">
          <?php echo esc_html__('Selecteer kolommen', 'modern-image-manager'); ?>
        </label>
        <select id="exportColumns" name="export_columns[]" multiple class="custom-dropdown columns-select-dropdown">
          <option value="filename"><?php echo esc_html__('Bestandsnaam', 'modern-image-manager'); ?></option>
          <option value="new_filename"><?php echo esc_html__('Nieuwe bestandsnaam', 'modern-image-manager'); ?></option>
          <option value="page"><?php echo esc_html__('Pagina', 'modern-image-manager'); ?></option>
          <option value="title"><?php echo esc_html__('Titel', 'modern-image-manager'); ?></option>
          <option value="caption"><?php echo esc_html__('Caption', 'modern-image-manager'); ?></option>
          <option value="description"><?php echo esc_html__('Description', 'modern-image-manager'); ?></option>
          <option value="alt_text"><?php echo esc_html__('Alt-tag', 'modern-image-manager'); ?></option>
          <option value="file_url"><?php echo esc_html__('Bestands-URL', 'modern-image-manager'); ?></option>
        </select>
      </div>

      <!-- Exporteerknop -->
      <button type="submit" name="export_csv" class="page-button export-button">
        <?php echo esc_html__('Exporteer naar CSV', 'modern-image-manager'); ?>
      </button>
    </form>
  </div>
</body>