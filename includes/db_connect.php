<?php
// Konfigurasi Database
define('DB_SERVER', 'localhost'); // Server database, biasanya localhost jika pakai XAMPP
define('DB_USERNAME', 'root');    // Username database, default XAMPP adalah root
define('DB_PASSWORD', '');        // Password database, default XAMPP adalah kosong
define('DB_NAME', 'ecommerce_db'); // Nama database yang akan kamu buat

// Membuat koneksi ke database MySQL
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Mengecek koneksi
if ($conn === false) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}
// Hapus baris echo ini setelah debugging selesai
// else {
//     echo "koneksi database berhasil";
// }
?>