<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan pengguna sudah login
redirectIfNotLoggedIn('login.php');

// Pastikan keranjang tidak kosong
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$error_message = '';
$success_message = '';
$cart_total = 0;

// Hitung ulang total keranjang
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

// Ambil data pengguna dari database untuk mengisi form awal
$user_id = $_SESSION['user_id'];
$user_info = null;
$stmt_user = $conn->prepare("SELECT username, email, phone_number, address FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows == 1) {
    $user_info = $result_user->fetch_assoc();
}
$stmt_user->close();


// --- Handle Checkout Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $shipping_address = sanitize_input($_POST['shipping_address']); // Menggunakan sanitize_input
    $payment_method = sanitize_input($_POST['payment_method']); // Menggunakan sanitize_input

    if (empty($shipping_address) || empty($payment_method)) {
        $error_message = "Alamat pengiriman dan metode pembayaran harus diisi.";
    } else {
        // Validasi stok terakhir kali sebelum memesan
        $all_items_in_stock = true;
        foreach ($_SESSION['cart'] as $key => $item) {
            $stmt_stock_check = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt_stock_check->bind_param("i", $item['product_id']);
            $stmt_stock_check->execute();
            $result_stock = $stmt_stock_check->get_result();
            $current_stock = $result_stock->fetch_assoc()['stock'] ?? 0;
            $stmt_stock_check->close();

            if ($item['quantity'] > $current_stock) {
                $error_message = "Maaf, stok untuk produk '" . htmlspecialchars($item['name']) . "' ukuran " . htmlspecialchars($item['selected_size']) . " tidak mencukupi. Stok tersedia: " . htmlspecialchars($current_stock) . " unit.";
                $all_items_in_stock = false;
                break;
            }
        }

        if ($all_items_in_stock) {
            // Mulai transaksi database (untuk memastikan semua operasi berhasil atau tidak sama sekali)
            $conn->begin_transaction();
            try {
                // 1. Masukkan pesanan ke tabel 'orders'
                $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) VALUES (?, ?, ?, ?, ?)");
                $status = 'pending';
                // PERBAIKAN PENTING DI SINI: ganti "idss" menjadi "idsss"
                $stmt_order->bind_param("idsss", $user_id, $cart_total, $status, $shipping_address, $payment_method); // Baris 70
                $stmt_order->execute();
                $order_id = $conn->insert_id; // Dapatkan ID pesanan yang baru dibuat
                $stmt_order->close();

                // 2. Masukkan item pesanan ke tabel 'order_items' dan kurangi stok produk
                foreach ($_SESSION['cart'] as $item) {
                    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, selected_size) VALUES (?, ?, ?, ?, ?)");
                    $stmt_item->bind_param("iiids", $order_id, $item['product_id'], $item['quantity'], $item['price'], $item['selected_size']);
                    $stmt_item->execute();
                    $stmt_item->close();

                    // Kurangi stok produk
                    $stmt_stock_update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt_stock_update->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt_stock_update->execute();
                    $stmt_stock_update->close();
                }

                // Jika semua berhasil, commit transaksi
                $conn->commit();
                $_SESSION['cart'] = []; // Kosongkan keranjang setelah pesanan berhasil
                $success_message = "Pesanan Anda berhasil dibuat! Nomor pesanan: #" . $order_id;
                // Redirect ke halaman konfirmasi atau akun saya setelah sukses
                header("Location: my_account.php?order_success=" . $order_id);
                exit();

            } catch (Exception $e) {
                // Jika ada error, rollback transaksi
                $conn->rollback();
                $error_message = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
                // Untuk debugging: error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container checkout-page">
        <h2>Checkout Pesanan</h2>
        <?php echo $error_message; ?>
        <?php echo $success_message; ?>

        <?php if (!empty($_SESSION['cart'])): ?>
            <div class="checkout-summary">
                <h3>Ringkasan Pesanan</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Ukuran</th>
                            <th>Harga Satuan</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['selected_size']); ?></td>
                                <td>Rp <?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"><strong>Total Pesanan:</strong></td>
                            <td><strong>Rp <?php echo number_format($cart_total, 2, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <form action="checkout.php" method="POST" class="checkout-form">
                <h3>Informasi Pengiriman</h3>
                <div class="form-group">
                    <label for="shipping_address">Alamat Pengiriman Lengkap:</label>
                    <textarea id="shipping_address" name="shipping_address" rows="5"
                        required><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="phone_number">Nomor Telepon:</label>
                    <input type="text" id="phone_number" name="phone_number"
                        value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>" required>
                    <small>Nomor ini akan digunakan untuk konfirmasi pesanan dan pengiriman.</small>
                </div>

                <h3>Metode Pembayaran</h3>
                <div class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="COD" required> Cash On Delivery (COD)
                    </label><br>
                    <label>
                        <input type="radio" name="payment_method" value="Bank Transfer"> Transfer Bank (Konfirmasi Manual)
                    </label>
                </div>

                <button type="submit" name="place_order" class="button button-primary large-button">Konfirmasi
                    Pesanan</button>
            </form>
        <?php else: ?>
            <p>Keranjang belanja Anda kosong. Tidak dapat melanjutkan checkout.</p>
            <p><a href="category.php">Kembali ke toko</a> atau <a href="cart.php">Lihat Keranjang</a></p>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>