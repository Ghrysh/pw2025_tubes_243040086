<?php
session_start();
require_once '../../config/inc_koneksi.php';

// Pastikan tabel 'login' memiliki kolom 'username'
// ALTER TABLE login ADD username VARCHAR(255) UNIQUE;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (!$username || !$email) {
        $error = urlencode("Username dan email wajib diisi.");
        header("Location: ../views/login/lupa_password.php?error=$error");
        exit;
    }

    // Cek apakah kombinasi username dan email ada di tabel user
    $stmt = $koneksi->prepare("SELECT id FROM login WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Jika ditemukan, alihkan ke halaman reset password dengan membawa email
        $_SESSION['reset_email'] = $email; // Gunakan session untuk keamanan
        header("Location: ../views/login/reset_password.php");
        exit;
    } else {
        // Jika tidak ditemukan
        $error = urlencode("Kombinasi username dan email tidak ditemukan.");
        header("Location: ../views/login/lupa_password.php?error=$error");
        exit;
    }

    $stmt->close();
    $koneksi->close();
} else {
    header("Location: ../views/login/lupa_password.php");
    exit;
}
