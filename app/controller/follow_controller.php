<?php
// Penanganan Error PHP Menjadi Exception
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Output Buffering & Header JSON
ob_start();
header('Content-Type: application/json');

try {
    // Inisialisasi Session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Koneksi ke Database
    $koneksi_path = __DIR__ . '/../../config/inc_koneksi.php';
    if (!file_exists($koneksi_path)) {
        throw new Exception("File koneksi tidak ditemukan.");
    }
    require_once $koneksi_path;

    // Validasi Variabel Koneksi
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Variabel \$koneksi tidak valid.");
    }

    // Validasi Login Pengguna
    if (!isset($_SESSION['id'])) {
        throw new Exception("Akses ditolak. Anda harus login.", 403);
    }

    // Validasi Metode Request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request harus POST.", 405);
    }

    // Ambil & Validasi Data Input
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Data JSON yang dikirim tidak valid.", 400);
    }

    $followingId = filter_var($data['following_id'] ?? null, FILTER_VALIDATE_INT);
    $followerId = (int)$_SESSION['id'];

    if (!$followingId) {
        throw new Exception("ID pengguna yang akan diikuti tidak valid.", 400);
    }

    if ($followerId === $followingId) {
        throw new Exception("Anda tidak bisa mengikuti diri sendiri.", 400);
    }

    // Logika Cek & Update Status Follow/Unfollow
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $followerId, $followingId);
    mysqli_stmt_execute($stmt_check);
    $isFollowing = mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0;

    $responseStatus = '';

    if ($isFollowing) {
        // Proses Unfollow
        $stmt = mysqli_prepare($koneksi, "DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $followerId, $followingId);
        if (mysqli_stmt_execute($stmt)) {
            $responseStatus = 'unfollowed';
        }
    } else {
        // Proses Follow & Notifikasi
        $stmt = mysqli_prepare($koneksi, "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $followerId, $followingId);
        if (mysqli_stmt_execute($stmt)) {
            $responseStatus = 'followed';
            $notif_stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow')"
            );
            mysqli_stmt_bind_param($notif_stmt, "ii", $followingId, $followerId);
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
        }
    }

    if (empty($responseStatus)) {
        throw new Exception("Gagal memperbarui status follow di database.");
    }

    // Ambil Jumlah Follower Terbaru
    $stmt_count = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM followers WHERE following_id = ?");
    mysqli_stmt_bind_param($stmt_count, "i", $followingId);
    mysqli_stmt_execute($stmt_count);
    $newFollowerCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['count'];

    ob_end_clean();
    echo json_encode([
        'status' => $responseStatus,
        'new_follower_count' => $newFollowerCount
    ]);
} catch (Exception $e) {
    ob_end_clean();
    $errorCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($errorCode);

    // Respons Error JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server.',
        'debug' => ['error_message' => $e->getMessage()]
    ]);
}

exit;
