<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan pengguna sudah login
redirectIfNotLoggedIn('login.php');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$success_message = '';

// Ambil informasi pengguna
$user_info = null;
$stmt_user = $conn->prepare("SELECT email, phone_number, address FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows == 1) {
    $user_info = $result_user->fetch_assoc();
}
$stmt_user->close();

// Ambil riwayat pesanan pengguna
$orders = [];
$stmt_orders = $conn->prepare("SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
if ($result_orders->num_rows > 0) {
    while ($row = $result_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt_orders->close();

// Tangani pesan sukses dari checkout
if (isset($_GET['order_success']) && !empty($_GET['order_success'])) {
    $order_id_success = htmlspecialchars(filter_var($_GET['order_success'], FILTER_SANITIZE_NUMBER_INT));
    $success_message = '<p class="success">Pesanan Anda dengan nomor #' . $order_id_success . ' berhasil dibuat! Silakan cek detailnya di bawah.</p>';
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container my-account-page">
        <h2>Halo, <?php echo htmlspecialchars($username); ?>!</h2>
        <?php echo $success_message; ?>

        <div class="user-profile">
            <h3>Informasi Akun Anda</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email'] ?? 'N/A'); ?></p>
            <p><strong>Nomor Telepon:</strong> <?php echo htmlspecialchars($user_info['phone_number'] ?? 'N/A'); ?></p>
            <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($user_info['address'] ?? 'N/A')); ?></p>
        </div>

        <div class="order-history">
            <h3>Riwayat Pesanan Anda</h3>
            <?php if (empty($orders)): ?>
                <p>Anda belum memiliki riwayat pesanan.</p>
                <p><a href="category.php">Mulai belanja sekarang!</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Tanggal Pesan</th>
                            <th>Total Belanja</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                <td><span
                                        class="order-status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                </td>
                                <td><a href="order_detail.php?id=<?php echo htmlspecialchars($order['id']); ?>"
                                        class="button button-small">Lihat Detail</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>