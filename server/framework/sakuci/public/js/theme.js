/*
| Toggle tema gelap/terang lewat klik logo (#themeToggle). Warna cincin logo
| otomatis mengikuti [data-bs-theme] lewat CSS (lihat .logo-ring di app.css),
| jadi di sini cukup ganti atribut & simpan pilihannya.
|
| Pemilihan awal (sebelum file ini dimuat) sudah diterapkan lewat script
| kecil di <head> supaya tidak ada flash warna -- lihat layouts/app.sakuci.php.
*/
(function () {
    var STORAGE_KEY = 'sakuci-theme';
    var root = document.documentElement;

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('themeToggle');

        if (! button) {
            return;
        }

        button.addEventListener('click', function () {
            var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';

            root.setAttribute('data-bs-theme', next);
            localStorage.setItem(STORAGE_KEY, next);
        });
    });
})();
