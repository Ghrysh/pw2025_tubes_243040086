<?php
// Penanganan Error Menjadi Exception
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Inisialisasi Output Buffer dan Header JSON
ob_start();
header('Content-Type: application/json');

try {
    // Mulai Session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Inisialisasi Path dan Koneksi Database
    $projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online';
    $koneksi_path = $projectRoot . '/config/inc_koneksi.php';

    if (!file_exists($koneksi_path)) {
        throw new Exception("File koneksi tidak ditemukan di path: " . $koneksi_path);
    }
    require_once $koneksi_path;

    // Validasi Koneksi Database
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Variabel koneksi tidak valid.");
    }

    // Validasi Login User
    if (!isset($_SESSION['id'])) {
        throw new Exception("Akses ditolak. Anda harus login untuk menyukai gambar.", 403);
    }

    // Validasi Metode Request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request harus POST.", 405);
    }

    // Ambil dan Validasi Data Input
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Data JSON yang dikirim tidak valid.", 400);
    }

    $imageId = filter_var($data['image_id'] ?? null, FILTER_VALIDATE_INT);
    $userId = (int)$_SESSION['id'];

    if (!$imageId) {
        throw new Exception("ID gambar tidak valid atau tidak ada.", 400);
    }

    // Logika Like dan Unlike Gambar
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM likes WHERE user_id = ? AND image_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $userId, $imageId);
    mysqli_stmt_execute($stmt_check);
    $isLiked = mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0;

    $responseStatus = '';

    if ($isLiked) {
        // Proses Unlike
        $stmt_unlike = mysqli_prepare($koneksi, "DELETE FROM likes WHERE user_id = ? AND image_id = ?");
        mysqli_stmt_bind_param($stmt_unlike, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_unlike)) {
            $responseStatus = 'unliked';
        }
    } else {
        // Proses Like
        $stmt_like = mysqli_prepare($koneksi, "INSERT INTO likes (user_id, image_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_like, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_like)) {
            $responseStatus = 'liked';
        }
    }

    // Kirim Notifikasi ke Pemilik Gambar
    $owner_res = mysqli_query($koneksi, "SELECT user_id FROM images WHERE id = $imageId");
    $image_owner_id = mysqli_fetch_assoc($owner_res)['user_id'];

    if ($image_owner_id != $userId) {
        $notif_stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO notifications (user_id, actor_id, type, target_id) VALUES (?, ?, 'like', ?)"
        );
        mysqli_stmt_bind_param($notif_stmt, "iii", $image_owner_id, $userId, $imageId);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);
    }

    if (empty($responseStatus)) {
        throw new Exception("Gagal memperbarui status like di database.");
    }

    // Ambil Jumlah Like Terbaru
    $stmt_count = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM likes WHERE image_id = ?");
    mysqli_stmt_bind_param($stmt_count, "i", $imageId);
    mysqli_stmt_execute($stmt_count);
    $newLikeCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['count'];

    ob_end_clean();
    echo json_encode([
        'status' => $responseStatus,
        'like_count' => $newLikeCount
    ]);
} catch (Exception $e) {
    // Penanganan Error dan Response JSON
    ob_end_clean();
    $errorCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($errorCode);

    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server.',
        'debug' => ['error_message' => $e->getMessage()]
    ]);
}

exit;
