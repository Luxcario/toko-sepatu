<?php

// include/auth.php
// Hapus blok kode ini dari sini:
// if (isset($_SESSION['user_id'])) {
//     if ($_SESSION['role'] === 'admin') {
//         header("Location: ../admin/index.php");
//     } else {
//         header("Location: my_account.php");
//     }
//     exit();
// }

// Fungsi untuk memeriksa apakah pengguna sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Fungsi untuk memeriksa apakah pengguna adalah admin
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // Tambah isset($_SESSION['role'])
}

// Fungsi untuk mengarahkan pengguna jika belum login
function redirectIfNotLoggedIn($redirect_url = 'login.php')
{
    if (!isLoggedIn()) {
        header("Location: " . $redirect_url);
        exit();
    }
}

// Fungsi untuk mengarahkan jika bukan admin
function redirectIfNotAdmin($redirect_url = '../pages/login.php')
{
    if (!isAdmin()) {
        header("Location: " . $redirect_url);
        exit();
    }
}

// Fungsi untuk logout
function logout()
{
    session_unset();
    session_destroy();
    header("Location: ../pages/login.php"); // Arahkan ke halaman login setelah logout
    exit();
}

?>