<?php
// ====== Mulai Session & Koneksi Database ======
session_start();
require_once '../../../config/inc_koneksi.php';

// ====== Ambil ID User dari Session ======
$id = (int) ($_SESSION['id'] ?? 0);

// ====== Fungsi: Ambil Profil User ======
function getProfilUser($id)
{
    global $koneksi;
    $id = (int)$id;
    $query = "SELECT * FROM user_profiles WHERE user_id = $id";
    $result = mysqli_query($koneksi, $query);
    if (!$result) {
        error_log('Query getProfilUser gagal: ' . mysqli_error($koneksi));
        die('Terjadi kesalahan pada sistem.');
    }
    return mysqli_fetch_assoc($result);
}

// ====== Ambil Data Profil & Username ======
$profil = getProfilUser($id);
$username = $_SESSION['username'] ?? 'Admin';

// ====== Hitung Total Gambar ======
$result_images = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM images");
$total_images = mysqli_fetch_assoc($result_images)['total'];

// ====== Hitung Total Pengguna ======
$result_users = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM login");
$total_users = mysqli_fetch_assoc($result_users)['total'];

// ====== Hitung Jumlah Gambar per Kategori ======
$query_kategori = "
    SELECT 
        c.name AS nama_kategori, 
        COUNT(i.id) AS jumlah_gambar
    FROM categories c
    LEFT JOIN images i ON c.id = i.category_id
    GROUP BY c.id, c.name
    ORDER BY jumlah_gambar DESC
";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$kategori_stats = [];
if ($result_kategori) {
    while ($row = mysqli_fetch_assoc($result_kategori)) {
        $kategori_stats[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- ====== Meta & Link CSS/Font ====== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_dashboard_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Dashboard - MizuPix Admin</title>
</head>

<body>
    <!-- ====== Sidebar Navigasi ====== -->
    <aside class="sidebar">
        <a href="dashboard_admin.php" class="sidebar__logo">
            <img src="../../../public/assets/img/loading_logo.png" alt="MizuPix Logo">
            <span>MizuPix</span>
        </a>

        <nav class="sidebar__nav">
            <ul>
                <li><a href="dashboard_admin.php" class="active"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
                <li><a href="manage_images.php"><i class='bx bxs-image-alt'></i><span>Gambar</span></a></li>
                <li><a href="manage_users.php"><i class='bx bxs-user-account'></i><span>Pengguna</span></a></li>
                <li><a href="manage_categories.php"><i class='bx bxs-category-alt'></i><span>Kategori</span></a></li>
                <li><a href="manage_messages.php"><i class='bx bxs-chat'></i><span>Pesan</span></a></li>
                <li><a href="manage_account.php"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <!-- ====== Header Dashboard ====== -->
        <header class="header">
            <div class="header__title">
                <h1>Dashboard</h1>
                <p>Selamat datang kembali, <?php echo htmlspecialchars($username); ?>!</p>
            </div>
            <div class="header__actions">
                <a href="pdf_report.php" class="btn btn-secondary" target="_blank">
                    <i class='bx bxs-file-pdf'></i> Download Laporan
                </a>
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
            </div>
        </header>

        <!-- ====== Statistik Utama ====== -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-card__icon" style="background-color: #E6F3FF;">
                    <i class='bx bxs-image' style="color: #007BFF;"></i>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value"><?php echo $total_images; ?></span>
                    <p class="stat-card__label">Total Gambar</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card__icon" style="background-color: #E5F9F0;">
                    <i class='bx bxs-user-account' style="color: #28A745;"></i>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value"><?php echo $total_users; ?></span>
                    <p class="stat-card__label">Total Pengguna</p>
                </div>
            </div>
        </section>

        <!-- ====== Laporan Gambar per Kategori ====== -->
        <section class="widget-container">
            <div class="card">
                <div class="card-header">
                    <h3>Laporan Gambar per Kategori</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($kategori_stats)): ?>
                        <div class="category-report-grid">
                            <?php foreach ($kategori_stats as $stat): ?>
                                <div class="category-card">
                                    <span class="category-card__name"><?php echo htmlspecialchars($stat['nama_kategori']); ?></span>
                                    <span class="category-card__count"><?php echo htmlspecialchars($stat['jumlah_gambar']); ?> Gambar</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Belum ada data kategori untuk ditampilkan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- ====== Script Dropdown Profil ====== -->
    <script>
        const profileBtn = document.querySelector('.profile-btn');
        const profileDropdown = document.querySelector('.profile-dropdown');

        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                profileDropdown.classList.toggle('show');
            });
        }

        window.addEventListener('click', function(e) {
            const profileContainer = document.querySelector('.header__profile');
            if (profileDropdown && !profileContainer.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
    </script>
</body>

</html>