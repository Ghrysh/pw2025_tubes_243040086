<?php
// Selalu mulai sesi di awal
session_start();

// Panggil file koneksi karena logika ini butuh akses database
require_once 'inc_koneksi.php';

// Verifikasi Fitur "Ingat Saya" hanya jika user belum login via sesi
if (!isset($_SESSION['id']) && isset($_COOKIE['remember_me'])) {

    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if ($selector && $validator) {
        $stmt = $koneksi->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_data = $result->fetch_assoc();
        $stmt->close();

        if ($token_data) {
            if (hash_equals(hash('sha256', $validator), $token_data['validator_hash'])) {
                $user_id = $token_data['user_id'];
                $stmt_user = $koneksi->prepare("SELECT id, username, email, role FROM login WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $user_data = $stmt_user->get_result()->fetch_assoc();
                $stmt_user->close();

                if ($user_data) {
                    $_SESSION['id'] = $user_data['id'];
                    $_SESSION['username'] = $user_data['username'];
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['role'] = $user_data['role'];
                    
                    $new_validator = bin2hex(random_bytes(32));
                    $new_validator_hash = hash('sha256', $new_validator);
                    $stmt_update = $koneksi->prepare("UPDATE auth_tokens SET validator_hash = ? WHERE selector = ?");
                    $stmt_update->bind_param("ss", $new_validator_hash, $selector);
                    $stmt_update->execute();
                    $stmt_update->close();

                    setcookie('remember_me', $selector . ':' . $new_validator, time() + (86400 * 30), "/");
                }
            } else {
                $stmt_del = $koneksi->prepare("DELETE FROM auth_tokens WHERE selector = ?");
                $stmt_del->bind_param("s", $selector);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }
    }
}