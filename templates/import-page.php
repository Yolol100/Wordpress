<!-- import-page.php -->
<body class="data-page">
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

    <!-- Import-samenvatting (voor bevestiging) -->
    <div id="import-summary" class="import-summary" hidden></div>

    <!-- Loader tijdens import -->
    <div id="import-loader" class="import-loader" hidden>
      🔄 <em><?php esc_html_e('Importeren bezig... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
    </div>

    <form method="post" enctype="multipart/form-data" class="page-form import-page-form" id="import-form" novalidate autocomplete="off">
      <!-- Drag & drop zone -->
      <div id="drag-drop-area" class="drag-drop-area" tabindex="0" aria-label="<?php esc_attr_e('Sleep een CSV-bestand hierheen of klik om te selecteren', 'modern-image-manager'); ?>">
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

      <div style="display: flex; justify-content: center; margin-top: 0.7rem;">
        <button
          type="submit"
          name="import_csv"
          class="page-button import-button"
          id="import-button"
        >
          <?php esc_html_e('Importeer', 'modern-image-manager'); ?>
        </button>
      </div>
    </form>
  </div>
  <!-- Vergeet niet je JS-bestand te includen -->
  <script src="<?php echo esc_url( plugins_url( 'assets/js/import-media.js', __FILE__ ) ); ?>"></script>
</body>