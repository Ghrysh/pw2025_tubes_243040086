<?php
session_start();
require_once '../../../config/inc_koneksi.php';

// Hapus token "Ingat Saya" dari database jika ada
if (isset($_COOKIE['remember_me'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    $stmt = $koneksi->prepare("DELETE FROM auth_tokens WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $stmt->close();
}

// Hapus cookie dari browser dengan mengatur waktu kedaluwarsa di masa lalu
setcookie('remember_me', '', time() - 3600, '/');

// Hancurkan semua data sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Alihkan ke halaman login
header("Location: login.php");
exit();
