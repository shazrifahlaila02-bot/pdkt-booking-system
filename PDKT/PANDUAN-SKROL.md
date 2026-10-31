# Pembaikan Pautan Skrol PDKT

Pembaikan ini membolehkan pautan **Cara Pinjam**, **Katalog Aset** dan **Lihat Katalog** menatal terus ke bahagian yang dipilih tanpa memuat semula halaman. Pautan terus dengan `#katalog`, hasil carian serta butang Kembali/Maju pelayar turut dikendalikan. Kedudukan tajuk mengambil kira menu yang melekat di bahagian atas.

## Cara memasang

1. Sandarkan fail `C:\laragon\www\PDKT\assets\js\sesi-tab.js`.
2. Ekstrak `PDKT-kemas-kini-skrol.zip`.
3. Salin folder **PDKT** di dalam ZIP ke `C:\laragon\www` dan gantikan fail `assets\js\sesi-tab.js` yang sama.
4. Kembali ke sistem dan tekan **Ctrl + F5**.
5. Tekan **Cara Pinjam**, **Katalog Aset** dan **Lihat Katalog** untuk mencuba.

Hanya fail JavaScript sesi dikemas kini. Tiada perubahan pangkalan data, import SQL atau penggantian fail `dbconnect.php` diperlukan. Fungsi Ubah/Padam serta sesi berasingan setiap tab dikekalkan.

## Semakan

Tujuh pemeriksaan skrol dalam Chromium lulus: dua pautan menu, butang Kembali/Maju, pautan ke sasaran sama, pautan terus, carian katalog dan paparan telefon. Ujian memastikan pautan bahagian halaman tidak menghantar permintaan halaman baharu. Dua belas pemeriksaan sesi setiap tab turut dijalankan semula menggunakan pangkalan data ujian berasingan.
