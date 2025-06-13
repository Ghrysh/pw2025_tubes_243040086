<?php
session_start();
require_once '../../config/inc_koneksi.php';

// Judul: Fungsi untuk mengatur sesi login dan cookie "Remember Me"
function set_login_session($koneksi, $id, $email, $role, $username = null, $remember_me = false)
{
    // Judul: Atur Sesi Standar
    $_SESSION['id'] = $id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    if ($username) {
        $_SESSION['username'] = $username;
    }

    // Judul: Proses "Remember Me" jika dicentang
    if ($remember_me) {
        // Judul: Hapus token lama user
        $stmt_delete = $koneksi->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Judul: Buat token baru dan simpan ke database
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $validator_hash = hash('sha256', $validator);

        $expires = new DateTime('now');
        $expires->add(new DateInterval('P30D'));
        $expires_db = $expires->format('Y-m-d H:i:s');

        $stmt_insert = $koneksi->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("isss", $id, $selector, $validator_hash, $expires_db);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Judul: Atur cookie remember_me di browser
        $cookie_value = $selector . ':' . $validator;
        $cookie_duration = $expires->getTimestamp();
        setcookie('remember_me', $cookie_value, $cookie_duration, "/");
    }
}

// Judul: Proses login saat form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    // Judul: Validasi input email dan password
    if (!$email || !$password) {
        $error = urlencode("Mohon masukkan email dan kata sandi.");
        header("Location: ../views/login/login.php?error=$error");
        exit;
    }

    // Judul: Cek user di tabel login
    $stmt = $koneksi->prepare("SELECT id, email, password, role, username FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Judul: Verifikasi password dan set session
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            set_login_session($koneksi, $user['id'], $user['email'], $user['role'], $user['username'], $remember_me);
            if ($user['role'] == 1) {
                header("Location: ../views/user/dashboard_user.php");
            } else if ($user['role'] == 2) {
                header("Location: ../views/admin/dashboard_admin.php");
            }
            exit;
        }
    }
    $stmt->close();

    // Judul: Jika login gagal
    $error = urlencode("Email atau kata sandi salah.");
    header("Location: ../views/login/login.php?error=$error");
    exit;
}
