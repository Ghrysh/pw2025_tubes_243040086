<?php
$host  = "localhost";
$user  = "root";
$pass  = "";
$db    = "db_artgallery";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// --- LOGIKA VERIFIKASI "INGAT SAYA" ---

// Cek hanya jika pengguna BELUM login (session tidak ada) DAN cookie remember_me ADA
if (!isset($_SESSION['id']) && isset($_COOKIE['remember_me'])) {

    // 1. Ambil selector dan validator dari cookie
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if ($selector && $validator) {
        // 2. Cari token di database berdasarkan selector
        $stmt = $koneksi->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_data = $result->fetch_assoc();
        $stmt->close();

        if ($token_data) {
            // 3. Jika token ditemukan, verifikasi validator
            $validator_from_cookie = $validator;
            $hashed_validator_from_db = $token_data['validator_hash'];

            // Cocokkan hash-nya. Gunakan hash_equals untuk keamanan terhadap timing attacks.
            if (hash_equals(hash('sha256', $validator_from_cookie), $hashed_validator_from_db)) {

                // 4. Jika cocok, LOGIN BERHASIL! Buat sesi untuk user.
                $user_id = $token_data['user_id'];

                // Ambil data user dari tabel 'login' atau 'admins'
                // Anda mungkin perlu menyesuaikan query ini sesuai struktur Anda
                $stmt_user = $koneksi->prepare("SELECT id, email FROM login WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $user_data = $result_user->fetch_assoc();

                if ($user_data) {
                    $_SESSION['id'] = $user_data['id'];
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['role'] = 1; // Asumsi user biasa, sesuaikan jika perlu

                    // (Opsional tapi sangat direkomendasikan) Perbarui validator token
                    // untuk mencegah pencurian cookie jangka panjang (token rotation)
                    $new_validator = bin2hex(random_bytes(32));
                    $new_validator_hash = hash('sha256', $new_validator);
                    $stmt_update = $koneksi->prepare("UPDATE auth_tokens SET validator_hash = ? WHERE selector = ?");
                    $stmt_update->bind_param("ss", $new_validator_hash, $selector);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Perbarui cookie dengan validator baru
                    setcookie('remember_me', $selector . ':' . $new_validator, time() + (86400 * 30), "/");
                }
            } else {
                // Jika validator tidak cocok, hapus token dari DB
                $stmt_del = $koneksi->prepare("DELETE FROM auth_tokens WHERE selector = ?");
                $stmt_del->bind_param("s", $selector);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }
    }
}
