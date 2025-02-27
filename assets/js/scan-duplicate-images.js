document.addEventListener('DOMContentLoaded', function() {
    var scanForm = document.querySelector('.mim-scan-page-form');
    if (scanForm) {
        scanForm.addEventListener('submit', function(e) {
            var intro = document.getElementById('mim-scan-intro');
            if (intro) {
                intro.style.display = 'none';
            }
        });
    }
});