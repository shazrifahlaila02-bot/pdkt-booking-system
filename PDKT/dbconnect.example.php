<?php
// Tetapan contoh. Salin sebagai dbconnect.php dan isi tetapan MySQL anda.
$hostname = "127.0.0.1";
$port     = 3308;
$username = "root";
$password = ""; // Isi sendiri kata laluan MySQL setempat jika diperlukan.
$dbname   = "booking";

// ---------- Timezone (so dates/times match Malaysia) ----------
date_default_timezone_set("Asia/Kuala_Lumpur");

// ---------- Make mysqli throw exceptions instead of silent warnings ----------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create the connection
    $dbcon = mysqli_connect($hostname, $username, $password, $dbname, $port);

    // Use utf8mb4 so special characters save correctly
    mysqli_set_charset($dbcon, "utf8mb4");

    // Keep MySQL's timezone in line with PHP (Malaysia = UTC+8)
    mysqli_query($dbcon, "SET time_zone = '+08:00'");

} catch (mysqli_sql_exception $e) {
    // Log the real error for you, show only a generic message to users
    error_log("DB connection failed: " . $e->getMessage());
    die("Maaf, sistem tidak dapat diakses buat sementara waktu. Sila cuba lagi sebentar nanti.");
}

// echo "DB connected successfully";   // uncomment only for testing
?>
