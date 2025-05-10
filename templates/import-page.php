<body class="data-page">
  <div class="page-container import-page-container">
    <h1 class="page-title import-page-title">
      <?php echo esc_html__( 'Importeren', 'modern-image-manager' ); ?>
    </h1>
    <p class="page-description import-page-description">
      <?php echo esc_html__( 'Selecteer een CSV-bestand om media te importeren.', 'modern-image-manager' ); ?>
    </p>
    <form method="post" enctype="multipart/form-data" class="page-form import-page-form">
      <?php wp_nonce_field( 'modern_image_import', 'modern_image_import_nonce' ); ?>
      <input
        type="file"
        name="import_file"
        accept=".csv"
        required
        class="page-file-input import-file-input"
      />
      <button type="submit" name="import_csv" class="page-button import-button">
        <?php echo esc_html__( 'Importeer', 'modern-image-manager' ); ?>
      </button>
    </form>
  </div>
</body>