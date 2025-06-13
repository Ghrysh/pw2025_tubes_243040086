<?php
session_start();

// Lindungi halaman ini, hanya bisa diakses jika session reset_email ada
if (!isset($_SESSION['reset_email'])) {
    header("Location: lupa_password.php?error=" . urlencode("Silakan verifikasi akun Anda terlebih dahulu."));
    exit;
}

$error = null;
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - MizuPix</title>
    <link rel="stylesheet" href="../../../public/assets/css/style_login.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
</head>

<body>
    <div class="login-page-container">
        <div class="login-visual">
            <div class="visual-overlay"></div>
            <div class="visual-content">
                <h1>Keamanan Akun</h1>
                <p>Gunakan kata sandi yang kuat dan mudah Anda ingat.</p>
            </div>
        </div>
        <div class="login-form-wrapper">
            <div class="login-form-container">
                <div class="form-header">
                    <img src="../../../public/assets/img/logo.png" alt="Logo Mizupix" class="logo">
                    <h2>Atur Ulang Kata Sandi</h2>
                    <p>Masukkan kata sandi baru Anda.</p>
                </div>

                <?php if ($error): ?>
                    <div class="notification is-error">
                        <i class='bx bxs-x-circle'></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="../../controller/update_password_controller.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="new_password">Kata Sandi Baru</label>
                        <div class="input-wrapper">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" id="new_password" name="new_password" placeholder="Masukkan kata sandi baru" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                        <div class="input-wrapper">
                            <i class='bx bx-lock-check'></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi kata sandi baru" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Ubah Kata Sandi</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>