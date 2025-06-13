<?php
// File: app/controller/bookmark_controller.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Menyimpan atau menghapus bookmark.
 * (Fungsi ini tidak diubah, sudah benar)
 */
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

/**
 * [DIPERBAIKI] Mengambil semua gambar yang di-bookmark oleh pengguna,
 * sekarang dengan data username dan fungsionalitas pencarian.
 */
function getBookmarkedImages($userId, $koneksi, $searchQuery = '')
{
    $bookmarks = [];

    // Query ditambahkan JOIN ke tabel 'login' untuk mengambil 'username'
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

    // Tambahkan kondisi pencarian jika $searchQuery tidak kosong
    if (!empty($searchQuery)) {
        // Mencari berdasarkan judul gambar, nama lengkap, atau username pembuat
        $query .= " AND (i.title LIKE ? OR up.nama_lengkap LIKE ? OR l.username LIKE ?)";
    }

    // Asumsi tabel bookmarks Anda punya kolom 'created_at' atau 'saved_at'
    // Ganti 'b.created_at' jika nama kolomnya berbeda
    $query .= " ORDER BY created_at DESC";

    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        if (!empty($searchQuery)) {
            $searchTerm = "%" . $searchQuery . "%";
            // 'isss' = integer, string, string, string
            mysqli_stmt_bind_param($stmt, "isss", $userId, $searchTerm, $searchTerm, $searchTerm);
        } else {
            // 'i' = integer
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


// Bagian AJAX handler di bawah ini bisa Anda aktifkan jika controllernya terpisah
// Saat ini, logika AJAX Anda sepertinya ada di file bookmark_handle_controller.php, jadi bagian ini bisa tetap di-comment.
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id'])) {
    // ...
}
*/
