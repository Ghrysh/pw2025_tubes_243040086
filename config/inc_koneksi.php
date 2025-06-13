<?php
// Koneksi ke Database
$host  = "localhost";
$user  = "root";
$pass  = "";
$db    = "db_artgallery";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Verifikasi Fitur "Ingat Saya"
if (!isset($_SESSION['id']) && isset($_COOKIE['remember_me'])) {

    // Ambil Selector dan Validator dari Cookie
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if ($selector && $validator) {
        // Cari Token Berdasarkan Selector di Database
        $stmt = $koneksi->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_data = $result->fetch_assoc();
        $stmt->close();

        if ($token_data) {
            // Verifikasi Validator Token
            $validator_from_cookie = $validator;
            $hashed_validator_from_db = $token_data['validator_hash'];

            if (hash_equals(hash('sha256', $validator_from_cookie), $hashed_validator_from_db)) {

                // Login Otomatis dan Buat Sesi Pengguna
                $user_id = $token_data['user_id'];

                // Ambil Data User dari Tabel Login
                $stmt_user = $koneksi->prepare("SELECT id, email FROM login WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $user_data = $result_user->fetch_assoc();

                if ($user_data) {
                    $_SESSION['id'] = $user_data['id'];
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['role'] = 1;

                    // Rotasi Validator Token (Token Rotation)
                    $new_validator = bin2hex(random_bytes(32));
                    $new_validator_hash = hash('sha256', $new_validator);
                    $stmt_update = $koneksi->prepare("UPDATE auth_tokens SET validator_hash = ? WHERE selector = ?");
                    $stmt_update->bind_param("ss", $new_validator_hash, $selector);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Perbarui Cookie dengan Validator Baru
                    setcookie('remember_me', $selector . ':' . $new_validator, time() + (86400 * 30), "/");
                }
            } else {
                // Hapus Token Jika Validator Tidak Cocok
                $stmt_del = $koneksi->prepare("DELETE FROM auth_tokens WHERE selector = ?");
                $stmt_del->bind_param("s", $selector);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }
    }
}
