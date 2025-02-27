<?php
// Voeg de foutmelding toe
$error_message = get_transient('media_export_error_message');
if ($error_message) {
    // Foutmelding is aanwezig, verwijder de transient
    delete_transient('media_export_error_message');
}
?>

<body class="data-page">
  <div class="page-container export-page-container">
    <h1 class="page-title export-page-title"><?php echo __("Exporteren", "modern-image-manager"); ?></h1>
    <p class="page-description export-page-description">
      <?php echo __("Selecteer de exportoptie en kies welke kolommen je wilt exporteren. Klik vervolgens op de knop om de CSV te genereren.", "modern-image-manager"); ?>
    </p>
    <form method="post" action="" class="page-form export-page-form">
      
    <!-- Exportoptie Dropdown -->
    <div class="form-group export-option-group">
      <label for="export-option" class="export-option-label"><?php echo __("Kies een exportoptie:", "modern-image-manager"); ?></label>
      <select name="export_option" id="export-option" class="custom-dropdown export-option-dropdown">
        <option value="all"><?php echo __("Alle pagina’s", "modern-image-manager"); ?></option>
        <option value="linked"><?php echo __("Alleen gekoppelde rijen", "modern-image-manager"); ?></option>
        <option value="unlinked"><?php echo __("Alleen niet-gekoppelde rijen", "modern-image-manager"); ?></option>
      </select>
    </div>
      
    <!-- Kolommen selecteren met een multi-select dropdown -->
    <div class="form-group columns-select-group">
      <label for="exportColumns" class="columns-select-label"><?php echo __("Selecteer kolommen", "modern-image-manager"); ?></label>
      <select id="exportColumns" name="export_columns[]" multiple class="custom-dropdown columns-select-dropdown">
        <option value="filename"><?php echo __("Bestandsnaam", "modern-image-manager"); ?></option>
        <option value="new_filename"><?php echo __("Nieuwe bestandsnaam", "modern-image-manager"); ?></option>
        <option value="page"><?php echo __("Pagina", "modern-image-manager"); ?></option>
        <option value="title"><?php echo __("Titel", "modern-image-manager"); ?></option>
        <option value="caption"><?php echo __("Caption", "modern-image-manager"); ?></option>
        <option value="description"><?php echo __("Description", "modern-image-manager"); ?></option>
        <option value="alt_text"><?php echo __("Alt-tag", "modern-image-manager"); ?></option>
        <option value="file_url"><?php echo __("Bestands-URL", "modern-image-manager"); ?></option>
      </select>
    </div>

      <!-- Exporteer Button -->
      <button type="submit" name="export_csv" class="page-button export-button">
        <?php echo __("Exporteer naar CSV", "modern-image-manager"); ?>
      </button>

      <!-- Foutmelding weergeven als er geen resultaten zijn -->
      <?php if ($error_message): ?>
        <div id="noExportMessage">
          <p style="color: red; text-align: center; margin-top: 10px;"><?php echo $error_message; ?></p>
        </div>
      <?php endif; ?>
    </form>
  </div>
</body>