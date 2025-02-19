<body class="data-page">
  <div class="page-container">
    <h1 class="page-title"><?php echo __("Importeren", "modern-image-manager"); ?></h1>
    <p class="page-description"><?php echo __("Selecteer een CSV-bestand om media te importeren.", "modern-image-manager"); ?></p>
    <form method="post" enctype="multipart/form-data" class="page-form">
      <input type="file" name="import_file" accept=".csv" required class="page-file-input" />
      <button type="submit" name="import_csv" class="page-button"><?php echo __("Importeer", "modern-image-manager"); ?></button>
    </form>
  </div>
</body>