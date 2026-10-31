# Ubah dan Padam Permohonan PDKT

## Pasang kemas kini

1. Sandarkan folder `C:\laragon\www\PDKT`.
2. Ekstrak `PDKT-kemas-kini-permohonan.zip`.
3. Salin kandungan folder **PDKT** di dalam ZIP ke folder PDKT sedia ada dan gantikan fail yang sama.
4. Tiga fail aplikasi dikemas kini: `staff/permohonan.php`, `staff/mohon.php` dan `staff/padam.php`.
5. Buka sistem dan tekan **Ctrl + F5**.

Fail sambungan pangkalan data dan fail sesi tidak diganti. Tiada import SQL atau perubahan jadual diperlukan. Kemas kini boleh digunakan dengan pengesahan asal atau sesi setiap tab yang telah dipasang sebelum ini.

## Cara menggunakan

- Di halaman **Permohonan Pinjaman**, permohonan berstatus **Menunggu** mempunyai butang **Ubah** dan **Padam**.
- Pilih **Ubah**. Maklumat serta senarai aset asal dimuatkan ke dalam borang.
- Untuk menukar aset, tekan butang buang pada aset lama, pilih aset baharu dan tekan **Tambah**. Anda juga boleh menukar tujuan, tempat digunakan dan tarikh.
- Tekan **Simpan Perubahan**. Nombor permohonan dikekalkan dan permohonan masih menunggu kelulusan.
- Pilih **Padam** untuk melihat halaman pengesahan. Pilih **Batal** untuk kembali tanpa memadam, atau **Ya, Padam Permohonan** untuk memadam secara kekal.

Hanya pemohon sendiri boleh mengubah atau memadam permohonan yang masih menunggu kelulusan. Permohonan yang sudah diproses tidak mempunyai butang tersebut, dan pelayan turut menolak cubaan akses terus. Tarikh mesti sah dan aset yang dipilih mesti tersedia serta tidak bertindih dengan pinjaman yang sudah diluluskan.

## Semakan

Sembilan pemeriksaan aliran Ubah/Padam lulus dalam pelayar Chromium menggunakan pangkalan data ujian berasingan. Ia meliputi pertukaran aset, nombor permohonan yang dikekalkan, CSRF, pemilikan rekod, sekatan selepas kelulusan, pengesahan, pembatalan dan pemadaman pautan aset. Semakan sesi setiap tab turut dijalankan semula. Pangkalan data Laragon pengguna tidak digunakan dalam ujian ini.
