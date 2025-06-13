<?php
// File: app/controller/detail_controller.php

// Pastikan session sudah dimulai di file yang memanggil
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengambil semua detail untuk satu gambar, termasuk info uploader,
 * jumlah like, dan apakah pengguna saat ini sudah me-like atau menyimpan.
 */
function getImageDetails($imageId, $currentUserId, $koneksi)
{
    $query = "
        SELECT 
            i.*, 
            up.nama_lengkap, 
            up.foto as user_photo,
            l.username,
            (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comment_count,
            (SELECT COUNT(*) FROM bookmarks WHERE image_id = i.id AND user_id = ?) as is_bookmarked,
            (SELECT COUNT(*) FROM likes WHERE image_id = i.id AND user_id = ?) as has_liked
        FROM images i
        JOIN user_profiles up ON i.user_id = up.user_id
        JOIN login l ON i.user_id = l.id
        WHERE i.id = ?
    ";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "iii", $currentUserId, $currentUserId, $imageId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Mengambil semua komentar untuk satu gambar.
 */
function getComments($imageId, $koneksi)
{
    $comments = [];
    $query = "
        SELECT c.comment_text, c.commented_at, up.nama_lengkap, up.foto as user_photo, l.username
        FROM comments c
        JOIN user_profiles up ON c.user_id = up.user_id
        JOIN login l ON c.user_id = l.id
        WHERE c.image_id = ?
        ORDER BY c.commented_at DESC
    ";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $imageId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    return $comments;
}

/**
 * Mengambil gambar rekomendasi secara acak, mengecualikan gambar saat ini.
 */
function getRecommendedImages($currentImageId, $limit, $koneksi)
{
    $images = [];
    $query = "SELECT id, image_path, title FROM images WHERE id != ? ORDER BY RAND() LIMIT ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $currentImageId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
    return $images;
}
