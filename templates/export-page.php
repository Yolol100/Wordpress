<body class="data-page">
  <div class="page-container">
    <h1 class="page-title"><?php echo __("Exporteren", "modern-image-manager"); ?></h1>
    <p class="page-description">
      <?php echo __("Selecteer de exportoptie en kies welke kolommen je wilt exporteren. Klik vervolgens op de knop om de CSV te genereren.", "modern-image-manager"); ?>
    </p>
    <form method="post" action="" class="page-form">
      
      <!-- Exportoptie Dropdown -->
      <div class="form-group">
        <label for="export-option"><?php echo __("Kies een exportoptie:", "modern-image-manager"); ?></label>
        <select name="export_option" id="export-option" class="custom-dropdown">
          <option value="all"><?php echo __("Alle pagina’s", "modern-image-manager"); ?></option>
          <option value="linked"><?php echo __("Alleen gekoppelde rijen", "modern-image-manager"); ?></option>
          <option value="unlinked"><?php echo __("Alleen niet-gekoppelde rijen", "modern-image-manager"); ?></option>
        </select>
      </div>
      
      <!-- Kolommen selecteren met een multi-select dropdown -->
      <div class="form-group">
        <label for="exportColumns"><?php echo __("Selecteer kolommen", "modern-image-manager"); ?></label>
        <select id="exportColumns" name="export_columns[]" multiple class="custom-dropdown">
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
      <button type="submit" name="export_csv" class="page-button">
        <?php echo __("Exporteer naar CSV", "modern-image-manager"); ?>
      </button>
    </form>
  </div>
</body