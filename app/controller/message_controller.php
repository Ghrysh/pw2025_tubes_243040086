<?php
// Mulai sesi
session_start();
// Import koneksi database
require_once '../../config/inc_koneksi.php';

// Ambil ID user dari sesi
$id = (int) $_SESSION['id'];

// Cek jika request method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan escape input subject dan message
    $subject = mysqli_real_escape_string($koneksi, $_POST['subject']);
    $message = mysqli_real_escape_string($koneksi, $_POST['message']);
    $created_at = date('Y-m-d H:i:s');

    // Query untuk insert pesan ke database
    $query = "INSERT INTO messages (user_id, subject, message, created_at) VALUES ('$id', '$subject', '$message', '$created_at')";

    // Eksekusi query dan set pesan sukses/gagal
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['success_message'] = "Pesan Anda telah berhasil dikirim!";
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . mysqli_error($koneksi);
    }
    // Redirect ke dashboard user
    header("Location: ../../app/views/user/dashboard_user.php");
    exit;
}
