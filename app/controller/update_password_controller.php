<?php
session_start();
require_once '../../config/inc_koneksi.php';

// Lindungi controller, hanya bisa diakses jika session reset_email ada
if (!isset($_SESSION['reset_email'])) {
    header("Location: ../views/login/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    // Validasi
    if (empty($new_password) || empty($confirm_password)) {
        $error = urlencode("Semua kolom wajib diisi.");
        header("Location: ../views/login/reset_password.php?error=$error");
        exit;
    }

    if ($new_password !== $confirm_password) {
        $error = urlencode("Kata sandi baru dan konfirmasi tidak cocok.");
        header("Location: ../views/login/reset_password.php?error=$error");
        exit;
    }

    if (strlen($new_password) < 8) {
        $error = urlencode("Kata sandi minimal harus 8 karakter.");
        header("Location: ../views/login/reset_password.php?error=$error");
        exit;
    }

    // Hash password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password di database
    $stmt = $koneksi->prepare("UPDATE login SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        // Hapus session setelah berhasil
        unset($_SESSION['reset_email']);

        $success = urlencode("Kata sandi berhasil diubah. Silakan login kembali.");
        header("Location: ../views/login/login.php?success=$success");
        exit;
    } else {
        $error = urlencode("Terjadi kesalahan. Gagal mengubah kata sandi.");
        header("Location: ../views/login/reset_password.php?error=$error");
        exit;
    }

    $stmt->close();
    $koneksi->close();
} else {
    header("Location: ../views/login/reset_password.php");
    exit;
}
