<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_login.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <title>Login - MizuPix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <img src="../../../public/assets/img/logo.png" alt="Logo Mizupix" class="logo">
        <h2>Wellcome to MizuPix</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="../../controller/login_controller.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Kata Sandi" required>
            <button type="submit">Masuk</button>
        </form>
        <a class="login-link" href="registrasi.php">Belum punya akun? Daftar di sini</a>
    </div>
</body>

</html>