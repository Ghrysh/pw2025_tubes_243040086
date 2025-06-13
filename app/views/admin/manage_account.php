<?php
session_start();
require_once '../../../config/inc_koneksi.php';

// =================================================================
// PROSES LOGIKA PHP (TAMBAH, HAPUS, AMBIL DATA)
// =================================================================

$errors = [];
// Proses Tambah Akun Admin Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin_account'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    // Validasi dasar
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        // Cek jika username atau email sudah ada
        $check_query = "SELECT * FROM login WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($koneksi, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Username atau email sudah digunakan.";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Tambahkan user dengan role 'admin' (role = 2)
            $insert_query = "INSERT INTO login (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 2)";
            if (mysqli_query($koneksi, $insert_query)) {
                header("Location: manage_account.php?status=added");
                exit;
            } else {
                $errors[] = "Gagal menambahkan akun ke database.";
            }
        }
    }
}

// Proses Hapus Akun Admin
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int) $_GET['delete'];
    $admin_id_logged_in = (int) ($_SESSION['id'] ?? 0);

    // Proteksi agar admin tidak bisa menghapus akunnya sendiri
    if ($user_id_to_delete === $admin_id_logged_in) {
        header("Location: manage_account.php?status=self_delete_error");
        exit;
    } else {
        mysqli_query($koneksi, "DELETE FROM login WHERE id = $user_id_to_delete AND role = 2");
        header("Location: manage_account.php?status=deleted");
        exit;
    }
}

// Ambil profil admin yang sedang login untuk header
$admin_id_logged_in = (int) ($_SESSION['id'] ?? 0);
$profil_query = "SELECT * FROM user_profiles WHERE user_id = $admin_id_logged_in";
$profil_result = mysqli_query($koneksi, $profil_query);
$profil = mysqli_fetch_assoc($profil_result);

// Ambil semua data akun dengan role 'admin'
$query_admins = "SELECT id, username, email, created_at FROM login WHERE role = 2 ORDER BY created_at DESC";
$result_admins = mysqli_query($koneksi, $query_admins);
$admins = [];
if ($result_admins) {
    while ($row = mysqli_fetch_assoc($result_admins)) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_manage_messages_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Kelola Akun - MizuPix Admin</title>
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
                <li><a href="manage_account.php" class="active"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Kelola Akun Admin</h1>
                <p>Tambah atau hapus akun dengan hak akses admin</p>
            </div>
            <div class="header__profile">
                <button class="profile-btn">
                    <?php if (!empty($profil['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($profil['foto']); ?>" alt="Foto Profil" class="profile-icon" />
                    <?php else: ?>
                        <i class='bx bxs-user-circle'></i>
                    <?php endif; ?>
                </button>
                <div class="profile-dropdown">
                    <a href="../../views/admin/profile_admin.php">Profile</a>
                    <a href="../login/logout.php" class="logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="grid-container-2col">
            <div class="grid-item">
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Akun Admin Baru</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="manage_account.php" class="form-layout">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="add_admin_account" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bxs-user-plus'></i> Tambah Akun
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Akun Admin</h3>
                        <div class="search-container">
                            <i class='bx bx-search'></i>
                            <input type="text" id="searchInput" placeholder="Cari admin...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="content-table">
                                <thead>
                                    <tr>
                                        <th class="sortable" data-sort="username">Username</th>
                                        <th class="sortable" data-sort="email">Email</th>
                                        <th class="sortable" data-sort="created_at">Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="adminTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminsData = <?php echo json_encode($admins); ?>;
            const tableBody = document.getElementById('adminTableBody');
            const searchInput = document.getElementById('searchInput');
            const adminLoggedInId = <?php echo $admin_id_logged_in; ?>;

            let currentSort = {
                key: 'created_at',
                direction: 'desc'
            };

            function renderTable(data) {
                tableBody.innerHTML = '';
                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 2rem;">Tidak ada data admin untuk ditampilkan.</td></tr>`;
                    return;
                }

                data.forEach(admin => {
                    const tr = document.createElement('tr');
                    const date = new Date(admin.created_at);
                    const formattedDate = date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });

                    // Tentukan apakah tombol hapus harus dinonaktifkan
                    const isSelf = admin.id == adminLoggedInId;
                    const deleteButton = isSelf ?
                        `<button class="btn-action btn-delete" disabled title="Tidak dapat menghapus akun sendiri"><i class='bx bxs-trash'></i></button>` :
                        `<a href="manage_account.php?delete=${admin.id}" class="btn-action btn-delete" title="Hapus Akun" onclick="return confirm('Apakah Anda yakin ingin menghapus akun admin ini?');"><i class='bx bxs-trash'></i></a>`;

                    tr.innerHTML = `
                    <td>${admin.username} ${isSelf ? '<span class="badge-self">(Anda)</span>' : ''}</td>
                    <td>${admin.email}</td>
                    <td>${formattedDate}</td>
                    <td>
                        <div class="action-buttons">${deleteButton}</div>
                    </td>
                `;
                    tableBody.appendChild(tr);
                });
            }

            function sortData(data, key, direction) {
                data.sort((a, b) => {
                    const valA = a[key] ? a[key].toString().toLowerCase() : '';
                    const valB = b[key] ? b[key].toString().toLowerCase() : '';
                    let comparison = valA.localeCompare(valB);
                    return direction === 'asc' ? comparison : -comparison;
                });
                return data;
            }

            searchInput.addEventListener('keyup', () => {
                const searchTerm = searchInput.value.toLowerCase();
                const filteredData = adminsData.filter(admin =>
                    admin.username.toLowerCase().includes(searchTerm) ||
                    admin.email.toLowerCase().includes(searchTerm)
                );
                renderTable(sortData(filteredData, currentSort.key, currentSort.direction));
            });

            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', () => {
                    const sortKey = header.getAttribute('data-sort');
                    if (currentSort.key === sortKey) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.key = sortKey;
                        currentSort.direction = 'asc';
                    }
                    document.querySelectorAll('.sortable').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
                    header.classList.add(`sort-${currentSort.direction}`);
                    searchInput.dispatchEvent(new Event('keyup'));
                });
            });

            const sortedInitialData = sortData(adminsData, currentSort.key, currentSort.direction);
            document.querySelector(`[data-sort="${currentSort.key}"]`).classList.add(`sort-${currentSort.direction}`);
            renderTable(sortedInitialData);

            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.querySelector('.profile-dropdown');
            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', () => {
                    profileDropdown.classList.toggle('show');
                });
                window.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>

</html>