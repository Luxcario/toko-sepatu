<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php'; // Opsional, jika ingin menampilkan status login di home
require_once '../includes/functions.php'; // Jika ada fungsi helper

// Ambil beberapa produk terbaru (misal 8 produk)
$products = [];
$stmt = $conn->prepare("SELECT id, name, price, image_url, brand FROM products ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Toko Sepatu Online</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main>
        <section class="hero-section">
            <h1>Temukan Sepatu Impianmu</h1>
            <p>Koleksi terbaru dari brand terbaik, hanya untukmu.</p>
            <a href="../pages/category.php" class="button button-primary">Belanja Sekarang</a>
        </section>

        <section class="featured-products container">
            <h2>Produk Terbaru</h2>
            <?php if (empty($products)): ?>
                <p>Belum ada produk yang tersedia.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="../pages/product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                                <?php if ($product['image_url']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($product['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <img src="images/placeholder.jpg" alt="No Image" style="max-width: 100px; height: auto;">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                                <p class="price">Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>

</html>