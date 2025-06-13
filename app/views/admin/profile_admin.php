<?php
session_start();
require_once '../../../config/inc_koneksi.php';

// Jika tidak ada session id, redirect ke login
if (!isset($_SESSION['id'])) {
    header('Location: ../login/login.php');
    exit;
}

$admin_id = (int)$_SESSION['id'];
$message = '';
$message_type = '';

// === PROSES UPDATE PROFIL (USERNAME & EMAIL) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);

    // Validasi dasar
    if (!empty($username) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Cek apakah username atau email baru sudah digunakan oleh user lain
        $check_query = "SELECT id FROM login WHERE (username = '$username' OR email = '$email') AND id != $admin_id";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) == 0) {
            $update_query = "UPDATE login SET username = '$username', email = '$email' WHERE id = $admin_id";
            if (mysqli_query($koneksi, $update_query)) {
                $_SESSION['username'] = $username; // Update session username
                $message = 'Informasi profil berhasil diperbarui.';
                $message_type = 'success';
            } else {
                $message = 'Terjadi kesalahan saat memperbarui profil.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Username atau Email sudah digunakan oleh akun lain.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Mohon isi username dan email dengan format yang benar.';
        $message_type = 'danger';
    }
}

// === PROSES UPDATE FOTO PROFIL ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../../../public/uploads/profiles/';
        // Buat direktori jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = uniqid() . '-' . basename($_FILES['profile_photo']['name']);
        $file_path = $upload_dir . $file_name;
        $db_path = '../../../public/uploads/profiles/' . $file_name; // Path untuk disimpan di DB

        // Pindahkan file yang diupload
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Hapus foto lama jika ada
            $old_photo_query = "SELECT foto FROM user_profiles WHERE user_id = $admin_id";
            $old_photo_result = mysqli_query($koneksi, $old_photo_query);
            if ($old_photo_row = mysqli_fetch_assoc($old_photo_result)) {
                if (!empty($old_photo_row['foto']) && file_exists($old_photo_row['foto'])) {
                    // unlink($old_photo_row['foto']);
                }
            }

            // Update path foto di database
            $update_photo_query = "UPDATE user_profiles SET foto = '$db_path' WHERE user_id = $admin_id";
            // Jika user belum punya profil, buat baru
            if (mysqli_num_rows(mysqli_query($koneksi, "SELECT user_id FROM user_profiles WHERE user_id = $admin_id")) == 0) {
                $update_photo_query = "INSERT INTO user_profiles (user_id, foto) VALUES ($admin_id, '$db_path')";
            }

            if (mysqli_query($koneksi, $update_photo_query)) {
                $message = 'Foto profil berhasil diperbarui.';
                $message_type = 'success';
            } else {
                $message = 'Gagal menyimpan path foto ke database.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Gagal mengupload file.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Tidak ada file yang dipilih atau terjadi error upload.';
        $message_type = 'danger';
    }
}

// === PROSES UPDATE PASSWORD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } else {
        // Ambil hash password saat ini dari DB
        $pass_query = "SELECT password FROM login WHERE id = $admin_id";
        $pass_result = mysqli_query($koneksi, $pass_query);
        $admin_data = mysqli_fetch_assoc($pass_result);

        // Verifikasi password saat ini
        if (password_verify($current_password, $admin_data['password'])) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass_query = "UPDATE login SET password = '$new_hashed_password' WHERE id = $admin_id";
            if (mysqli_query($koneksi, $update_pass_query)) {
                $message = 'Password berhasil diperbarui.';
                $message_type = 'success';
            } else {
                $message = 'Gagal memperbarui password.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Password saat ini salah.';
            $message_type = 'danger';
        }
    }
}


// Ambil data terbaru untuk ditampilkan
$query_admin_data = "
    SELECT l.username, l.email, up.foto 
    FROM login l
    LEFT JOIN user_profiles up ON l.id = up.user_id
    WHERE l.id = $admin_id
";
$result_admin_data = mysqli_query($koneksi, $query_admin_data);
$admin_display = mysqli_fetch_assoc($result_admin_data);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_profile_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Profil Admin - MizuPix</title>
</head>

<body>
    <aside class="sidebar">
        <a href="dashboard_admin.php" class="sidebar__logo">
            <img src="../../../public/assets/img/loading_logo.png" alt="MizuPix Logo">
            <span>MizuPix</span>
        </a>

        <nav class="sidebar__nav">
            <ul>
                <li><a href="dashboard_admin.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
                <li><a href="manage_images.php"><i class='bx bxs-image-alt'></i><span>Gambar</span></a></li>
                <li><a href="manage_users.php"><i class='bx bxs-user-account'></i><span>Pengguna</span></a></li>
                <li><a href="manage_categories.php"><i class='bx bxs-category-alt'></i><span>Kategori</span></a></li>
                <li><a href="manage_messages.php"><i class='bx bxs-chat'></i><span>Pesan</span></a></li>
                <li><a href="manage_account.php"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Profil Saya</h1>
                <p>Kelola informasi dan keamanan akun Anda</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> show">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid-container">
            <div class="grid-item">
                <div class="card">
                    <div class="card-header">
                        <h3>Foto Profil</h3>
                    </div>
                    <div class="card-body profile-photo-section">
                        <div class="profile-picture-container">
                            <?php if (!empty($admin_display['foto']) && file_exists($admin_display['foto'])): ?>
                                <img src="<?php echo htmlspecialchars($admin_display['foto']); ?>" alt="Foto Profil" class="profile-picture">
                            <?php else: ?>
                                <i class='bx bxs-user-circle default-profile-icon'></i>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="profile_admin.php" enctype="multipart/form-data" class="form-layout">
                            <div class="form-group">
                                <label for="profile_photo_input" class="btn btn-secondary" style="width: 100%; text-align:center;">
                                    <i class='bx bx-upload'></i> Pilih Foto Baru
                                </label>
                                <input type="file" id="profile_photo_input" name="profile_photo" class="form-control-file" accept="image/png, image/jpeg" required>
                                <span id="file-chosen">Tidak ada file dipilih</span>
                            </div>
                            <button type="submit" name="update_photo" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bxs-save'></i> Simpan Foto
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card">
                    <div class="card-header">
                        <h3>Informasi Akun</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile_admin.php" class="form-layout">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($admin_display['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_display['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bxs-save'></i> Simpan Informasi
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3>Ubah Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile_admin.php" class="form-layout">
                            <div class="form-group">
                                <label for="current_password">Password Saat Ini</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Password Baru</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bxs-key'></i> Ubah Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Script untuk menampilkan nama file yang dipilih
        const actualBtn = document.getElementById('profile_photo_input');
        const fileChosen = document.getElementById('file-chosen');
        if (actualBtn) {
            actualBtn.addEventListener('change', function() {
                fileChosen.textContent = this.files[0].name
            });
        }

        // Script untuk menghilangkan notifikasi setelah beberapa detik
        const alertBox = document.querySelector('.alert.show');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 500);
            }, 5000); // 5 detik
        }
    </script>
</body>

</html>