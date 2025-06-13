<?php
// Judul: Mengubah Error Menjadi Exception
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Judul: Mulai Output Buffering dan Set Header JSON
ob_start();
header('Content-Type: application/json');

try {
    // Judul: Mulai Session Jika Belum Ada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Judul: Inisialisasi Path dan Koneksi Database
    $projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online';
    $koneksi_path = $projectRoot . '/config/inc_koneksi.php';

    if (!file_exists($koneksi_path)) {
        throw new Exception("File koneksi tidak ditemukan di path: " . $koneksi_path);
    }
    require_once $koneksi_path;

    // Judul: Validasi Variabel Koneksi
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Variabel koneksi tidak valid.");
    }

    // Judul: Validasi Login Pengguna
    if (!isset($_SESSION['id'])) {
        throw new Exception("Akses ditolak. Anda harus login untuk berkomentar.", 403);
    }

    // Judul: Validasi Metode Request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request harus POST.", 405);
    }

    // Judul: Ambil dan Validasi Data Input
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Data JSON yang dikirim tidak valid.", 400);
    }

    $imageId = filter_var($data['image_id'] ?? null, FILTER_VALIDATE_INT);
    $commentText = trim(htmlspecialchars($data['comment_text'] ?? ''));
    $userId = (int)$_SESSION['id'];

    if (!$imageId || empty($commentText)) {
        throw new Exception("ID gambar atau teks komentar tidak valid.", 400);
    }

    // Judul: Simpan Komentar ke Database
    $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO comments (user_id, image_id, comment_text) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt_insert, "iis", $userId, $imageId, $commentText);

    // Judul: Ambil user_id Pemilik Gambar
    $owner_res = mysqli_query($koneksi, "SELECT user_id FROM images WHERE id = $imageId");
    $image_owner_id = mysqli_fetch_assoc($owner_res)['user_id'];

    // Judul: Eksekusi Penyimpanan Komentar dan Notifikasi
    if (mysqli_stmt_execute($stmt_insert)) {
        $commentId = mysqli_insert_id($koneksi);

        // Judul: Kirim Notifikasi Jika Bukan Gambar Sendiri
        if ($image_owner_id != $userId) {
            $notif_stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO notifications (user_id, actor_id, type, target_id) VALUES (?, ?, 'comment', ?)"
            );
            mysqli_stmt_bind_param($notif_stmt, "iii", $image_owner_id, $userId, $commentId);
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
        }

        // Judul: Ambil Data Profil Pengguna yang Berkomentar
        $stmt_profile = mysqli_prepare($koneksi, "SELECT up.nama_lengkap, up.foto, l.username FROM user_profiles up JOIN login l ON up.user_id = l.id WHERE up.user_id = ?");
        mysqli_stmt_bind_param($stmt_profile, "i", $userId);
        mysqli_stmt_execute($stmt_profile);
        $myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_profile));
        $defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';

        // Judul: Buat HTML Komentar Baru
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

        // Judul: Ambil Jumlah Total Komentar
        $stmt_count = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM comments WHERE image_id = ?");
        mysqli_stmt_bind_param($stmt_count, "i", $imageId);
        mysqli_stmt_execute($stmt_count);
        $comment_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['count'];

        // Judul: Siapkan Respons Sukses
        $response = [
            'status' => 'success',
            'comment_html' => $commentHtml,
            'comment_count' => $comment_count
        ];
    } else {
        throw new Exception("Gagal menyimpan komentar ke database: " . mysqli_error($koneksi));
    }

    ob_end_clean();
    echo json_encode($response);
} catch (Exception $e) {
    ob_end_clean();
    $errorCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($errorCode);

    // Judul: Kirim Respons Error JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server.',
        'debug' => ['error_message' => $e->getMessage()]
    ]);
}

exit;
