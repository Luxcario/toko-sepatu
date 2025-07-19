<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php'; // Mungkin tidak diperlukan
require_once '../includes/functions.php'; // Jika ada fungsi validasi input

$message_status = ''; // Untuk menampilkan pesan sukses/error

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_content = trim($_POST['message']);

    // Validasi input
    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $message_status = '<p class="error">Semua field harus diisi.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_status = '<p class="error">Format email tidak valid.</p>';
    } else {
        // --- Simulasi Pengiriman Email (di lingkungan lokal) ---
        // Di lingkungan produksi, Anda akan menggunakan fungsi mail() atau library seperti PHPMailer.
        // Contoh sederhana untuk debugging/simulasi:
        $to = "admin@tokosepatu.com"; // Ganti dengan email Anda di produksi
        $headers = "From: " . $email . "\r\n" .
            "Reply-To: " . $email . "\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n";
        $email_body = "Nama: " . $name . "\n"
            . "Email: " . $email . "\n"
            . "Subjek: " . $subject . "\n"
            . "Pesan:\n" . $message_content;

        // mail($to, $subject, $email_body, $headers); // Uncomment ini jika server Anda mendukung mail()

        $message_status = '<p class="success">Pesan Anda berhasil terkirim. Kami akan menghubungi Anda segera!</p>';
        // Opsional: Kosongkan field form setelah sukses
        $_POST = [];

        // Untuk debugging lokal, bisa tampilkan saja
        // $message_status .= '<div style="background-color:#f0f0f0; padding:10px; border:1px solid #ccc; margin-top:15px;">';
        // $message_status .= '<strong>Simulasi Email Terkirim:</strong><br>';
        // $message_status .= 'Ke: ' . htmlspecialchars($to) . '<br>';
        // $message_status .= 'Dari: ' . htmlspecialchars($email) . '<br>';
        // $message_status .= 'Subjek: ' . htmlspecialchars($subject) . '<br>';
        // $message_status .= 'Pesan:<br>' . nl2br(htmlspecialchars($message_content)) . '<br>';
        // $message_status .= '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami - Toko Sepatu</title>
    <link rel="stylesheet" href="../public/css/global.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container static-page">
        <h2>Hubungi Kami</h2>
        <p>Kami senang mendengar dari Anda! Silakan gunakan formulir di bawah ini untuk pertanyaan, masukan, atau
            bantuan apa pun.</p>

        <?php echo $message_status; // Tampilkan pesan sukses/error ?>

        <form action="contact.php" method="POST" class="contact-form">
            <div class="form-group">
                <label for="name">Nama Lengkap:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="email">Email Anda:</label>
                <input type="email" id="email" name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="subject">Subjek:</label>
                <input type="text" id="subject" name="subject"
                    value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="message">Pesan Anda:</label>
                <textarea id="message" name="message" rows="8"
                    required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="submit_contact" class="button button-primary">Kirim Pesan</button>
        </form>

        <div class="contact-info">
            <h3>Informasi Kontak Lainnya</h3>
            <p><strong>Alamat:</strong> Jl. Contoh Toko Sepatu No. 123, Kota Anda, 12345</p>
            <p><strong>Telepon:</strong> (021) 1234-5678</p>
            <p><strong>Jam Operasional:</strong> Senin - Jumat, 09:00 - 17:00 WIB</p>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../public/js/script.js"></script>
</body>

</html>