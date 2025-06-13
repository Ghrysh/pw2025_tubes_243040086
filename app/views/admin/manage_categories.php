<?php
session_start();
require_once '../../../config/inc_koneksi.php';

// =================================================================
// PROSES LOGIKA PHP (TAMBAH, EDIT, HAPUS)
// =================================================================

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($koneksi, $_POST['category_name']);
    if (!empty($name)) {
        mysqli_query($koneksi, "INSERT INTO categories (name) VALUES ('$name')");
    }
    header("Location: manage_categories.php?status=added");
    exit;
}

// Proses Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int) $_POST['category_id'];
    $name = mysqli_real_escape_string($koneksi, $_POST['category_name']);
    if (!empty($name)) {
        mysqli_query($koneksi, "UPDATE categories SET name = '$name' WHERE id = $id");
    }
    header("Location: manage_categories.php?status=edited");
    exit;
}

// Proses Hapus Kategori
if (isset($_GET['delete'])) {
    $category_id = (int) $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM categories WHERE id = $category_id");
    header("Location: manage_categories.php?status=deleted");
    exit;
}

// Ambil profil admin yang sedang login untuk header
$admin_id = (int) ($_SESSION['id'] ?? 0);
$profil_query = "SELECT * FROM user_profiles WHERE user_id = $admin_id";
$profil_result = mysqli_query($koneksi, $profil_query);
$profil = mysqli_fetch_assoc($profil_result);

// Ambil semua data kategori
$query_categories = "SELECT * FROM categories ORDER BY name ASC";
$result_categories = mysqli_query($koneksi, $query_categories);
$categories = [];
if ($result_categories) {
    while ($row = mysqli_fetch_assoc($result_categories)) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_manage_categories_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Manajemen Kategori - MizuPix Admin</title>
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
                <li><a href="manage_categories.php" class="active"><i class='bx bxs-category-alt'></i><span>Kategori</span></a></li>
                <li><a href="manage_messages.php"><i class='bx bxs-chat'></i><span>Pesan</span></a></li>
                <li><a href="manage_account.php"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Manajemen Kategori</h1>
                <p>Tambah, edit, dan hapus kategori gambar</p>
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
                        <h3>Tambah Kategori Baru</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="manage_categories.php" class="form-layout">
                            <div class="form-group">
                                <label for="category_name">Nama Kategori</label>
                                <input type="text" id="category_name" name="category_name" class="form-control" placeholder="Contoh: Alam, Perkotaan" required>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bx-plus'></i> Tambah Kategori
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Kategori</h3>
                        <div class="search-container">
                            <i class='bx bx-search'></i>
                            <input type="text" id="searchInput" placeholder="Cari kategori...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="content-table">
                                <thead>
                                    <tr>
                                        <th class="sortable" data-sort="name">Nama Kategori</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryTableBody">
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
            const categoriesData = <?php echo json_encode($categories); ?>;
            const tableBody = document.getElementById('categoryTableBody');
            const searchInput = document.getElementById('searchInput');

            let currentSort = {
                key: 'name',
                direction: 'asc'
            };

            function renderTable(data) {
                tableBody.innerHTML = '';
                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 2rem;">Data tidak ditemukan.</td></tr>`;
                    return;
                }

                data.forEach(category => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${category.name}</td>
                    <td>
                        <div class="action-buttons-inline">
                            <form method="POST" class="inline-edit-form">
                                <input type="hidden" name="category_id" value="${category.id}">
                                <input type="text" name="category_name" class="form-control-inline" value="${category.name}" required>
                                <button type="submit" name="edit_category" class="btn-action btn-edit" title="Simpan Perubahan"><i class='bx bxs-save'></i></button>
                            </form>
                            <a href="manage_categories.php?delete=${category.id}" class="btn-action btn-delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?');"><i class='bx bxs-trash'></i></a>
                        </div>
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
                const filteredData = categoriesData.filter(cat => cat.name.toLowerCase().includes(searchTerm));
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

            const sortedInitialData = sortData(categoriesData, currentSort.key, currentSort.direction);
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