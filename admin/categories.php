<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php'; // Untuk sanitasi input dll.

redirectIfNotAdmin('../pages/login.php');

$message = '';
$category_to_edit = null;

// --- Handle DELETE ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    // Cek apakah ada produk yang masih terkait dengan kategori ini
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->bind_result($product_count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($product_count > 0) {
        $message = '<p class="error">Tidak dapat menghapus kategori ini karena masih ada ' . $product_count . ' produk yang terkait dengannya. Silakan ubah kategori produk tersebut terlebih dahulu.</p>';
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = '<p class="success">Kategori berhasil dihapus!</p>';
        } else {
            $message = '<p class="error">Gagal menghapus kategori: ' . $stmt->error . '</p>';
        }
        $stmt->close();
    }
}

// --- Handle ADD/UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT) : null;

    if (empty($name)) {
        $message = '<p class="error">Nama kategori tidak boleh kosong.</p>';
    } else {
        if ($category_id) { // UPDATE
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $category_id);
            if ($stmt->execute()) {
                $message = '<p class="success">Kategori berhasil diperbarui!</p>';
            } else {
                $message = '<p class="error">Gagal memperbarui kategori: ' . $stmt->error . '</p>';
            }
        } else { // INSERT
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $message = '<p class="success">Kategori berhasil ditambahkan!</p>';
            } else {
                $message = '<p class="error">Gagal menambahkan kategori: ' . $stmt->error . '</p>';
            }
        }
        $stmt->close();
    }
}

// --- Handle EDIT form population ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $category_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Fetch all categories for display ---
$categories_query = $conn->query("SELECT id, name, description FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_query->num_rows > 0) {
    while ($row = $categories_query->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
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
            <h2>Kelola Kategori</h2>
            <?php echo $message; // Menampilkan pesan sukses/error ?>

            <h3><?php echo ($category_to_edit ? 'Edit Kategori' : 'Tambah Kategori Baru'); ?></h3>
            <form action="categories.php" method="POST">
                <?php if ($category_to_edit): ?>
                    <input type="hidden" name="category_id"
                        value="<?php echo htmlspecialchars($category_to_edit['id']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Nama Kategori:</label>
                    <input type="text" id="name" name="name"
                        value="<?php echo htmlspecialchars($category_to_edit['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi (Opsional):</label>
                    <textarea id="description"
                        name="description"><?php echo htmlspecialchars($category_to_edit['description'] ?? ''); ?></textarea>
                </div>
                <button type="submit"
                    name="submit_category"><?php echo ($category_to_edit ? 'Update Kategori' : 'Tambah Kategori'); ?></button>
                <?php if ($category_to_edit): ?>
                    <a href="categories.php" class="button button-secondary">Batal Edit</a>
                <?php endif; ?>
            </form>

            <hr>

            <h3>Daftar Kategori</h3>
            <?php if (empty($categories)): ?>
                <p>Belum ada kategori yang ditambahkan.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['id']); ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                <td>
                                    <a href="categories.php?action=edit&id=<?php echo htmlspecialchars($cat['id']); ?>"
                                        class="button button-small">Edit</a>
                                    <a href="categories.php?action=delete&id=<?php echo htmlspecialchars($cat['id']); ?>"
                                        class="button button-small button-danger"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Semua produk yang terkait akan kehilangan kategorinya (jadi NULL).');">Hapus</a>
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