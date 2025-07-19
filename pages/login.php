<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// PERBAIKAN PENTING: Arahkan pengguna jika sudah login
// Ini mencegah redirect loop jika user sudah login tapi mencoba akses login.php
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/index.php'); // Arahkan ke dashboard admin
    } else {
        redirect('my_account.php'); // Arahkan ke halaman akun pelanggan
    }
}

// $error_message = ''; // Sekarang pakai flash messages

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username_email = sanitize_input($_POST['username_email']); // Gunakan sanitize_input
    $password = $_POST['password'];

    if (empty($username_email) || empty($password)) {
        set_flash_message('error', "Username/Email dan password harus diisi.");
    } else {
        // Cari pengguna berdasarkan username atau email
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Login berhasil, mulai sesi (session_start() sudah di config.php)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Arahkan pengguna sesuai perannya
                if ($user['role'] == 'admin') {
                    set_flash_message('success', 'Selamat datang, Admin ' . htmlspecialchars($user['username']) . '!');
                    redirect('../admin/index.php');
                } else {
                    set_flash_message('success', 'Selamat datang, ' . htmlspecialchars($user['username']) . '!');
                    redirect('my_account.php');
                }
            } else {
                set_flash_message('error', "Username/Email atau password salah.");
            }
        } else {
            set_flash_message('error', "Username/Email atau password salah.");
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main>
        <div class="container">
            <h2>Login</h2>
            <?php
            // Tampilkan pesan error atau sukses menggunakan flash messages
            echo get_flash_message('success');
            echo get_flash_message('error');
            ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username_email">Username atau Email:</label>
                    <input type="text" id="username_email" name="username_email" required
                        value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>