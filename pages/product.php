<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$product = null;
$product_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : null;

if ($product_id) {
    // Ambil detail produk dari database, termasuk nama kategori
    $stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    // Produk tidak ditemukan, arahkan ke halaman kategori atau tampilkan pesan error
    header("Location: category.php");
    exit();
}

// Ubah string ukuran menjadi array untuk ditampilkan sebagai pilihan
$available_sizes = !empty($product['size_available']) ? explode(',', $product['size_available']) : [];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container product-detail">
        <div class="product-image">
            <?php if ($product['image_url']): ?>
                <img src="../public/uploads/<?php echo htmlspecialchars($product['image_url']); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <img src="../public/images/placeholder.jpg" alt="No Image">
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="brand">Brand: <?php echo htmlspecialchars($product['brand']); ?></p>
            <p class="category">Kategori: <a
                    href="category.php?category_id=<?php echo htmlspecialchars($product['category_id']); ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></a>
            </p>
            <p class="price">Harga: Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
            <p class="stock">Stok:
                <?php echo ($product['stock'] > 0) ? htmlspecialchars($product['stock']) : '<span class="out-of-stock">Habis</span>'; ?>
            </p>

            <div class="product-description">
                <h3>Deskripsi:</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <form action="cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">

                <?php if (!empty($available_sizes)): ?>
                    <div class="form-group">
                        <label for="size">Pilih Ukuran:</label>
                        <select id="size" name="selected_size" required>
                            <option value="">-- Pilih Ukuran --</option>
                            <?php foreach ($available_sizes as $size): ?>
                                <option value="<?php echo htmlspecialchars(trim($size)); ?>">
                                    <?php echo htmlspecialchars(trim($size)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <p class="info-text">Ukuran tidak tersedia.</p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="quantity">Jumlah:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1"
                        max="<?php echo htmlspecialchars($product['stock']); ?>" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?> required>
                </div>

                <button type="submit" name="add_to_cart" class="button button-primary" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                    <?php echo ($product['stock'] > 0) ? 'Tambah ke Keranjang' : 'Stok Habis'; ?>
                </button>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>