<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$routeApps = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online';
$appsName = "Gallery_Seni_Online";
require_once $routeApps . '/config/inc_koneksi.php';
$uploadMsg = "";

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if (!isset($_SESSION['id'])) {
    die('User  ID tidak ditemukan di session. Silakan login terlebih dahulu.');
}

$user_id = (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $category_id = $_POST['category'] ?? '';

    // Validasi input
    if (!$title || !$caption || !$category_id) {
        $uploadMsg = "<p class='error'>Please fill in all required fields.</p>";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
        $uploadMsg = "<p class='error'>Please select an image to upload.</p>";
    } else {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $uploadMsg = "<p class='error'>Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.</p>";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $uploadMsg = "<p class='error'>File size exceeds 5MB limit.</p>";
        } else {
            // Tentukan direktori upload
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online/public/assets/img/uploaded_user/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Buat nama file baru
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = '/Gallery_Seni_Online/public/assets/img/uploaded_user/' . $new_file_name; // Path relatif untuk disimpan di database

            // Pindahkan file ke direktori upload
            if (!move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $uploadMsg = "<p class='error'>Failed to move uploaded file.</p>";
            } else {
                // Simpan informasi gambar ke database
                $stmt = $koneksi->prepare("INSERT INTO images (user_id, title, caption, category_id, image_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($stmt === false) {
                    $uploadMsg = "<p class='error'>Prepare failed: " . htmlspecialchars($koneksi->error) . "</p>";
                } else {
                    $stmt->bind_param("issss", $user_id, $title, $caption, $category_id, $upload_path);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = 'Image uploaded successfully!';
                        header('Location: ../views/user/profile_user.php');
                        exit;
                    } else {
                        $uploadMsg = "<p class='error'>Database error: " . htmlspecialchars($stmt->error) . "</p>";
                    }
                    $stmt->close();
                }
            }
        }
    }
}
$koneksi->close();
