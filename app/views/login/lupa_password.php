<?php
// Bagian: Inisialisasi Sesi dan Penanganan Error
session_start();
$error = null;
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Bagian: Metadata dan Link CSS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - MizuPix</title>
    <link rel="stylesheet" href="../../../public/assets/css/style_login.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
</head>

<body>
    <!-- Bagian: Kontainer Halaman Lupa Password -->
    <div class="login-page-container">
        <!-- Bagian: Visual Halaman -->
        <div class="login-visual">
            <div class="visual-overlay"></div>
            <div class="visual-content">
                <h1>Satu Langkah Lagi</h1>rapper">
                <p>Verifikasi akun Anda untuk mengatur ulang kata sandi.</p>er">
            </div>er">
        </div>go">
        <!-- Bagian: Form Lupa Password -->
        <div class="login-form-wrapper">
            <div class="login-form-container">
                <!-- Bagian: Header Form -->
                <div class="form-header">
                    <img src="../../../public/assets/img/logo.png" alt="Logo Mizupix" class="logo">
                    <h2>Lupa Kata Sandi</h2>
                    <p>Masukkan username dan email Anda yang terdaftar.</p>
                </div>

                <?php if ($error): ?>
                    <!-- Bagian: Notifikasi Error -->
                    <div class="notification is-error">
                        <i class='bx bxs-x-circle'></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Bagian: Form Input Username dan Email -->
                <form action="../../controller/lupa_password_controller.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class='bx bx-user'></i>
                            <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <div class="input-wrapper">
                            <i class='bx bx-envelope'></i>
                            <input type="email" id="email" name="email" placeholder="contoh@email.com" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Verifikasi Akun</button>
                </form>

                <!-- Bagian: Footer Form -->
                <div class="form-footer">
                    <p>Sudah ingat? <a href="login.php">Kembali ke Login</a></p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>