<?php
// Mulai sesi dan cek autentikasi pengguna
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$currentUserId = (int)$_SESSION['id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

// Ambil daftar notifikasi
if ($action === 'fetch') {
    $query = "
        SELECT 
            n.id, n.type, n.target_id, n.is_read, n.created_at,
            a.username as actor_username, 
            ap.foto as actor_photo,
            i.image_path as target_image,
            i.id as image_id
        FROM notifications n
        JOIN login a ON n.actor_id = a.id
        LEFT JOIN user_profiles ap ON a.id = ap.user_id
        LEFT JOIN images i ON n.target_id = i.id AND (n.type = 'like' OR n.type = 'comment')
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 20
    ";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    echo json_encode(['notifications' => $notifications]);
    // Tandai notifikasi sebagai sudah dibaca
} elseif ($action === 'mark_read' && isset($_GET['id'])) {
    $notificationId = (int)$_GET['id'];
    $stmt = mysqli_prepare($koneksi, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $notificationId, $currentUserId);
    mysqli_stmt_execute($stmt);
    echo json_encode(['status' => 'success']);
    // Hitung jumlah notifikasi yang belum dibaca
} elseif ($action === 'unread_count') {
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(id) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    mysqli_stmt_bind_param($stmt, "i", $currentUserId);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
    echo json_encode(['unread_count' => $count]);
}
