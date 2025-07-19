<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php'; // Mungkin tidak diperlukan jika hanya statis
require_once '../includes/functions.php'; // Mungkin tidak diperlukan
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container static-page">
        <h2>Tentang Toko Sepatu Kami</h2>
        <p>Selamat datang di <strong>Toko Sepatu Online</strong>, destinasi terbaik Anda untuk menemukan koleksi sepatu
            terbaru dan terlengkap!</p>

        <p>Kami percaya bahwa setiap langkah adalah perjalanan, dan setiap perjalanan dimulai dengan sepatu yang tepat.
            Sejak didirikan pada tahun 2023, kami berdedikasi untuk menyediakan sepatu berkualitas tinggi dari berbagai
            *brand* ternama, mulai dari *sneakers* trendi, sepatu lari performa tinggi, hingga sepatu formal yang
            elegan.</p>

        <h3>Visi Kami</h3>
        <p>Menjadi toko sepatu *online* terdepan yang menginspirasi setiap individu untuk melangkah dengan percaya diri
            dan gaya, didukung oleh koleksi sepatu terbaik dan pengalaman belanja yang tak tertandingi.</p>

        <h3>Misi Kami</h3>
        <ul>
            <li>Menyediakan beragam pilihan sepatu dari *brand* lokal maupun internasional.</li>
            <li>Menjamin kualitas dan keaslian setiap produk yang kami jual.</li>
            <li>Memberikan pelayanan pelanggan yang responsif dan personal.</li>
            <li>Memastikan proses belanja yang mudah, aman, dan nyaman dari awal hingga akhir.</li>
        </ul>

        <p>Tim kami adalah para penggemar sepatu yang bersemangat, selalu siap membantu Anda menemukan pasangan yang
            sempurna untuk gaya hidup dan kebutuhan Anda. Terima kasih telah memilih Toko Sepatu Online sebagai mitra
            perjalanan Anda.</p>

        <p>Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi kami melalui halaman <a
                href="contact.php">Kontak</a>.</p>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>