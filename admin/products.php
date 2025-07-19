<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotAdmin('../pages/login.php');

$message = '';
$product_to_edit = null;

// --- Fetch categories for dropdown ---
$categories_for_dropdown = [];
$category_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories_for_dropdown[] = $row;
    }
}

// --- Handle DELETE ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    // Dapatkan nama file gambar sebelum dihapus dari DB
    $stmt_get_image = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt_get_image->bind_param("i", $id);
    $stmt_get_image->execute();
    $result_image = $stmt_get_image->get_result();
    $image_row = $result_image->fetch_assoc();
    $old_image_url = $image_row['image_url'] ?? null;
    $stmt_get_image->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Hapus file gambar dari server jika ada
        if ($old_image_url && file_exists('../public/uploads/' . $old_image_url)) {
            unlink('../public/uploads/' . $old_image_url);
        }
        $message = '<p class="success">Produk berhasil dihapus!</p>';
    } else {
        $message = '<p class="error">Gagal menghapus produk: ' . $stmt->error . '</p>';
    }
    $stmt->close();
}

// --- Handle ADD/UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {
    $product_id = isset($_POST['product_id']) ? filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $name = sanitize_input($_POST['name']); // Menggunakan sanitize_input
    $description = sanitize_input($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $brand = sanitize_input($_POST['brand']); // Menggunakan sanitize_input
    $size_available = sanitize_input($_POST['size_available']); // Menggunakan sanitize_input
    $image_url = $_POST['current_image_url'] ?? null; // Pertahankan gambar lama jika tidak ada upload baru

    if (empty($name) || $price === false || $stock === false || empty($category_id) || empty($brand)) { // Perbaiki validasi untuk price/stock
        $message = '<p class="error">Nama, harga, stok, kategori, dan brand wajib diisi, serta format harga/stok harus benar.</p>';
    } else {
        // --- Image Upload Handling ---
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../public/uploads/";
            $image_file_name = uniqid() . '_' . basename($_FILES["image"]["name"]); // Nama unik untuk file
            $target_file = $target_dir . $image_file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validasi tipe file
            $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];
            if (!in_array($imageFileType, $allowed_types)) {
                $message = '<p class="error">Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan.</p>';
            } elseif ($_FILES["image"]["size"] > 5000000) { // Max 5MB
                $message = '<p class="error">Maaf, ukuran file gambar terlalu besar. Maks 5MB.</p>';
            } else {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Jika ini update dan ada gambar lama, hapus gambar lama
                    if ($product_id && $_POST['current_image_url'] && file_exists($target_dir . $_POST['current_image_url'])) {
                        unlink($target_dir . $_POST['current_image_url']);
                    }
                    $image_url = $image_file_name; // Simpan nama file ke DB
                } else {
                    $message = '<p class="error">Terjadi kesalahan saat mengunggah gambar.</p>';
                }
            }
        }

        if (empty($message)) { // Lanjutkan jika tidak ada error upload
            if ($product_id) { // UPDATE
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, category_id = ?, brand = ?, size_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                // PERBAIKAN PENTING DI SINI: ganti "ssdiiii" menjadi "ssdissisi"
                $stmt->bind_param("ssdissisi", $name, $description, $price, $stock, $image_url, $category_id, $brand, $size_available, $product_id);
            } else { // INSERT
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image_url, category_id, brand, size_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                // Ini sudah benar: "ssdissis"
                $stmt->bind_param("ssdissis", $name, $description, $price, $stock, $image_url, $category_id, $brand, $size_available);
            }

            if ($stmt->execute()) {
                $message = '<p class="success">Produk berhasil ' . ($product_id ? 'diperbarui' : 'ditambahkan') . '!</p>';
                // Clear form for new entry after successful add
                if (!$product_id) {
                    $_POST = []; // Reset POST array to clear form
                }
            } else {
                $message = '<p class="error">Gagal ' . ($product_id ? 'memperbarui' : 'menambahkan') . ' produk: ' . $stmt->error . '</p>';
            }
            $stmt->close();
        }
    }
}

// --- Handle EDIT form population ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $product_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Fetch all products for display ---
// Join dengan tabel categories untuk menampilkan nama kategori
$products_query = $conn->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
$products = [];
if ($products_query->num_rows > 0) {
    while ($row = $products_query->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin</title>
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
            <h2>Kelola Produk</h2>
            <?php echo $message; // Menampilkan pesan sukses/error ?>

            <h3><?php echo ($product_to_edit ? 'Edit Produk' : 'Tambah Produk Baru'); ?></h3>
            <form action="products.php" method="POST" enctype="multipart/form-data">
                <?php if ($product_to_edit): ?>
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_to_edit['id']); ?>">
                    <input type="hidden" name="current_image_url"
                        value="<?php echo htmlspecialchars($product_to_edit['image_url']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Nama Produk:</label>
                    <input type="text" id="name" name="name"
                        value="<?php echo htmlspecialchars($product_to_edit['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi Produk:</label>
                    <textarea id="description"
                        name="description"><?php echo htmlspecialchars($product_to_edit['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Harga:</label>
                    <input type="number" step="0.01" id="price" name="price"
                        value="<?php echo htmlspecialchars($product_to_edit['price'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stok:</label>
                    <input type="number" id="stock" name="stock"
                        value="<?php echo htmlspecialchars($product_to_edit['stock'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Kategori:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories_for_dropdown as $cat_opt): ?>
                            <option value="<?php echo htmlspecialchars($cat_opt['id']); ?>" <?php echo ($product_to_edit && $product_to_edit['category_id'] == $cat_opt['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat_opt['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="brand">Brand:</label>
                    <input type="text" id="brand" name="brand"
                        value="<?php echo htmlspecialchars($product_to_edit['brand'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="size_available">Ukuran Tersedia (Pisahkan dengan koma, misal: 38,39,40):</label>
                    <input type="text" id="size_available" name="size_available"
                        value="<?php echo htmlspecialchars($product_to_edit['size_available'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="image">Gambar Produk:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if ($product_to_edit && $product_to_edit['image_url']): ?>
                        <p>Gambar saat ini: <img
                                src="../public/uploads/<?php echo htmlspecialchars($product_to_edit['image_url']); ?>"
                                alt="Gambar Produk" style="max-width: 100px; vertical-align: middle;"></p>
                    <?php endif; ?>
                </div>
                <button type="submit"
                    name="submit_product"><?php echo ($product_to_edit ? 'Update Produk' : 'Tambah Produk'); ?></button>
                <?php if ($product_to_edit): ?>
                    <a href="products.php" class="button button-secondary">Batal Edit</a>
                <?php endif; ?>
            </form>

            <hr>

            <h3>Daftar Produk</h3>
            <?php if (empty($products)): ?>
                <p>Belum ada produk yang ditambahkan.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Ukuran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod['id']); ?></td>
                                <td>
                                    <?php if ($prod['image_url']): ?>
                                        <img src="../public/uploads/<?php echo htmlspecialchars($prod['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                            style="max-width: 80px; height: auto;">
                                    <?php else: ?>
                                        <img src="../public/images/placeholder.jpg" alt="No Image"
                                            style="max-width: 100px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                <td><?php echo htmlspecialchars($prod['category_name'] ?? 'N/A'); ?></td>
                                <td>Rp <?php echo number_format($prod['price'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($prod['stock']); ?></td>
                                <td><?php echo htmlspecialchars($prod['size_available'] ?: 'N/A'); ?></td>
                                <td>
                                    <a href="products.php?action=edit&id=<?php echo htmlspecialchars($prod['id']); ?>"
                                        class="button button-small">Edit</a>
                                    <a href="products.php?action=delete&id=<?php echo htmlspecialchars($prod['id']); ?>"
                                        class="button button-small button-danger"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                                </td>
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