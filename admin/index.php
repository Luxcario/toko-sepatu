<?php
require_once '../includes/config.php'; // Memulai session_start()
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

redirectIfNotAdmin('../pages/login.php'); // Arahkan ke halaman login jika bukan admin

// Sisanya adalah konten dashboard admin
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/global.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="admin-main">
        <div class="admin-sidebar">
            <h3>Admin Menu</h3>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php">Kelola Produk</a></li>
                <li><a href="categories.php">Kelola Kategori</a></li>
                <li><a href="orders.php">Kelola Pesanan</a></li>
                <li><a href="users.php">Kelola Pengguna</a></li>
                <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="admin-content">
            <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Ini adalah dashboard admin Anda. Gunakan menu di samping untuk mengelola toko.</p>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>