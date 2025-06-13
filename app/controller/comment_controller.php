<?php
// File: app/controller/handle_comment.php

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

    // Menggunakan path yang lebih eksplisit dan andal
    $projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online';
    $koneksi_path = $projectRoot . '/config/inc_koneksi.php';

    if (!file_exists($koneksi_path)) {
        throw new Exception("File koneksi tidak ditemukan di path: " . $koneksi_path);
    }
    require_once $koneksi_path;

    // Periksa variabel koneksi
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Variabel koneksi tidak valid.");
    }

    // Periksa login
    if (!isset($_SESSION['id'])) {
        throw new Exception("Akses ditolak. Anda harus login untuk berkomentar.", 403);
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
    // PERBAIKAN: Mengganti filter yang usang dengan htmlspecialchars
    $commentText = trim(htmlspecialchars($data['comment_text'] ?? ''));
    $userId = (int)$_SESSION['id'];

    if (!$imageId || empty($commentText)) {
        throw new Exception("ID gambar atau teks komentar tidak valid.", 400);
    }

    // --- Logika Inti Database ---

    // Simpan komentar ke database
    $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO comments (user_id, image_id, comment_text) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt_insert, "iis", $userId, $imageId, $commentText);

    // Dapatkan user_id pemilik gambar
    $owner_res = mysqli_query($koneksi, "SELECT user_id FROM images WHERE id = $imageId");
    $image_owner_id = mysqli_fetch_assoc($owner_res)['user_id'];

    // Eksekusi penyimpanan komentar
    if (mysqli_stmt_execute($stmt_insert)) {
        // Dapatkan ID komentar yang baru saja dibuat
        $commentId = mysqli_insert_id($koneksi); // Get the ID of the newly inserted comment

        // Jangan kirim notifikasi jika mengomentari gambar sendiri
        if ($image_owner_id != $userId) {
            $notif_stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO notifications (user_id, actor_id, type, target_id) VALUES (?, ?, 'comment', ?)"
            );
            mysqli_stmt_bind_param($notif_stmt, "iii", $image_owner_id, $userId, $commentId); // Use the correct comment ID
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
        }

        // Jika berhasil, ambil data yang dibutuhkan untuk respons

        // 1. Ambil data profil pengguna yang baru saja berkomentar
        $stmt_profile = mysqli_prepare($koneksi, "SELECT up.nama_lengkap, up.foto, l.username FROM user_profiles up JOIN login l ON up.user_id = l.id WHERE up.user_id = ?");
        mysqli_stmt_bind_param($stmt_profile, "i", $userId);
        mysqli_stmt_execute($stmt_profile);
        $myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_profile));
        $defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';

        // 2. Buat HTML untuk komentar baru
        $commentHtml = '
            <div class="comment-item new-comment">
                <a href="public_profile.php?username=' . htmlspecialchars($myProfile['username']) . '">
                    <img src="' . htmlspecialchars($myProfile['foto'] ?? $defaultPhotoPath) . '" class="commenter-avatar">
                </a>
                <div class="comment-content">
                    <a href="public_profile.php?username=' . htmlspecialchars($myProfile['username']) . '" class="commenter-name">
                        <strong>' . htmlspecialchars($myProfile['nama_lengkap']) . '</strong>
                    </a>
                    <p>' . htmlspecialchars($commentText) . '</p>
                </div>
            </div>';

        // 3. Ambil jumlah total komentar yang baru
        $stmt_count = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM comments WHERE image_id = ?");
        mysqli_stmt_bind_param($stmt_count, "i", $imageId);
        mysqli_stmt_execute($stmt_count);
        $comment_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['count'];

        // Siapkan respons sukses
        $response = [
            'status' => 'success',
            'comment_html' => $commentHtml,
            'comment_count' => $comment_count
        ];
    } else {
        throw new Exception("Gagal menyimpan komentar ke database: " . mysqli_error($koneksi));
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
        'debug' => ['error_message' => $e->getMessage()]
    ]);
}

exit;
