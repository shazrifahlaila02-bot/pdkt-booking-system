# PDKT — Pembaikan pautan skrol

**[Muat turun pembaikan skrol](https://github.com/shazrifahlaila02-bot/pdkt-booking-system/raw/refs/heads/baiki-skrol/PDKT-kemas-kini-skrol.zip)**

Ekstrak ZIP dan salin folder **PDKT** ke `C:\laragon\www`, kemudian gantikan fail `assets\js\sesi-tab.js` yang sama. Buka sistem dan tekan **Ctrl + F5**.

Pautan **Cara Pinjam**, **Katalog Aset** dan **Lihat Katalog** kini menatal ke sasaran tanpa memuat semula halaman. Pautan terus, carian katalog dan navigasi Kembali/Maju turut dikendalikan. Tiada import SQL diperlukan.

[Panduan skrol](PDKT/PANDUAN-SKROL.md)

---

# PDKT — Ubah dan Padam Permohonan

**[Muat turun kemas kini Ubah/Padam](https://github.com/shazrifahlaila02-bot/pdkt-booking-system/raw/refs/heads/ubah-padam-permohonan/PDKT-kemas-kini-permohonan.zip)**

Sandarkan folder PDKT, kemudian salin kandungan folder PDKT dalam ZIP ke `C:\laragon\www\PDKT` dan gantikan fail yang sama. Tekan **Ctrl + F5** selepas pemasangan. Tiada import SQL diperlukan; fail tetapan pangkalan data dan sesi tidak diganti.

Butang **Ubah** dan **Padam** ditambah pada permohonan sendiri yang masih **Menunggu**. Ubah membolehkan pertukaran aset, tujuan, tempat serta tarikh sambil mengekalkan nombor permohonan. Padam memerlukan pengesahan pada halaman berasingan. Akses ke permohonan pengguna lain serta perubahan selepas kelulusan ditolak oleh pelayan.

[Panduan penggunaan](PDKT/PANDUAN-UBAH-PADAM.md)

---

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
