<?php
require_once '../../../config/inc_koneksi.php';

header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
$suggestions = ['titles' => [], 'users' => [], 'categories' => []];

if (strlen($term) > 0) {
    $searchTerm = '%' . $term . '%';

    // Cari judul gambar
    $stmt = $koneksi->prepare("SELECT DISTINCT title FROM images WHERE title LIKE ? LIMIT 3");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions['titles'][] = $row['title'];
    }
    $stmt->close();

    // Cari username
    $stmt = $koneksi->prepare("SELECT DISTINCT username FROM login WHERE username LIKE ? LIMIT 2");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions['users'][] = $row['username'];
    }
    $stmt->close();

    // Cari kategori
    $stmt = $koneksi->prepare("SELECT DISTINCT name FROM categories WHERE name LIKE ? LIMIT 2");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions['categories'][] = $row['name'];
    }
    $stmt->close();
} else {
    // Rekomendasi awal (contoh: kategori populer/acak)
    $result = $koneksi->query("SELECT name FROM categories ORDER BY RAND() LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $suggestions['categories'][] = $row['name'];
    }
}

echo json_encode($suggestions);
