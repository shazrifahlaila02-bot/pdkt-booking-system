# PDKT — Sesi berasingan setiap tab

Kemas kini ini berdasarkan ZIP PDKT yang dimuat naik dan mengekalkan paparan asalnya.

**[Muat turun pakej kemas kini sesi](https://github.com/shazrifahlaila02-bot/pdkt-booking-system/raw/refs/heads/sesi-setiap-tab/PDKT-kemas-kini-sesi.zip)**

1. Sandarkan folder `C:\laragon\www\PDKT`.
2. Ekstrak ZIP kemas kini dan salin kandungan folder **PDKT** di dalamnya ke folder PDKT sedia ada. Gantikan fail yang sama.
3. Kekalkan `dbconnect.php` anda. Tiada import SQL diperlukan.
4. Tutup tab PDKT lama, buka `http://localhost:100/PDKT/` dan tekan **Ctrl + F5**.

Setiap tab baharu meminta log masuk. Muat semula atau navigasi dalam tab yang sama mengekalkan sesi. Sesi tamat selepas 15 minit tanpa aktiviti. Log keluar menamatkan tab berkenaan sahaja.

[Panduan lengkap](PDKT/PANDUAN-SESI.md) · [Keputusan 12 pemeriksaan sesi](PDKT/SEMAKAN-SESI.md)

Pakej ZIP hanya mengandungi enam fail kemas kini; ia tidak mengandungi tetapan sambungan pangkalan data sebenar, rekod pangkalan data atau fail teks kata laluan. Folder PDKT mengandungi sumber versi lengkap, manakala fail prototaip lama di akar repositori dikekalkan.
