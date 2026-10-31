# Keputusan semakan sesi

Semakan dijalankan dalam pelayar Chromium menggunakan PHP 8.3 dan pangkalan data ujian MariaDB 11.4. Pangkalan data pengguna tidak digunakan.

- Lulus: Pendaftaran dan log masuk berfungsi dengan sesi tab.
- Lulus: Muat semula tab asal mengekalkan sesi.
- Lulus: Tab baharu meminta log masuk walaupun kuki pelayar dikongsi.
- Lulus: Dua tab boleh log masuk secara berasingan.
- Lulus: Tab yang mewarisi sessionStorage juga meminta log masuk.
- Lulus: Navigasi dan carian GET mengekalkan token tab.
- Lulus: Borang permohonan POST dan CSRF berfungsi.
- Lulus: Permintaan tanpa token tab tidak menerima data akaun.
- Lulus: Log keluar menamatkan tab berkenaan sahaja.
- Lulus: Pembaharuan aktiviti memerlukan CSRF yang sah; semakan GET tidak memanjangkan sesi.
- Lulus: Sesi tamat selepas 15 minit dan tab beralih automatik ke log masuk; semakan latar tidak menghidupkannya semula.
- Lulus: Permintaan dilindungi selepas tamat sesi ditolak.

Tiada ralat JavaScript dikesan. Semua fail PHP turut lulus semakan sintaks. Tamat tempoh pelayan diuji dengan mengundurkan cap masa sesi ujian; pengalihan automatik pelayar kemudian turut disahkan.
