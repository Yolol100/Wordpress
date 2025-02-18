<body class="data-page">
  <div class="page-container">
    <h1 class="page-title">Exporteren</h1>
    <p class="page-description">
      Selecteer een exportoptie en klik op de knop om de CSV te genereren.
    </p>
    <form method="post" action="" class="page-form">
      <select name="export_option" id="export-option" class="custom-dropdown">
        <option value="all">Alle pagina’s</option>
        <option value="linked">Alleen gekoppelde rijen</option>
        <option value="unlinked">Alleen niet-gekoppelde rijen</option>
      </select>
      <button type="submit" name="export_csv" class="page-button">
        Exporteer naar CSV
      </button>
    </form>
  </div>
</body>