<?php
// includes/config.php

ini_set('display_errors', 1); // Aktifkan display error untuk debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Pastikan baris ini ada dan di paling atas!

// Pengaturan zona waktu
date_default_timezone_set('Asia/Jakarta');

// ... konfigurasi lainnya jika ada
?>