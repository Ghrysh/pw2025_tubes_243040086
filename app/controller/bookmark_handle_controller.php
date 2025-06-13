<?php
// bookmark_handle_controller.php

// Mengubah semua error PHP menjadi Exception yang bisa ditangkap
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Mulai output buffering untuk memastikan tidak ada output lain
ob_start();
// Atur header default ke JSON
header('Content-Type: application/json');

try {
    // Mulai session jika belum ada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // PERBAIKAN: Path yang benar dari app/controller/ adalah naik dua level ke root, lalu ke config
    $koneksi_path = __DIR__ . '/../../config/inc_koneksi.php';
    if (!file_exists($koneksi_path)) {
        throw new Exception("File koneksi tidak ditemukan di path: " . $koneksi_path);
    }
    require_once $koneksi_path;

    // Periksa variabel koneksi
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Variabel \$koneksi tidak valid atau tidak ditemukan di dalam file inc_koneksi.php.");
    }

    // Periksa login
    if (!isset($_SESSION['id'])) {
        throw new Exception("Akses ditolak. Sesi pengguna tidak ditemukan.", 403);
    }

    // Periksa metode request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request harus POST.", 405);
    }

    // Ambil dan validasi data input
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Data JSON yang dikirim tidak valid.", 400);
    }

    $imageId = filter_var($data['image_id'] ?? null, FILTER_VALIDATE_INT);
    $userId = (int)$_SESSION['id'];

    if (!$imageId) {
        throw new Exception("ID gambar tidak valid atau tidak ada.", 400);
    }

    // --- Logika Inti Database ---

    // Cek apakah bookmark sudah ada
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM bookmarks WHERE user_id = ? AND image_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $userId, $imageId);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    $response = [];
    if (mysqli_num_rows($result_check) > 0) {
        // Hapus bookmark
        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM bookmarks WHERE user_id = ? AND image_id = ?");
        mysqli_stmt_bind_param($stmt_delete, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_delete)) {
            $response = ['status' => 'removed'];
        } else {
            throw new Exception("Gagal menghapus bookmark dari DB: " . mysqli_error($koneksi));
        }
    } else {
        // Tambah bookmark (logika ini tidak digunakan di halaman bookmark, tapi ada untuk kelengkapan)
        $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO bookmarks (user_id, image_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_insert, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_insert)) {
            $response = ['status' => 'saved'];
        } else {
            throw new Exception("Gagal menyimpan bookmark ke DB: " . mysqli_error($koneksi));
        }
    }

    ob_end_clean(); // Hapus buffer jika sukses
    echo json_encode($response);
} catch (Exception $e) {
    ob_end_clean(); // Hapus buffer jika terjadi error
    $errorCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($errorCode);

    // Kirim respons JSON yang berisi detail error untuk debugging
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server.',
        'debug' => [
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

exit;
