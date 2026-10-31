# Sistem Pinjaman Aset PDKT — Reka Bentuk Bahasa Melayu

Versi ini berdasarkan fail `PDKT.zip` yang diberikan, menggunakan PHP, MySQLi dan JavaScript. Antara muka diperbaharui dengan tema hijau zamrud dan emas, paparan responsif, menu mudah alih, ikon mengikut jenis aset dan label Bahasa Melayu. Semua aset reka bentuk disertakan secara tempatan; tiada sambungan CDN diperlukan.

## Kemas kini sistem sedia ada dalam Laragon

1. Hentikan sementara penggunaan sistem dan buat salinan folder `C:\laragon\www\PDKT` sebagai sandaran.
2. Dalam ZIP yang dimuat turun, buka folder projek dan cari folder **PDKT**. Salin kandungan folder PDKT baharu ke `C:\laragon\www\PDKT` dan gantikan fail paparan lama.
3. **Kekalkan fail `dbconnect.php` anda.** Fail tetapan sebenar tidak disertakan dalam muat turun baharu. `dbconnect.example.php` hanyalah contoh untuk pemasangan baharu. Jika perlu, salin contoh itu menjadi `dbconnect.php` dan masukkan tetapan MySQL anda sendiri.
4. Pastikan fail katalog dinamakan `staff/katalog.php`. Ia telah diseragamkan daripada `Katalog.php` supaya pautan turut berfungsi pada pelayan yang membezakan huruf besar dan kecil.
5. Dalam Laragon, mulakan pelayan web dan MySQL. Sistem memerlukan PHP 8.1 atau lebih baharu dengan sambungan `mysqli` dan `mbstring`.
6. Buka `http://localhost:100/PDKT/` jika pelayan web menggunakan port 100. Jika port 80, gunakan `http://localhost/PDKT/`.
7. Tekan **Ctrl + F5** untuk memuat semula fail gaya terkini. Log masuk menggunakan akaun sedia ada.

## Pangkalan data

**Tiada import SQL atau perubahan jadual diperlukan untuk kemas kini reka bentuk ini.** Gunakan pangkalan data sistem dalam ZIP asal yang mempunyai jadual `pengguna`, `aset`, `permohonan` dan `permohonan_aset`. Data dan kata laluan pengguna tidak ditukar. Fail SQL prototaip terdahulu yang menggunakan `users`, `equipment` dan `bookings` bukan skema untuk versi ini.

Contoh tetapan setempat ialah hos `127.0.0.1`, port MySQL `3308` dan pangkalan data `booking`. Gunakan nama pengguna dan kata laluan sebenar pemasangan Laragon anda; jangan muat naik tetapan sebenar ke GitHub.

## Perubahan paparan

- Laman utama dengan gambar pejabat, ringkasan ketersediaan dan panduan empat langkah.
- Paparan log masuk serta pendaftaran dengan susun atur baharu.
- Papan pemuka kakitangan dengan menu sisi, ringkasan status dan pautan permohonan baharu.
- Kad katalog dengan ikon komputer riba, kamera atau telefon apabila gambar aset tidak tersedia.
- Borang dan senarai permohonan dengan gaya yang konsisten.
- Menu mudah alih, fokus papan kekunci, pautan langkau kandungan dan sokongan kurang gerakan.
- Mesej pengesahan borang milik aplikasi dalam Bahasa Melayu. Bahasa pemilih tarikh atau antara muka pelayar ditentukan oleh tetapan peranti.

## Had yang dikekalkan daripada fail asal

Halaman pentadbir asal masih dalam pembinaan. Versi ini memperbaharui paparannya dan menerangkan status modul tersebut; tindakan kelulusan, pengurusan aset dan rekod pemulangan pentadbir belum dilaksanakan dalam fail yang diberikan. Penghantaran permohonan kakitangan kekal berfungsi. Tiada akaun pentadbir baharu atau kata laluan lalai ditambah.

## Pengesahan

Diuji menggunakan PHP 8.3 dan MariaDB 11.4 dengan pangkalan data ujian berasingan: semakan sintaks semua fail PHP, pendaftaran, log masuk, carian katalog, pemilihan aset, penghantaran permohonan, senarai permohonan, log keluar dan kawalan akses. Paparan desktop 1440 piksel dan telefon 390 piksel turut diuji, termasuk menu mudah alih dan halaman pentadbir. Pangkalan data Laragon pengguna tidak disentuh. Imej pratonton menggunakan data ujian, bukan rekod sebenar.
