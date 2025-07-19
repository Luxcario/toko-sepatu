<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn('login.php');

$order = null;
$order_items = [];
$order_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : null;
$user_id = $_SESSION['user_id'];

if ($order_id) {
    // Ambil detail pesanan
    $stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt_order->bind_param("ii", $order_id, $user_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    if ($result_order->num_rows == 1) {
        $order = $result_order->fetch_assoc();
    }
    $stmt_order->close();

    // Jika pesanan ditemukan, ambil item-itemnya
    if ($order) {
        $stmt_items = $conn->prepare("SELECT oi.*, p.name AS product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        if ($result_items->num_rows > 0) {
            while ($row = $result_items->fetch_assoc()) {
                $order_items[] = $row;
            }
        }
        $stmt_items->close();
    }
}

if (!$order) {
    // Pesanan tidak ditemukan atau bukan milik pengguna ini
    header("Location: my_account.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo htmlspecialchars($order['id']); ?> - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <main class="container order-detail-page">
        <h2>Detail Pesanan #<?php echo htmlspecialchars($order['id']); ?></h2>
        <p><strong>Tanggal Pesan:</strong> <?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></p>
        <p><strong>Total Pesanan:</strong> Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></p>
        <p><strong>Status:</strong> <span
                class="order-status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
        </p>
        <p><strong>Alamat Pengiriman:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <?php if (!empty($order['tracking_number'])): ?>
            <p><strong>Nomor Resi:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
        <?php endif; ?>

        <h3>Item Pesanan:</h3>
        <?php if (empty($order_items)): ?>
            <p>Tidak ada item dalam pesanan ini.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Ukuran</th>
                        <th>Harga Satuan</th>
                        <th>Kuantitas</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="order-item-product-info">
                                <?php if ($item['image_url']): ?>
                                    <img src="../public/uploads/<?php echo htmlspecialchars($item['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <?php endif; ?>
                                <a
                                    href="product.php?id=<?php echo htmlspecialchars($item['product_id']); ?>"><?php echo htmlspecialchars($item['product_name']); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($item['selected_size']); ?></td>
                            <td>Rp <?php echo number_format($item['price_at_purchase'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>Rp <?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="my_account.php" class="button button-secondary">Kembali ke Akun Saya</a></p>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>