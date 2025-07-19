<?php
require_once '../includes/config.php'; // Pastikan session_start() ada di sini
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// --- Handle Add to Cart ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    $selected_size = trim($_POST['selected_size']);

    if ($product_id && $quantity > 0 && !empty($selected_size)) {
        // Ambil detail produk dari database
        $stmt = $conn->prepare("SELECT id, name, price, stock, image_url FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            // Cek stok
            if ($quantity > $product['stock']) {
                $message = '<p class="error">Maaf, stok untuk produk ' . htmlspecialchars($product['name']) . ' tidak mencukupi. Stok tersedia: ' . htmlspecialchars($product['stock']) . '</p>';
            } else {
                // Buat unique key untuk item di keranjang (product_id + size)
                $cart_item_key = $product_id . '_' . $selected_size;

                if (isset($_SESSION['cart'][$cart_item_key])) {
                    // Jika produk dengan ukuran yang sama sudah ada di keranjang, update kuantitas
                    $_SESSION['cart'][$cart_item_key]['quantity'] += $quantity;
                } else {
                    // Tambahkan produk baru ke keranjang
                    $_SESSION['cart'][$cart_item_key] = [
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image_url' => $product['image_url'],
                        'quantity' => $quantity,
                        'selected_size' => $selected_size,
                        'stock_available' => $product['stock'] // Simpan stok saat ini untuk validasi nanti
                    ];
                }
                $message = '<p class="success">Produk berhasil ditambahkan ke keranjang!</p>';
            }
        } else {
            $message = '<p class="error">Produk tidak ditemukan.</p>';
        }
    } else {
        $message = '<p class="error">Gagal menambahkan produk ke keranjang. Pastikan memilih ukuran dan kuantitas.</p>';
    }
}

// --- Handle Update Quantity ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_item_key => $new_quantity) {
        $new_quantity = filter_var($new_quantity, FILTER_SANITIZE_NUMBER_INT);
        if (isset($_SESSION['cart'][$cart_item_key]) && $new_quantity >= 0) {
            // Cek stok saat update
            if ($new_quantity > $_SESSION['cart'][$cart_item_key]['stock_available']) {
                $message = '<p class="error">Maaf, stok untuk ' . htmlspecialchars($_SESSION['cart'][$cart_item_key]['name']) . ' ukuran ' . htmlspecialchars($_SESSION['cart'][$cart_item_key]['selected_size']) . ' tidak mencukupi. Stok tersedia: ' . htmlspecialchars($_SESSION['cart'][$cart_item_key]['stock_available']) . '</p>';
                // Set kuantitas ke stok maksimal yang tersedia
                $_SESSION['cart'][$cart_item_key]['quantity'] = $_SESSION['cart'][$cart_item_key]['stock_available'];
            } else {
                $_SESSION['cart'][$cart_item_key]['quantity'] = $new_quantity;
            }

            // Hapus item jika kuantitas 0
            if ($_SESSION['cart'][$cart_item_key]['quantity'] == 0) {
                unset($_SESSION['cart'][$cart_item_key]);
                $message = '<p class="success">Produk berhasil dihapus dari keranjang.</p>';
            } else {
                $message = '<p class="success">Kuantitas keranjang berhasil diperbarui.</p>';
            }
        }
    }
}

// --- Handle Remove Item ---
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['key'])) {
    $cart_item_key_to_remove = $_GET['key'];
    if (isset($_SESSION['cart'][$cart_item_key_to_remove])) {
        unset($_SESSION['cart'][$cart_item_key_to_remove]);
        $message = '<p class="success">Produk berhasil dihapus dari keranjang.</p>';
    } else {
        $message = '<p class="error">Item tidak ditemukan di keranjang.</p>';
    }
}

// --- Hitung Total Keranjang ---
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container cart-page">
        <h2>Keranjang Belanja Anda</h2>
        <?php echo $message; // Tampilkan pesan sukses/error ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <p>Keranjang belanja Anda kosong. <a href="category.php">Mulai belanja sekarang!</a></p>
        <?php else: ?>
            <form action="cart.php" method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Ukuran</th>
                            <th>Harga</th>
                            <th>Kuantitas</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                            <tr>
                                <td class="cart-product-info">
                                    <?php if ($item['image_url']): ?>
                                        <img src="../public/uploads/<?php echo htmlspecialchars($item['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <img src="../public/images/placeholder.jpg" alt="No Image">
                                    <?php endif; ?>
                                    <a
                                        href="product.php?id=<?php echo htmlspecialchars($item['product_id']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                </td>
                                <td><?php echo htmlspecialchars($item['selected_size']); ?></td>
                                <td>Rp <?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                                <td>
                                    <input type="number" name="quantities[<?php echo htmlspecialchars($key); ?>]"
                                        value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1"
                                        max="<?php echo htmlspecialchars($item['stock_available']); ?>">
                                </td>
                                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="cart.php?action=remove&key=<?php echo htmlspecialchars($key); ?>"
                                        class="button button-small button-danger"
                                        onclick="return confirm('Hapus produk ini dari keranjang?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-actions">
                    <button type="submit" name="update_cart" class="button button-secondary">Perbarui Keranjang</button>
                </div>
            </form>

            <div class="cart-summary">
                <h3>Ringkasan Belanja</h3>
                <p>Total: <span>Rp <?php echo number_format($cart_total, 2, ',', '.'); ?></span></p>
                <a href="checkout.php" class="button button-primary">Lanjutkan ke Checkout</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>