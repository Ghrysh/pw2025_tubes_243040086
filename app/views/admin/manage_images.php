<?php
// ==========================
// Mulai Sesi & Koneksi DB
// ==========================
session_start();
require_once '../../../config/inc_koneksi.php';

// ==========================
// Hapus Gambar Jika Diminta
// ==========================
if (isset($_GET['delete'])) {
    $image_id = (int) $_GET['delete'];
    $query_delete = "DELETE FROM images WHERE id = $image_id";
    mysqli_query($koneksi, $query_delete);
    header("Location: manage_images.php?status=deleted");
    exit;
}

// ==========================
// Ambil Profil User
// ==========================
$user_id = (int) ($_SESSION['id'] ?? 0);
$profil_query = "SELECT * FROM user_profiles WHERE user_id = $user_id";
$profil_result = mysqli_query($koneksi, $profil_query);
$profil = mysqli_fetch_assoc($profil_result);
$username = $_SESSION['username'] ?? 'Admin';

// ==========================
// Ambil Data Semua Gambar
// ==========================
$query_images = "
    SELECT 
        images.id, 
        images.title, 
        login.username AS uploader, 
        categories.name AS category_name, 
        images.uploaded_at, 
        images.image_path
    FROM images
    JOIN login ON images.user_id = login.id
    LEFT JOIN categories ON images.category_id = categories.id
    ORDER BY images.uploaded_at DESC
";

$result_images = mysqli_query($koneksi, $query_images);
$images = [];
if ($result_images) {
    while ($row = mysqli_fetch_assoc($result_images)) {
        $images[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- ==========================
         Meta & Link CSS/JS
    ========================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_manage_image_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Manajemen Gambar - MizuPix Admin</title>
</head>

<body>
    <!-- ==========================
         Sidebar Navigasi
    ========================== -->
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
                    <a href="manage_images.php" class="active"><i class='bx bxs-image-alt'></i><span>Gambar</span></a>
                </li>
                <li>
                    <a href="manage_users.php"><i class='bx bxs-user-account'></i><span>Pengguna</span></a>
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
         Konten Utama & Header
    ========================== -->
    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Manajemen Gambar</h1>
                <p>Cari dan kelola semua gambar di MizuPix</p>
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
             Widget Daftar Gambar
        ========================== -->
        <section class="widget-container">
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Seluruh Gambar</h3>
                    <div class="search-container">
                        <i class='bx bx-search'></i>
                        <input type="text" id="searchInput" placeholder="Cari berdasarkan judul, uploader, atau kategori...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="content-table">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th class="sortable" data-sort="title">Judul</th>
                                    <th class="sortable" data-sort="uploader">Uploader</th>
                                    <th class="sortable" data-sort="category_name">Kategori</th>
                                    <th class="sortable" data-sort="uploaded_at">Tanggal Unggah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="imageTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==========================
         Script JS: Tabel, Sort, Search, Dropdown
    ========================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data Gambar dari PHP
            const imagesData = <?php echo json_encode($images); ?>;
            const tableBody = document.getElementById('imageTableBody');
            const searchInput = document.getElementById('searchInput');

            let currentSort = {
                key: 'uploaded_at',
                direction: 'desc'
            };

            // Render Tabel Gambar
            function renderTable(data) {
                tableBody.innerHTML = '';

                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem;">Data tidak ditemukan.</td></tr>`;
                    return;
                }

                data.forEach(image => {
                    const tr = document.createElement('tr');

                    const date = new Date(image.uploaded_at);
                    const formattedDate = date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }) + ', ' + date.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    tr.innerHTML = `
                    <td>
                        <img src="${image.image_path}" alt="${image.title}" class="table-thumbnail">
                    </td>
                    <td>${image.title}</td>
                    <td>${image.uploader || 'N/A'}</td>
                    <td>${image.category_name || 'Tanpa Kategori'}</td>
                    <td>${formattedDate}</td>
                    <td>
                        <div class="action-buttons">
                            <a href="manage_images.php?delete=${image.id}" class="btn-action btn-delete" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus gambar ini?');"><i class='bx bxs-trash'></i></a>
                        </div>
                    </td>
                `;
                    tableBody.appendChild(tr);
                });
            }

            // Sorting Data
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

            // Fitur Pencarian
            searchInput.addEventListener('keyup', () => {
                const searchTerm = searchInput.value.toLowerCase();

                const filteredData = imagesData.filter(image => {
                    return (image.title.toLowerCase().includes(searchTerm) ||
                        (image.uploader && image.uploader.toLowerCase().includes(searchTerm)) ||
                        (image.category_name && image.category_name.toLowerCase().includes(searchTerm))
                    );
                });

                renderTable(sortData(filteredData, currentSort.key, currentSort.direction));
            });

            // Fitur Sorting Kolom
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

            // Render Awal Tabel
            const sortedInitialData = sortData(imagesData, currentSort.key, currentSort.direction);
            document.querySelector(`[data-sort="${currentSort.key}"]`).classList.add(`sort-${currentSort.direction}`);
            renderTable(sortedInitialData);

            // Dropdown Profil
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