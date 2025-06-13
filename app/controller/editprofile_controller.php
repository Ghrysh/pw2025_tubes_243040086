<?php
// --- Inisialisasi Session dan Variabel Utama ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$routeApps = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online';
$appsName = "Gallery_Seni_Online";

require_once $routeApps . '/config/inc_koneksi.php';

// --- Fungsi: Mengambil Data Profil User ---

function getProfilUser($id, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// --- Fungsi: Mengambil Data Username User ---

function getUser($id, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT username FROM login WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// --- Fungsi: Proses Update Profil User ---

function processProfileUpdate($id, $postData, $fileData, $koneksi)
{
    $nama_lengkap = mysqli_real_escape_string($koneksi, $postData['nama_lengkap']);
    $tentang = mysqli_real_escape_string($koneksi, $postData['tentang']);
    $situs_web = mysqli_real_escape_string($koneksi, $postData['situs_web']);
    $username = mysqli_real_escape_string($koneksi, $postData['username']);

    // --- Validasi Username Unik ---

    $stmt_cek_username = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM login WHERE username = ? AND id != ?");
    mysqli_stmt_bind_param($stmt_cek_username, "si", $username, $id);
    mysqli_stmt_execute($stmt_cek_username);
    $result = mysqli_stmt_get_result($stmt_cek_username);
    $row = mysqli_fetch_row($result);
    $username_count = $row[0];
    if ($username_count > 0) {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Username sudah digunakan. Silakan pilih username lain.'];
        return;
    }

    // --- Cek Apakah Profil Sudah Ada ---

    $stmt_cek = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_cek, "i", $id);
    mysqli_stmt_execute($stmt_cek);
    $result = mysqli_stmt_get_result($stmt_cek);
    $row = mysqli_fetch_row($result);
    $count = $row[0];

    // --- Update atau Insert Data Profil ---

    if ($count > 0) {
        $stmt_profil = mysqli_prepare($koneksi, "UPDATE user_profiles SET nama_lengkap = ?, tentang = ?, situs_web = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_profil, "sssi", $nama_lengkap, $tentang, $situs_web, $id);
        $profilUpdated = mysqli_stmt_execute($stmt_profil);
    } else {
        $stmt_profil = mysqli_prepare($koneksi, "INSERT INTO user_profiles (user_id, nama_lengkap, tentang, situs_web) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_profil, "isss", $id, $nama_lengkap, $tentang, $situs_web);
        $profilUpdated = mysqli_stmt_execute($stmt_profil);
    }

    // --- Update Username di Tabel Login ---

    $stmt_login = mysqli_prepare($koneksi, "UPDATE login SET username = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_login, "si", $username, $id);
    $usernameUpdated = mysqli_stmt_execute($stmt_login);

    if (!$profilUpdated || !$usernameUpdated) {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Gagal memperbarui data teks profil.'];
        return;
    }

    // --- Proses Upload Foto Profil ---

    if (isset($fileData['foto']) && $fileData['foto']['error'] == UPLOAD_ERR_OK) {
        $foto = $fileData['foto'];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Gallery_Seni_Online/public/assets/img/profile_user/';
        $dbPathPrefix = '/Gallery_Seni_Online/public/assets/img/profile_user/';

        if ($foto['size'] > 2097152) { // 2MB
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
            return;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($foto['type'], $allowedTypes)) {
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Format file tidak valid. Hanya JPG, PNG, GIF.'];
            return;
        }

        $fileExtension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('profile_') . '.' . $fileExtension;
        $destination = $uploadDir . $newFileName;
        $dbPath = $dbPathPrefix . $newFileName;

        $oldProfileData = getProfilUser($id, $koneksi);
        $defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
        if ($oldProfileData && !empty($oldProfileData['foto']) && $oldProfileData['foto'] !== $defaultPhotoPath) {
            $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . $oldProfileData['foto'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        if (move_uploaded_file($foto['tmp_name'], $destination)) {
            $stmt_foto = mysqli_prepare($koneksi, "UPDATE user_profiles SET foto = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt_foto, "si", $dbPath, $id);
            if (!mysqli_stmt_execute($stmt_foto)) {
                $_SESSION['notification'] = ['type' => 'error', 'message' => 'Gagal menyimpan path foto ke database.'];
                return;
            }
        } else {
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Gagal mengunggah file. Periksa izin folder.'];
            return;
        }
    }

    // --- Notifikasi Berhasil ---

    if (!isset($_SESSION['notification'])) {
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Profil berhasil diperbarui!'];
    }
}

// --- Fungsi: Hapus Foto Profil User ---

function deleteProfilePhoto($id, $koneksi)
{
    $appsName = "Gallery_Seni_Online";
    $defaultPhotoPath = "/$appsName/public/assets/img/profile_user/blank-profile.png";

    $oldProfileData = getProfilUser($id, $koneksi);
    if ($oldProfileData && !empty($oldProfileData['foto']) && $oldProfileData['foto'] !== $defaultPhotoPath) {
        $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . $oldProfileData['foto'];
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE user_profiles SET foto = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $defaultPhotoPath, $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Foto profil berhasil dihapus.'];
    } else {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Gagal menghapus foto.'];
    }
}

// --- Main Logic: Proses Request POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['id'];

    if (isset($_POST['simpan'])) {
        processProfileUpdate($id, $_POST, $_FILES, $koneksi);
        header("Location: ../views/user/profile_user.php");
        exit();
    }

    if (isset($_POST['hapus_foto'])) {
        deleteProfilePhoto($id, $koneksi);
        header("Location: ../views/user/profile_user.php");
        exit();
    }
}
