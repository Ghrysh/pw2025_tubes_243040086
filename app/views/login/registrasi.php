<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../../../public/assets/css/style_registrasi.css" />
    <title>Registrasi - Mizupix</title>
</head>

<body>
    <div class="register-container">
        <h2>Wellcome to Mizupix</h2>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form action="../../controller/registrasi_controller.php" method="POST" autocomplete="off">
            <input type="text" name="username" placeholder="Nama Pengguna" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Kata Sandi" required minlength="6" />
            <input type="password" name="password_confirm" placeholder="Konfirmasi Kata Sandi" required minlength="6" />
            <button type="submit">Daftar</button>
        </form>
        <a class="login-link" href="login.php">Sudah punya akun? Masuk di sini</a>
    </div>
</body>

</html>