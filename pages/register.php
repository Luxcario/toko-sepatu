<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // Digunakan untuk redirect

// Variabel untuk pesan sukses/error (sebelum menggunakan flash messages)
// $error_message = '';
// $success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) { // PERBAIKAN: name="register"
    $username = sanitize_input($_POST['username']); // Gunakan sanitize_input
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = sanitize_input($_POST['phone_number']);
    $address = sanitize_input($_POST['address']);

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        set_flash_message('error', "Semua field wajib diisi.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('error', "Format email tidak valid.");
    } elseif ($password !== $confirm_password) {
        set_flash_message('error', "Konfirmasi password tidak cocok.");
    } elseif (strlen($password) < 6) {
        set_flash_message('error', "Password minimal 6 karakter.");
    } else {
        // Cek apakah username atau email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            set_flash_message('error', "Username atau email sudah terdaftar.");
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Masukkan data ke database
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, phone_number, address, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            $stmt_insert->bind_param("sssss", $username, $email, $hashed_password, $phone_number, $address);

            if ($stmt_insert->execute()) {
                set_flash_message('success', "Pendaftaran berhasil! Silakan login.");
                redirect('login.php'); // Menggunakan fungsi redirect dari functions.php
            } else {
                // Untuk debugging, tampilkan error MySQL:
                set_flash_message('error', "Terjadi kesalahan saat mendaftar: " . $stmt_insert->error);
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    // Jika ada error_message, kita akan kembali ke halaman ini dan menampilkannya.
    // Jika sukses dan redirect, kode di bawah ini tidak akan dieksekusi.
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/global.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main>
        <div class="container">
            <h2>Daftar Akun Baru</h2>
            <?php
            // Tampilkan pesan error atau sukses menggunakan flash messages
            echo get_flash_message('success');
            echo get_flash_message('error');
            ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Nomor Telepon (Opsional):</label>
                    <input type="text" id="phone_number" name="phone_number"
                        value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="address">Alamat (Opsional):</label>
                    <textarea id="address"
                        name="address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="register">Daftar</button>
            </form>
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>