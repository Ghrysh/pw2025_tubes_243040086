<?php
session_start();
require_once '../../config/inc_koneksi.php';

$id = (int) $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = mysqli_real_escape_string($koneksi, $_POST['subject']);
    $message = mysqli_real_escape_string($koneksi, $_POST['message']);
    $created_at = date('Y-m-d H:i:s');

    $query = "INSERT INTO messages (user_id, subject, message, created_at) VALUES ('$id', '$subject', '$message', '$created_at')";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['success_message'] = "Pesan Anda telah berhasil dikirim!";
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . mysqli_error($koneksi);
    }
    header("Location: ../../app/views/user/dashboard_user.php");
    exit;
}
