<body class="data-page">
  <div class="page-container import-page-container">
    <h1 class="page-title import-page-title">
      <?php echo esc_html__('Importeren', 'modern-image-manager'); ?>
    </h1>

    <p class="page-description import-page-description">
      <?php echo esc_html__('Selecteer een CSV-bestand om media te importeren.', 'modern-image-manager'); ?>
    </p>

    <form method="post" enctype="multipart/form-data" class="page-form import-page-form">
      <input
        type="file"
        name="import_file"
        accept=".csv"
        required
        class="page-file-input import-file-input"
        aria-label="<?php esc_attr_e('CSV-bestand kiezen', 'modern-image-manager'); ?>"
      />

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