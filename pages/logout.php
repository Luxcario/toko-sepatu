<?php
// pages/logout.php

require_once '../includes/config.php'; // Ini akan memulai session_start()
require_once '../includes/auth.php';   // Ini berisi fungsi logout()

// Panggil fungsi logout untuk mengakhiri sesi dan mengarahkan pengguna
logout();

// Tidak ada kode HTML atau output lain di sini karena akan langsung redirect
?>