# Kemas Kini Sesi Setiap Tab — PDKT

Kemas kini ini menggunakan sistem dalam ZIP asal anda dan mengekalkan reka bentuk serta struktur pangkalan datanya.

## Cara memasang dalam Laragon

1. Buat salinan folder `C:\laragon\www\PDKT` sebagai sandaran.
2. Ekstrak **PDKT-kemas-kini-sesi.zip**. Di dalamnya terdapat folder **PDKT**.
3. Salin kandungan folder PDKT tersebut ke folder PDKT sedia ada dan pilih untuk menggantikan fail yang sama.
4. Pastikan fail ini wujud selepas salinan:
   - `includes/auth.php`
   - `assets/js/sesi-tab.js`
   - `login.php`
   - `logout.php`
   - `session.php`
5. Kekalkan fail `dbconnect.php` anda. Tiada import SQL atau perubahan pangkalan data diperlukan.
6. Tutup tab PDKT lama, buka `http://localhost:100/PDKT/` dan tekan **Ctrl + F5**. Pengguna perlu log masuk semula sekali selepas kemas kini.

## Tingkah laku sesi

- Setiap tab baharu meminta log masuk sendiri, walaupun tab lain sudah log masuk.
- Tab pendua yang mewarisi storan tab juga dikesan apabila tab asal masih terbuka.
- Muat semula halaman atau berpindah halaman dalam tab yang sama mengekalkan sesi yang masih sah.
- Sesi tamat selepas **15 minit tanpa aktiviti**. Aktiviti papan kekunci, klik, tatal atau pengisian borang dalam tab aktif memperbaharui sesi secara berkala.
- Selepas tamat sesi, akses halaman terlindung ditolak oleh pelayan. Tab akan kembali ke halaman log masuk apabila semakan seterusnya berjalan.
- Log keluar menamatkan sesi **tab berkenaan sahaja**. Tab lain yang telah log masuk secara berasingan tidak terjejas.
- JavaScript, kuki dan storan sesi perlu dibenarkan. Gunakan Chrome, Edge atau Firefox versi terkini.

## Cara menguji sendiri

1. Log masuk dalam tab pertama dan muat semula halaman; akaun masih boleh digunakan.
2. Buka tab baharu dan taip alamat PDKT; tab baharu meminta log masuk.
3. Cuba buka alamat `http://localhost:100/PDKT/staff/dashboard.php` terus pada tab baharu; ia juga meminta log masuk.
4. Log masuk pada tab kedua, kemudian log keluar pada tab pertama; sesi tab kedua kekal sah.
5. Biarkan tab tidak aktif selama 15 minit. Semasa digunakan kembali, sesi yang tamat memerlukan log masuk semula.

## Catatan teknikal

Sesi PHP berasingan dipilih melalui token rawak setiap tab. Token dihantar sebagai pengepala permintaan, bukan melalui URL. Kuki sesi menggunakan HttpOnly dan tidak menyimpan kata laluan. Pelayan tidak menghantar kandungan akaun tanpa pengesahan tab. Mesej saluran pelayar mengesan token yang diwarisi tab pendua. Semakan latar tanpa aktiviti tidak memanjangkan sesi. Borang sedia ada dan perlindungan CSRF dikekalkan.

Fail teks akaun/kata laluan daripada ZIP asal dan tetapan pangkalan data sebenar tidak dimasukkan dalam pakej muat turun. Kemas kini tidak mewujudkan atau menukar akaun pengguna.
