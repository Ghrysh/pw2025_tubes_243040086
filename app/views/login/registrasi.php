<?php
session_start();
// Jika sudah login, alihkan ke dasbor
// if (isset($_SESSION['id'])) {
//     header("Location: dashboard_user.php");
//     exit;
// }

require_once '../../../config/inc_koneksi.php';

$error = null;
$success = null;
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - MizuPix</title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="../../../public/assets/css/style_registrasi.css">
</head>

<body>
    <div class="register-page-container">

        <!-- Sisi Visual (Kiri) -->
        <div class="register-visual">
            <div class="visual-overlay"></div>
            <div class="visual-content">
                <h1>Bergabung dengan Komunitas Kreatif</h1>
                <p>Daftar sekarang untuk mulai berbagi dan menemukan karya seni visual yang mengagumkan.</p>
            </div>
        </div>

        <!-- Sisi Form (Kanan) -->
        <div class="register-form-wrapper">
            <div class="register-form-container">
                <div class="form-header">
                    <img src="../../../public/assets/img/logo.png" alt="Logo Mizupix" class="logo">
                    <h2>Buat Akun Baru</h2>
                    <p>Gratis dan hanya butuh beberapa detik.</p>
                </div>

                <?php if ($error): ?>
                    <div class="notification is-error">
                        <i class='bx bxs-x-circle'></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="notification is-success">
                        <i class='bx bxs-check-circle'></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form action="../../controller/registrasi_controller.php" method="POST" class="register-form" autocomplete="off">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class='bx bx-user'></i>
                            <input type="text" id="username" name="username" placeholder="pilih_username_unik" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <div class="input-wrapper">
                            <i class='bx bx-envelope'></i>
                            <input type="email" id="email" name="email" placeholder="contoh@email.com" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <div class="input-wrapper">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Konfirmasi Kata Sandi</label>
                        <div class="input-wrapper">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi kata sandi" required minlength="6" />
                        </div>
                    </div>
                    <button type="submit" class="btn-register">Daftar Sekarang</button>
                </form>

                <div class="form-footer">
                    <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
                </div>
            </div>
        </div>

    </div>
</body>

</html>