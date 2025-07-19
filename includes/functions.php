<?php

// includes/functions.php

/**
 * Fungsi untuk membersihkan dan memvalidasi string input.
 * Menggunakan htmlspecialchars untuk mencegah XSS.
 *
 * @param string $data Input string dari pengguna.
 * @return string Data yang sudah dibersihkan.
 */
function sanitize_input($data)
{
    $data = trim($data); // Hapus spasi di awal dan akhir
    $data = stripslashes($data); // Hapus backslashes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Konversi karakter khusus ke entitas HTML
    return $data;
}

/**
 * Fungsi untuk memformat harga menjadi format mata uang Rupiah.
 *
 * @param float $price Harga dalam bentuk angka.
 * @return string Harga yang diformat dengan "Rp" dan tanda titik sebagai pemisah ribuan.
 */
function format_rupiah($price)
{
    return 'Rp ' . number_format($price, 2, ',', '.');
}

/**
 * Fungsi untuk mengarahkan pengguna ke URL tertentu.
 * Digunakan setelah operasi POST atau saat otorisasi.
 *
 * @param string $url URL tujuan.
 * @return void
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Fungsi untuk menampilkan pesan flash (sukses/error) yang disimpan di session.
 * Harus dipanggil setelah session_start() di config.php.
 *
 * @param string $type Tipe pesan ('success' atau 'error').
 * @param string $message Isi pesan.
 */
function set_flash_message($type, $message)
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

/**
 * Fungsi untuk mendapatkan dan menampilkan pesan flash, lalu menghapusnya dari session.
 *
 * @param string $type Tipe pesan ('success' atau 'error').
 * @return string HTML pesan atau string kosong jika tidak ada pesan.
 */
function get_flash_message($type)
{
    $output = '';
    if (isset($_SESSION['flash_messages'][$type])) {
        $output = '<p class="' . htmlspecialchars($type) . '">' . htmlspecialchars($_SESSION['flash_messages'][$type]) . '</p>';
        unset($_SESSION['flash_messages'][$type]); // Hapus pesan setelah ditampilkan
    }
    return $output;
}

// Anda bisa menambahkan fungsi-fungsi lain di masa mendatang, misalnya:
// function generate_unique_id($prefix = '') { ... }
// function validate_email($email) { ... }
// function upload_image($file_array, $target_dir) { ... }

?>