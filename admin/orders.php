<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotAdmin('../pages/login.php');

$message = '';
$order_to_view = null;
$order_items_view = [];

// --- Handle Update Order Status ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    // Debugging: AKTIFKAN INI UNTUK MELIHAT DATA POST YANG DITERIMA
    // echo "<pre>"; print_r($_POST); echo "</pre>";
    // echo "<p>Debug: Masuk ke update_order_status block.</p>";

    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = trim($_POST['new_status']);
    // Ambil tracking_number_input HANYA JIKA ADA di $_POST
    $tracking_number_input_sent = isset($_POST['tracking_number_input']);
    $tracking_number = $tracking_number_input_sent ? sanitize_input($_POST['tracking_number_input']) : null;

    // Debugging: Cek nilai variabel setelah sanitasi
    // echo "<p>Debug: Order ID: " . $order_id . "</p>";
    // echo "<p>Debug: New Status: " . $new_status . "</p>";
    // echo "<p>Debug: Tracking Number (from input): " . ($tracking_number ?? 'NULL') . "</p>";
    // echo "<p>Debug: Tracking Number input sent: " . ($tracking_number_input_sent ? 'Yes' : 'No') . "</p>";


    if (empty($order_id) || empty($new_status)) {
        $message = '<p class="error">ID pesanan dan status baru tidak boleh kosong.</p>';
        // echo "<p>Debug: Error: ID pesanan atau status kosong.</p>";
    } else {
        $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($new_status, $allowed_statuses)) {
            $message = '<p class="error">Status tidak valid.</p>';
            // echo "<p>Debug: Error: Status tidak valid.</p>";
        } else {
            // Logika untuk UPDATE status
            $update_query = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP";
            $bind_types = ""; // Inisialisasi kosong untuk tipe data
            $bind_params_values = []; // Inisialisasi array untuk nilai parameter

            // Tambahkan status
            $bind_types .= "s";
            $bind_params_values[] = $new_status;

            // --- PERBAIKAN LOGIKA NOMOR RESI ---
            // Hanya update kolom tracking_number jika input fieldnya memang ada di POST
            // (artinya form yang di-submit punya input tracking_number_input)
            if ($tracking_number_input_sent) {
                $update_query .= ", tracking_number = ?";
                $bind_types .= "s";
                $bind_params_values[] = $tracking_number; // Nilai ini bisa string kosong jika admin mengosongkannya
            }
            // Jika tracking_number_input_sent false, berarti input fieldnya tidak ada di form.
            // Dalam kasus ini, kita TIDAK akan menyertakan 'tracking_number' dalam SET clause,
            // sehingga nilai yang ada di database akan tetap dipertahankan.
            // --- AKHIR PERBAIKAN LOGIKA ---

            // Tambahkan order_id (selalu di akhir WHERE)
            $update_query .= " WHERE id = ?";
            $bind_types .= "i";
            $bind_params_values[] = $order_id;

            $stmt = $conn->prepare($update_query);

            if ($stmt === false) {
                $message = '<p class="error">Gagal menyiapkan query update: ' . $conn->error . '</p>';
                // echo "<p>Debug: Prepare Query Error: " . $conn->error . "</p>";
            } else {
                // Buat array referensi untuk bind_param
                $bind_param_args = [];
                $bind_param_args[] = $bind_types; // Argumen pertama adalah string tipe data

                // Masukkan semua nilai variabel ke array dengan referensi
                foreach ($bind_params_values as $key => $value) {
                    $bind_param_args[] = &$bind_params_values[$key]; // Luluskan dengan referensi
                }

                // Panggil bind_param menggunakan call_user_func_array
                if (call_user_func_array([$stmt, 'bind_param'], $bind_param_args)) {
                    if ($stmt->execute()) {
                        $message = '<p class="success">Status pesanan #' . htmlspecialchars($order_id) . ' berhasil diperbarui menjadi ' . htmlspecialchars(ucfirst($new_status)) . '!</p>';
                        // echo "<p>Debug: Query executed successfully!</p>";
                    } else {
                        $message = '<p class="error">Gagal mengeksekusi update status pesanan: ' . $stmt->error . '</p>';
                        // echo "<p>Debug: Execute Query Error: " . $stmt->error . "</p>";
                    }
                } else {
                    $message = '<p class="error">Gagal mengikat parameter untuk update: ' . $stmt->error . '</p>';
                    // echo "<p>Debug: Bind Param Error: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        }
    }
}


// --- Handle View Order Detail ---
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $order_id_view = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    // Ambil detail pesanan
    $stmt_order_view = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt_order_view->bind_param("i", $order_id_view);
    $stmt_order_view->execute();
    $result_order_view = $stmt_order_view->get_result();
    if ($result_order_view->num_rows == 1) {
        $order_to_view = $result_order_view->fetch_assoc();
    }
    $stmt_order_view->close();

    // Jika pesanan ditemukan, ambil item-itemnya
    if ($order_to_view) {
        $stmt_items_view = $conn->prepare("SELECT oi.*, p.name AS product_name, p.image_url, p.brand FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt_items_view->bind_param("i", $order_id_view);
        $stmt_items_view->execute();
        $result_items_view = $stmt_items_view->get_result();
        if ($result_items_view->num_rows > 0) {
            while ($row = $result_items_view->fetch_assoc()) {
                $order_items_view[] = $row;
            }
        }
        $stmt_items_view->close();
    }
}

// --- Fetch all orders for display ---
$all_orders = [];
$orders_query = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
if ($orders_query->num_rows > 0) {
    while ($row = $orders_query->fetch_assoc()) {
        $all_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/global.css">
    <style>
        /* CSS untuk tampilan modal atau detail order */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            position: relative;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .order-detail-modal-content img {
            max-width: 80px;
            height: auto;
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
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
            <h2>Kelola Pesanan</h2>
            <?php echo $message; ?>

            <h3>Daftar Semua Pesanan</h3>
            <?php if (empty($all_orders)): ?>
                <p>Belum ada pesanan yang masuk.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pembeli</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Metode Pembayaran</th>
                            <th>No. Resi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <form action="orders.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="order_id"
                                            value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <input type="hidden" name="update_order_status" value="1"> <select name="new_status"
                                            onchange="this.form.submit()"
                                            style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <?php if ($order['status'] == 'shipped' || $order['status'] == 'processing'): ?>
                                            <input type="text" name="tracking_number_input" placeholder="Nomor Resi"
                                                value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>"
                                                style="margin-left: 5px; padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($order['tracking_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="orders.php?action=view&id=<?php echo htmlspecialchars($order['id']); ?>"
                                        class="button button-small view-order-button">Lihat Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($order_to_view): ?>
        <div id="orderDetailModal" class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <div class="order-detail-modal-content">
                    <h3>Detail Pesanan #<?php echo htmlspecialchars($order_to_view['id']); ?></h3>
                    <p><strong>Pembeli:</strong> <?php echo htmlspecialchars($order_to_view['username']); ?>
                        (<?php echo htmlspecialchars($order_to_view['email']); ?>)</p>
                    <p><strong>Tanggal Pesan:</strong>
                        <?php echo date('d M Y H:i', strtotime($order_to_view['order_date'])); ?></p>
                    <p><strong>Total Pesanan:</strong> Rp
                        <?php echo number_format($order_to_view['total_amount'], 2, ',', '.'); ?>
                    </p>
                    <p><strong>Status:</strong> <span
                            class="order-status-<?php echo strtolower(htmlspecialchars($order_to_view['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order_to_view['status'])); ?></span>
                    </p>
                    <p><strong>Alamat Pengiriman:</strong>
                        <?php echo nl2br(htmlspecialchars($order_to_view['shipping_address'])); ?></p>
                    <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order_to_view['payment_method']); ?>
                    </p>
                    <?php if (!empty($order_to_view['tracking_number'])): ?>
                        <p><strong>Nomor Resi:</strong> <?php echo htmlspecialchars($order_to_view['tracking_number']); ?></p>
                    <?php endif; ?>

                    <h4>Item Pesanan:</h4>
                    <?php if (empty($order_items_view)): ?>
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
                                <?php foreach ($order_items_view as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['image_url']): ?>
                                                <img src="../public/uploads/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                    alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['selected_size']); ?></td>
                                        <td>Rp <?php echo number_format($item['price_at_purchase'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>Rp
                                            <?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
            // JavaScript untuk menutup modal
            var modal = document.getElementById("orderDetailModal");
            var span = document.getElementsByClassName("close-button")[0];
            if (span) {
                span.onclick = function () {
                    modal.style.display = "none";
                    // Hapus parameter action=view dan id dari URL setelah modal ditutup
                    window.history.pushState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]action=view.*?(&|$)|[\?&]id=.*?(&|$)/g, ''));
                }
            }
            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                    window.history.pushState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]action=view.*?(&|$)|[\?&]id=.*?(&|$)/g, ''));
                }
            }
        </script>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>