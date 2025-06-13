<?php
// Mulai sesi dan koneksi database
session_start();
require_once '../../config/inc_koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil input username dan email dari form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // Validasi input username dan email
    if (!$username || !$email) {
        $error = urlencode("Username dan email wajib diisi.");
        header("Location: ../views/login/lupa_password.php?error=$error");
        exit;
    }

    // Cek kombinasi username dan email di database
    $stmt = $koneksi->prepare("SELECT id FROM login WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    // Proses jika data ditemukan
    if ($stmt->num_rows === 1) {
        // Simpan email ke session dan redirect ke halaman reset password
        $_SESSION['reset_email'] = $email;
        header("Location: ../views/login/reset_password.php");
        exit;
    } else {
        // Proses jika data tidak ditemukan
        $error = urlencode("Kombinasi username dan email tidak ditemukan.");
        header("Location: ../views/login/lupa_password.php?error=$error");
        exit;
    }

    // Tutup statement dan koneksi
    $stmt->close();
    $koneksi->close();
} else {
    // Redirect jika bukan request POST
    header("Location: ../views/login/lupa_password.php");
    exit;
}
