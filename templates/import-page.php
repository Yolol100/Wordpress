<!-- import-page.php -->
<!-- import-page.php -->
<div class="data-page">
  <div class="page-container import-page-container">

    <!-- Stap-indicator -->
    <nav class="import-steps" aria-label="<?php esc_attr_e('Import stappen', 'modern-image-manager'); ?>">
      <ol>
        <li><span class="step-number">1</span> <?php esc_html_e('Selecteer bestand', 'modern-image-manager'); ?></li>
        <li><span class="step-number">2</span> <?php esc_html_e('Valideer bestand', 'modern-image-manager'); ?></li>
        <li><span class="step-number">3</span> <?php esc_html_e('Importeer', 'modern-image-manager'); ?></li>
      </ol>
    </nav>

    <!-- Titel en uitleg -->
    <h1 class="page-title import-page-title">
      <?php echo esc_html__('Media importeren', 'modern-image-manager'); ?>
    </h1>
    <p class="page-description import-page-description">
      <?php echo esc_html__('Selecteer een CSV-bestand om media te importeren.', 'modern-image-manager'); ?>
    </p>

    <!-- Meldingen boven formulier -->
    <?php if (!empty($notice_type) && !empty($notice_html)): ?>
      <div class="mim-notice mim-notice--<?php echo esc_attr($notice_type); ?>" role="alert" aria-live="assertive">
        <?php echo $notice_html; ?>
      </div>
    <?php endif; ?>

    <!-- Loader tijdens import -->
    <div id="import-loader" class="import-loader" hidden role="status" aria-live="polite" aria-busy="true">
      <em><?php esc_html_e('Importeren bezig... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
    </div>

    <form
      method="post"
      enctype="multipart/form-data"
      class="page-form import-page-form"
      id="import-form"
      novalidate
      autocomplete="off"
    >
      <?php wp_nonce_field('mim_import_csv', 'mim_import_nonce'); ?>
      <!-- Drag & drop zone -->
      <div
        id="drag-drop-area"
        class="drag-drop-area"
        tabindex="0"
        aria-label="<?php esc_attr_e('Sleep een CSV-bestand hierheen of klik om te selecteren', 'modern-image-manager'); ?>"
      >
        <p><?php esc_html_e('Sleep hier je CSV-bestand naartoe of klik om te selecteren', 'modern-image-manager'); ?></p>
        <input
          type="file"
          id="csv-file-input"
          name="import_file"
          accept=".csv"
          required
          class="page-file-input import-file-input"
          aria-label="<?php esc_attr_e('CSV-bestand kiezen', 'modern-image-manager'); ?>"
        />
        <div id="file-selected-name" style="margin-top: 0.6em; font-size: 0.95em; color: #007cba;"></div>
        <div id="file-error" class="file-error" role="alert" hidden tabindex="-1"></div>
      </div>

      <!-- Nieuw: Checkbox voor overschrijven met leegte -->
      <p style="text-align:center; margin:1em 0 0.5em 0;">
        <label>
          <input type="checkbox" name="overwrite_empty" value="1"
            <?php echo !empty($GLOBALS['mim_overwrite_empty']) ? 'checked' : ''; ?> />
          <?php esc_html_e('Lege waarden mogen bestaande data overschrijven', 'modern-image-manager'); ?>
        </label>
      </p>

      <div style="display: flex; justify-content: center; margin-top: 0.7rem;">
        <button
          type="submit"
          name="import_csv"
          class="page-button import-button"
          id="import-button"
          aria-label="<?php esc_attr_e('Start importeren van CSV-bestand', 'modern-image-manager'); ?>"
        >
          <?php esc_html_e('Importeer', 'modern-image-manager'); ?>
        </button>
      </div>
    </form>
  </div>

</div>