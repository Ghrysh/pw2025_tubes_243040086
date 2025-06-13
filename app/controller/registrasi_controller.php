<?php
// Mulai sesi dan koneksi database
session_start();
require_once '../../config/inc_koneksi.php';

// Cek jika request method POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil data dari form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validasi input kosong
    if (!$username || !$email || !$password || !$password_confirm) {
        $error = urlencode("Mohon isi semua bidang.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = urlencode("Email tidak valid.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    // Validasi konfirmasi password
    if ($password !== $password_confirm) {
        $error = urlencode("Konfirmasi kata sandi tidak cocok.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    // Validasi panjang password
    if (strlen($password) < 6) {
        $error = urlencode("Kata sandi minimal 6 karakter.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    // Cek email sudah terdaftar atau belum
    $stmt = $koneksi->prepare("SELECT id FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $error = urlencode("Email sudah terdaftar.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Set role user
    $role = 1;

    // Simpan data user baru ke database
    $stmt = $koneksi->prepare("INSERT INTO login (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password_hash, $role);
    if ($stmt->execute()) {
        $stmt->close();
        $success = urlencode("Registrasi berhasil! Silakan masuk.");
        // Redirect ke halaman login jika berhasil
        header("Location: ../views/login/login.php?success=$success");
        exit;
    } else {
        $stmt->close();
        $error = urlencode("Terjadi kesalahan saat registrasi: " . $stmt->error);
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }
} else {
    // Redirect jika bukan request POST
    header("Location: ../views/login/register.php");
    exit;
}

// Tutup koneksi database
$koneksi->close();
