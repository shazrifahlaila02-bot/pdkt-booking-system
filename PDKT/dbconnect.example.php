<?php
// Salin sebagai dbconnect.php untuk pemasangan baharu sahaja.
// Jika mengemas kini, kekalkan dbconnect.php anda sendiri.
date_default_timezone_set('Asia/Kuala_Lumpur');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $dbcon = mysqli_connect('127.0.0.1', 'root', '', 'booking', 3308);
    mysqli_set_charset($dbcon, 'utf8mb4');
    mysqli_query($dbcon, "SET time_zone = '+08:00'");
} catch (mysqli_sql_exception $error) {
    error_log('Sambungan pangkalan data gagal.');
    die('Maaf, sistem tidak dapat diakses buat sementara waktu.');
}
