<?php
// Controller hanya butuh koneksi database yang bersih.
require_once '../../config/inc_koneksi.php';

// Cek jika request method POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil data dari form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Semua validasi Anda (email, password, dll.) tetap di sini
    if (!$username || !$email || !$password || !$password_confirm) {
        $error = urlencode("Mohon isi semua bidang.");
        header("Location: ../views/login/registrasi.php?error=$error");
        exit;
    }
    // ... validasi lainnya ...
    
    // Cek duplikasi email
    $stmt_check = $koneksi->prepare("SELECT id FROM login WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        $error = urlencode("Email sudah terdaftar.");
        header("Location: ../views/login/registrasi.php?error=$error");
        exit;
    }
    $stmt_check->close();

    // Hash password & set role
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 1;

    // Simpan data user baru ke database
    $stmt_login = $koneksi->prepare("INSERT INTO login (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt_login->bind_param("sssi", $username, $email, $password_hash, $role);
    
    if ($stmt_login->execute()) {
        $new_user_id = $stmt_login->insert_id;
        $stmt_login->close();

        // Membuat profil kosong
        // Pastikan nama kolom di DB Anda adalah 'user_id' dan 'nama_lengkap'
        $stmt_profile = $koneksi->prepare("INSERT INTO user_profiles (user_id, nama_lengkap) VALUES (?, ?)");
        $stmt_profile->bind_param("is", $new_user_id, $username);
        
        if ($stmt_profile->execute()) {
            // JIKA SEMUA BERHASIL
            $stmt_profile->close();
            $koneksi->close();
            $success = urlencode("Registrasi berhasil! Silakan masuk.");
            header("Location: ../views/login/login.php?success=$success");
            exit;
        } else {
            // Jika GAGAL buat profil, hapus user yang sudah terlanjur dibuat
            $koneksi->query("DELETE FROM login WHERE id = $new_user_id");
            $error = urlencode("DATABASE ERROR: " . $stmt_profile->error);
            header("Location: ../views/login/registrasi.php?error=$error");
            exit;
        }
    } else {
        $error = urlencode("REGISTRATION ERROR: " . $stmt_login->error);
        header("Location: ../views/login/registrasi.php?error=$error");
        exit;
    }
} else {
    header("Location: ../views/login/registrasi.php");
    exit;
}