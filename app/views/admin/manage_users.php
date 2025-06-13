<?php
// ==============================
// Bagian: Inisialisasi Sesi & Koneksi Database
// ==============================
session_start();
require_once '../../../config/inc_koneksi.php';

// ==============================
// Bagian: Proses Hapus Pengguna
// ==============================
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int) $_GET['delete'];

    // Cegah admin menghapus akun sendiri
    if (isset($_SESSION['id']) && $user_id_to_delete == $_SESSION['id']) {
        header("Location: manage_users.php?status=self_delete_error");
        exit;
    }

    // Proses hapus pengguna
    $query_delete = "DELETE FROM login WHERE id = $user_id_to_delete";
    mysqli_query($koneksi, $query_delete);

    header("Location: manage_users.php?status=deleted");
    exit;
}

// ==============================
// Bagian: Ambil Profil Admin Login
// ==============================
$admin_id = (int) ($_SESSION['id'] ?? 0);
$profil_query = "SELECT * FROM user_profiles WHERE user_id = $admin_id";
$profil_result = mysqli_query($koneksi, $profil_query);
$profil = mysqli_fetch_assoc($profil_result);
$username = $_SESSION['username'] ?? 'Admin';

// ==============================
// Bagian: Ambil Data Seluruh Pengguna
// ==============================
$query_users = "SELECT id, username, email, created_at FROM login ORDER BY created_at DESC";
$result_users = mysqli_query($koneksi, $query_users);
$users = [];
if ($result_users) {
    while ($row = mysqli_fetch_assoc($result_users)) {
        if ($row['id'] != $admin_id) {
            $users[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- ==========================
         Bagian: Header & Resource
    =========================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_manage_image_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Manajemen Pengguna - MizuPix Admin</title>
</head>

<body>
    <!-- ==========================
         Bagian: Sidebar Navigasi
    =========================== -->
    <aside class="sidebar">
        <a href="dashboard_admin.php" class="sidebar__logo">
            <img src="../../../public/assets/img/loading_logo.png" alt="MizuPix Logo">
            <span>MizuPix</span>
        </a>

        <nav class="sidebar__nav">
            <ul>
                <li>
                    <a href="dashboard_admin.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a>
                </li>
                <li>
                    <a href="manage_images.php"><i class='bx bxs-image-alt'></i><span>Gambar</span></a>
                </li>
                <li>
                    <a href="manage_users.php" class="active"><i class='bx bxs-user-account'></i><span>Pengguna</span></a>
                </li>
                <li>
                    <a href="manage_categories.php"><i class='bx bxs-category-alt'></i><span>Kategori</span></a>
                </li>
                <li>
                    <a href="manage_messages.php"><i class='bx bxs-chat'></i><span>Pesan</span></a>
                </li>
                <li>
                    <a href="manage_account.php"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- ==========================
         Bagian: Konten Utama & Header
    =========================== -->
    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Manajemen Pengguna</h1>
                <p>Kelola semua pengguna yang terdaftar di MizuPix</p>
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

        <!-- ==========================
             Bagian: Widget Daftar Pengguna
        =========================== -->
        <section class="widget-container">
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Seluruh Pengguna</h3>
                    <div class="search-container">
                        <i class='bx bx-search'></i>
                        <input type="text" id="searchInput" placeholder="Cari pengguna berdasarkan nama atau email...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="content-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="username">Username</th>
                                    <th class="sortable" data-sort="email">Email</th>
                                    <th class="sortable" data-sort="created_at">Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==========================
         Bagian: Script JavaScript
    =========================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bagian: Konversi Data Pengguna dari PHP ke JS
            const usersData = <?php echo json_encode($users); ?>;
            const tableBody = document.getElementById('userTableBody');
            const searchInput = document.getElementById('searchInput');

            let currentSort = {
                key: 'created_at',
                direction: 'desc'
            };

            // Bagian: Fungsi Render Tabel Pengguna
            function renderTable(data) {
                tableBody.innerHTML = '';

                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 2rem;">Data tidak ditemukan.</td></tr>`;
                    return;
                }

                data.forEach(user => {
                    const tr = document.createElement('tr');

                    const date = new Date(user.created_at);
                    const formattedDate = date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });

                    tr.innerHTML = `
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${formattedDate}</td>
                    <td>
                        <div class="action-buttons">
                            <a href="manage_users.php?delete=${user.id}" class="btn-action btn-delete" title="Hapus Pengguna" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait pengguna ini akan hilang.');"><i class='bx bxs-trash'></i></a>
                        </div>
                    </td>
                `;
                    tableBody.appendChild(tr);
                });
            }

            // Bagian: Fungsi Sorting Data Pengguna
            function sortData(data, key, direction) {
                data.sort((a, b) => {
                    const valA = a[key] ? a[key].toString().toLowerCase() : '';
                    const valB = b[key] ? b[key].toString().toLowerCase() : '';

                    let comparison = 0;
                    if (valA > valB) {
                        comparison = 1;
                    } else if (valA < valB) {
                        comparison = -1;
                    }

                    return direction === 'asc' ? comparison : -comparison;
                });
                return data;
            }

            // Bagian: Event Live Search Pengguna
            searchInput.addEventListener('keyup', () => {
                const searchTerm = searchInput.value.toLowerCase();

                const filteredData = usersData.filter(user => {
                    return (user.username.toLowerCase().includes(searchTerm) ||
                        user.email.toLowerCase().includes(searchTerm)
                    );
                });

                renderTable(sortData(filteredData, currentSort.key, currentSort.direction));
            });

            // Bagian: Event Sorting Kolom Tabel
            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', () => {
                    const sortKey = header.getAttribute('data-sort');

                    if (currentSort.key === sortKey) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.key = sortKey;
                        currentSort.direction = 'asc';
                    }

                    document.querySelectorAll('.sortable').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                    });
                    header.classList.add(`sort-${currentSort.direction}`);

                    searchInput.dispatchEvent(new Event('keyup'));
                });
            });

            // Bagian: Render Tabel Saat Halaman Dimuat
            const sortedInitialData = sortData(usersData, currentSort.key, currentSort.direction);
            document.querySelector(`[data-sort="${currentSort.key}"]`).classList.add(`sort-${currentSort.direction}`);
            renderTable(sortedInitialData);

            // Bagian: Dropdown Profil Admin
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