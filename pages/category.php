<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$selected_category_id = isset($_GET['category_id']) ? filter_var($_GET['category_id'], FILTER_SANITIZE_NUMBER_INT) : null;
$search_query = isset($_GET['search_query']) ? sanitize_input($_GET['search_query']) : null; // Ambil kata kunci pencarian
$category_name = "Semua Produk";

// Ambil semua kategori untuk navigasi/filter
$categories = [];
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
        if ($selected_category_id == $row['id']) {
            $category_name = htmlspecialchars($row['name']);
        }
    }
}

// Query dasar untuk mengambil produk
$sql = "SELECT id, name, price, image_url, brand FROM products WHERE 1=1"; // WHERE 1=1 agar mudah menambahkan kondisi AND

$params = [];
$types = "";

// Tambahkan filter kategori jika ada
if ($selected_category_id) {
    $sql .= " AND category_id = ?";
    $params[] = $selected_category_id;
    $types .= "i";
}

// Tambahkan filter pencarian jika ada
if ($search_query) {
    $search_term_like = '%' . $search_query . '%'; // Tambahkan wildcard untuk LIKE
    $sql .= " AND (name LIKE ? OR description LIKE ? OR brand LIKE ?)";
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $types .= "sss"; // 3 string untuk name, description, brand
}

$sql .= " ORDER BY name ASC";

$products = [];
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error); // Debugging error
}

// Hanya bind_param jika ada parameter
if (!empty($params)) {
    // Buat array referensi untuk bind_param
    $bind_param_args = [];
    $bind_param_args[] = $types; // Argumen pertama adalah string tipe data

    // Masukkan semua nilai variabel ke array dengan referensi
    foreach ($params as $key => $value) {
        $bind_param_args[] = &$params[$key]; // Luluskan dengan referensi
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_param_args);
}

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
    <title><?php echo $category_name; ?> - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container product-listing">
        <aside class="sidebar">
            <h3>Kategori</h3>
            <ul>
                <li><a href="category.php"
                        class="<?php echo ($selected_category_id === null && !$search_query) ? 'active' : ''; ?>">Semua
                        Produk</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="category.php?category_id=<?php echo htmlspecialchars($cat['id']); ?>"
                            class="<?php echo ($selected_category_id == $cat['id'] && !$search_query) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <section class="content">
            <h2><?php echo $category_name; ?>
                <?php if ($search_query): ?>
                    (Hasil Pencarian untuk "<?php echo htmlspecialchars($search_query); ?>")
                <?php endif; ?>
            </h2>

            <div class="search-bar">
                <form action="category.php" method="GET">
                    <input type="text" name="search_query" placeholder="Cari produk..."
                        value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                    <button type="submit" class="button button-secondary">Cari</button>
                    <?php if ($search_query || $selected_category_id): ?>
                        <a href="category.php" class="button button-small">Reset Filter</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($products)): ?>
                <p>Tidak ada produk yang tersedia dengan filter ini.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                                <?php if ($product['image_url']): ?>
                                    <img src="../public/uploads/<?php echo htmlspecialchars($product['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <img src="../public/images/placeholder.jpg" alt="No Image">
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
    <script src="../public/js/script.js"></script>
</body>

</html>