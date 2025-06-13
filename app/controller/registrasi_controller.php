<?php
session_start();
require_once '../../config/inc_koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (!$username || !$email || !$password || !$password_confirm) {
        $error = urlencode("Mohon isi semua bidang.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = urlencode("Email tidak valid.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    if ($password !== $password_confirm) {
        $error = urlencode("Konfirmasi kata sandi tidak cocok.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

    if (strlen($password) < 6) {
        $error = urlencode("Kata sandi minimal 6 karakter.");
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }

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

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $role = 1;

    $stmt = $koneksi->prepare("INSERT INTO login (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password_hash, $role);
    if ($stmt->execute()) {
        $stmt->close();
        $success = urlencode("Registrasi berhasil! Silakan masuk.");
        // Arahkan ke halaman login setelah registrasi berhasil
        header("Location: ../views/login/login.php?success=$success");
        exit;
    } else {
        $stmt->close();
        $error = urlencode("Terjadi kesalahan saat registrasi: " . $stmt->error);
        header("Location: ../views/login/register.php?error=$error");
        exit;
    }
} else {
    header("Location: ../views/login/register.php");
    exit;
}

$koneksi->close();
