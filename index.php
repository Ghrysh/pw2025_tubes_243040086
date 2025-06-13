<?php
// --- Inisialisasi Sesi & Koneksi Database ---
session_start();
require_once 'config/inc_koneksi.php';

// --- Cek Cookie Remember Me ---
if (isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_token']) && !isset($_SESSION['id'])) {
}

// --- Penanganan Error & Success ---
$error = null;
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

$success = null;
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <!-- --- Metadata & Link CSS/Font --- -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MizuPix</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="public/assets/css/style_login.css">
</head>

<body>
    <div class="login-page-container">

        <!-- --- Bagian Visual Kiri --- -->
        <div class="login-visual">
            <div class="visual-overlay"></div>
            <div class="visual-content">
                <h1>Temukan Inspirasi Tanpa Batas</h1>
                <p>Jelajahi jutaan gambar berkualitas tinggi dari para kreator di seluruh dunia.</p>
            </div>
        </div>

        <!-- --- Bagian Form Login Kanan --- -->
        <div class="login-form-wrapper">
            <div class="login-form-container">
                <div class="form-header">
                    <img src="public/assets/img/logo.png" alt="Logo Mizupix" class="logo">
                    <h2>Selamat Datang Kembali</h2>
                    <p>Masuk untuk melanjutkan ke MizuPix.</p>
                </div>

                <!-- --- Notifikasi Error --- -->
                <?php if ($error): ?>
                    <div class="notification is-error">
                        <i class='bx bxs-x-circle'></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- --- Form Login --- -->
                <form action="app/controller/login_controller.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <div class="input-wrapper">
                            <i class='bx bx-envelope'></i>
                            <input type="email" id="email" name="email" placeholder="contoh@email.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <div class="input-wrapper">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" id="password" name="password" placeholder="Masukkan kata sandi Anda" required>
                        </div>
                    </div>
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <label for="remember_me">Ingat Saya</label>
                        </div>
                        <div class="forgot-password-link">
                            <a href="lupa_password.php">Lupa Password?</a>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Masuk</button>
                </form>

                <!-- --- Footer Form Login --- -->
                <div class="form-footer">
                    <p>Belum punya akun? <a href="app/views/login/registrasi.php">Daftar di sini</a></p>
                </div>
            </div>
        </div>

    </div>
</body>

</html>