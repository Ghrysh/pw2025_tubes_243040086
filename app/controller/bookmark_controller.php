<?php
// === Session Start ===
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// === Toggle Bookmark Function ===
function toggleBookmark($userId, $imageId, $koneksi)
{
    // Cek apakah sudah dibookmark
    $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM bookmarks WHERE user_id = ? AND image_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $userId, $imageId);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Jika sudah ada, hapus (un-bookmark)
        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM bookmarks WHERE user_id = ? AND image_id = ?");
        mysqli_stmt_bind_param($stmt_delete, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_delete)) {
            return ['status' => 'removed', 'message' => 'Bookmark dihapus'];
        } else {
            return ['status' => 'error', 'message' => 'Gagal menghapus bookmark'];
        }
    } else {
        // Jika belum ada, tambahkan (bookmark)
        $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO bookmarks (user_id, image_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_insert, "ii", $userId, $imageId);
        if (mysqli_stmt_execute($stmt_insert)) {
            return ['status' => 'saved', 'message' => 'Gambar disimpan'];
        } else {
            return ['status' => 'error', 'message' => 'Gagal menyimpan bookmark'];
        }
    }
}

// === Get Bookmarked Images Function ===
function getBookmarkedImages($userId, $koneksi, $searchQuery = '')
{
    $bookmarks = [];

    $query = "
        SELECT 
            i.id, 
            i.image_path, 
            i.title, 
            up.nama_lengkap, 
            up.foto as user_photo,
            l.username 
        FROM bookmarks b
        JOIN images i ON b.image_id = i.id
        JOIN user_profiles up ON i.user_id = up.user_id
        JOIN login l ON i.user_id = l.id
        WHERE b.user_id = ?
    ";

    if (!empty($searchQuery)) {
        $query .= " AND (i.title LIKE ? OR up.nama_lengkap LIKE ? OR l.username LIKE ?)";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        if (!empty($searchQuery)) {
            $searchTerm = "%" . $searchQuery . "%";
            mysqli_stmt_bind_param($stmt, "isss", $userId, $searchTerm, $searchTerm, $searchTerm);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $userId);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $bookmarks[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    return $bookmarks;
}
