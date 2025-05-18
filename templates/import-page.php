<body class="data-page">
  <div class="page-container import-page-container">

    <h1 class="page-title import-page-title">
      <?php echo esc_html__('Importeren', 'modern-image-manager'); ?>
    </h1>

    <!-- Stap-indicator -->
    <div class="import-steps">
      <ol>
        <li><?php esc_html_e('Stap 1: Selecteer bestand', 'modern-image-manager'); ?></li>
        <li><?php esc_html_e('Stap 2: Valideer bestand', 'modern-image-manager'); ?></li>
        <li><?php esc_html_e('Stap 3: Importeer', 'modern-image-manager'); ?></li>
      </ol>
    </div>

    <p class="page-description import-page-description">
      <?php echo esc_html__('Selecteer een CSV-bestand om media te importeren.', 'modern-image-manager'); ?>
      <span class="tooltip" data-tooltip="<?php echo esc_attr__('ACF staat voor Advanced Custom Fields – een plugin voor het toevoegen van extra velden aan berichten.', 'modern-image-manager'); ?>">?</span>
    </p>

    <!-- Import-samenvatting (voor bevestiging) -->
    <div id="import-summary" class="import-summary" hidden></div>

    <!-- Loader tijdens import -->
    <div id="import-loader" class="import-loader" hidden>
      🔄 <em><?php esc_html_e('Importeren bezig... Een ogenblik geduld.', 'modern-image-manager'); ?></em>
    </div>

    <form method="post" enctype="multipart/form-data" class="page-form import-page-form">
      <!-- Drag & drop zone -->
      <div id="drag-drop-area" class="drag-drop-area">
        <p><?php esc_html_e('Sleep hier je CSV-bestand naartoe of klik om te selecteren', 'modern-image-manager'); ?></p>
        <input
          type="file"
          name="import_file"
          accept=".csv"
          required
          class="page-file-input import-file-input"
          aria-label="<?php esc_attr_e('CSV-bestand kiezen', 'modern-image-manager'); ?>"
        />
      </div>

      <button
        type="submit"
        name="import_csv"
        class="page-button import-button"
      >
        <?php esc_html_e('Importeer', 'modern-image-manager'); ?>
      </button>
    </form>
  </div>
</body>