<?php
// Pastikan session_start() sudah dipanggil di config.php
require_once 'config.php'; // Jika belum di config.php
require_once 'auth.php'; // Sertakan file auth.php
?>
<header>
    <nav>
        <div class="logo">
            <a href="../public/index.php">Toko Sepatu</a>
        </div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Beranda</a></li>
            <li><a href="../pages/category.php">Produk</a></li>
            <li><a href="../pages/contact.php">Kontak</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="../pages/my_account.php">Akun Saya (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </li>
                <?php if (isAdmin()): ?>
                    <li><a href="../admin/index.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="../pages/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="../pages/login.php">Login</a></li>
                <li><a href="../pages/register.php">Daftar</a></li>
            <?php endif; ?>
            <li><a href="../pages/cart.php">Keranjang</a></li>
        </ul>
    </nav>
</header>