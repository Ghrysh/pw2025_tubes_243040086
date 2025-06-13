<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}
$currentUserId = (int)$_SESSION['id'];

function getUser($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT username FROM login WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getProfilUser($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT nama_lengkap, tentang, situs_web, foto FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getUserPosts($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT id, image_path, title FROM images WHERE user_id = ? ORDER BY uploaded_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function getFollowCounts($userId, $koneksi)
{
    $counts = ['followers' => 0, 'following' => 0];
    $stmt_followers = mysqli_prepare($koneksi, "SELECT COUNT(id) as count FROM followers WHERE following_id = ?");
    mysqli_stmt_bind_param($stmt_followers, "i", $userId);
    mysqli_stmt_execute($stmt_followers);
    $counts['followers'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_followers))['count'];
    mysqli_stmt_close($stmt_followers);

    $stmt_following = mysqli_prepare($koneksi, "SELECT COUNT(id) as count FROM followers WHERE follower_id = ?");
    mysqli_stmt_bind_param($stmt_following, "i", $userId);
    mysqli_stmt_execute($stmt_following);
    $counts['following'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_following))['count'];
    mysqli_stmt_close($stmt_following);
    return $counts;
}

$user = getUser($currentUserId, $koneksi);
$profil = getProfilUser($currentUserId, $koneksi);
$posts_result = getUserPosts($currentUserId, $koneksi);
$post_count = mysqli_num_rows($posts_result);
$followCounts = getFollowCounts($currentUserId, $koneksi);

$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Saya - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_profile.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <div class="fab-container">
        <div class="fab-actions">
            <button class="fab-action-btn" id="serviceCenterBtn" title="Pusat Layanan"><i class='bx bx-message-rounded-dots'></i></button>
            <a href="uploadimage_user.php" class="fab-action-btn" title="Unggah Gambar"><i class='bx bx-upload'></i></a>
        </div>
        <button class="fab-main" id="fabMainBtn" title="Menu Aksi"><i class='bx bx-plus'></i></button>
    </div>

    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" id="closeServiceModal">&times;</button>
            <h2>Pusat Layanan MizuPix</h2>
            <p>Punya pertanyaan atau masukan? Kirimkan kepada kami.</p>
            <form id="messageForm" method="POST" action="../../controller/message_controller.php">
                <div class="form-group"><label for="subject">Subjek</label><input type="text" id="subject" name="subject" required /></div>
                <div class="form-group"><label for="message">Pesan Anda</label><textarea id="message" name="message" rows="5" required></textarea></div>
                <button type="submit" class="btn btn-primary btn-full">Kirim Pesan</button>
            </form>
        </div>
    </div>

    <main class="main-container">
        <div class="profile-card">
            <div class="profile-header">
                <img src="<?php echo $userPhoto; ?>" alt="Foto Profil" class="profile-avatar" />
                <div class="profile-header-info">
                    <div class="profile-name-wrapper">
                        <h1 class="profile-name"><?php echo htmlspecialchars($profil['nama_lengkap'] ?? 'Nama Pengguna'); ?></h1>
                        <a href="editprofile_user.php" class="btn btn-secondary">Edit Profil</a>
                    </div>
                    <h2 class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></h2>
                    <div class="profile-stats">
                        <div class="stat-item"><strong><?php echo $post_count; ?></strong><span>Karya</span></div>
                        <a href="followers.php?username=<?php echo htmlspecialchars($user['username']); ?>&type=followers" class="stat-item">
                            <strong><?php echo $followCounts['followers']; ?></strong><span>Pengikut</span>
                        </a>
                        <a href="followers.php?username=<?php echo htmlspecialchars($user['username']); ?>&type=following" class="stat-item">
                            <strong><?php echo $followCounts['following']; ?></strong><span>Mengikuti</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="profile-body">
                <p class="profile-bio"><?php echo !empty($profil['tentang']) ? nl2br(htmlspecialchars($profil['tentang'])) : 'Bio belum diatur.'; ?></p>
                <?php if (!empty($profil['situs_web'])): ?>
                    <a href="<?php echo htmlspecialchars($profil['situs_web']); ?>" class="profile-website" target="_blank" rel="noopener noreferrer">
                        <i class='bx bx-link-alt'></i> <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $profil['situs_web'])); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-tabs">
            <button class="tab-btn active">Karya Saya (<?php echo $post_count; ?>)</button>
        </div>

        <div class="image-gallery">
            <?php if ($post_count > 0): ?>
                <?php mysqli_data_seek($posts_result, 0); ?>
                <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                    <div class="gallery-card">
                        <a href="image_detail.php?id=<?php echo $post['id']; ?>" class="gallery-link">
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="gallery-image" loading="lazy" />
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="gallery-message">Anda belum mengunggah karya apa pun.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profile-menu');
            const searchInput = document.getElementById('searchInput');
            const suggestionsContainer = document.getElementById('searchSuggestions');
            const searchWrapper = document.querySelector('.search-wrapper');
            const fabMainBtn = document.getElementById('fabMainBtn');
            const fabActions = document.querySelector('.fab-actions');
            const serviceModal = document.getElementById('serviceModal');
            const serviceCenterBtn = document.getElementById('serviceCenterBtn');
            const closeServiceModal = document.getElementById('closeServiceModal');
            let searchTimeout = null;

            // Dropdown profil
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
            }

            // FAB Speed Dial
            if (fabMainBtn) {
                fabMainBtn.addEventListener('click', () => {
                    fabActions.classList.toggle('open');
                    fabMainBtn.classList.toggle('open');
                });
            }

            // Modal Pusat Layanan
            if (serviceCenterBtn) {
                serviceCenterBtn.addEventListener('click', () => {
                    serviceModal.classList.add('show');
                });
            }
            if (closeServiceModal) {
                closeServiceModal.addEventListener('click', () => {
                    serviceModal.classList.remove('show');
                });
            }


            // --- [DIPERBAIKI] Search Logic ---
            const renderSuggestions = (data, container) => {
                if (!container) return;
                let html = '';
                const createSuggestionItem = (text, type = 'search') => `<div class="suggestion-item" data-type="${type}">${text}</div>`;

                if (data.titles && data.titles.length > 0) {
                    html += '<div class="suggestion-group"><h5>Judul</h5>';
                    data.titles.forEach(s => html += createSuggestionItem(s));
                    html += '</div>';
                }
                if (data.users && data.users.length > 0) {
                    html += '<div class="suggestion-group"><h5>Seniman</h5>';
                    data.users.forEach(s => html += createSuggestionItem(`@${s}`));
                    html += '</div>';
                }
                if (data.categories && data.categories.length > 0) {
                    html += '<div class="suggestion-group"><h5>Kategori</h5>';
                    data.categories.forEach(s => html += createSuggestionItem(s, 'category'));
                    html += '</div>';
                }
                // ... (bisa ditambahkan grup lain seperti user atau kategori jika perlu)
                container.innerHTML = html;
                container.style.display = html ? 'block' : 'none';
            };

            const handleSearchInput = (term, container) => {
                if (!term) {
                    container.style.display = 'none';
                    return;
                }
                // Pastikan Anda membuat file get_search_suggestions.php di path yang benar
                fetch(`/Gallery_Seni_Online/app/views/user/get_search_suggestions.php?term=${encodeURIComponent(term)}`)
                    .then(res => res.json())
                    .then(data => renderSuggestions(data, container))
                    .catch(err => console.error("Fetch suggestions error:", err));
            };

            // [FUNGSI UTAMA] Fungsi ini sekarang hanya mengarahkan ke dasbor
            const performSearch = (term) => {
                const searchTerm = term.trim();
                if (searchTerm !== '') {
                    // Membuat URL tujuan dan pindah halaman
                    window.location.href = `dashboard_user.php?search=${encodeURIComponent(searchTerm)}`;
                }
            };

            if (searchInput) {
                // Menampilkan saran saat mengetik
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => handleSearchInput(searchInput.value.trim(), suggestionsContainer), 300);
                });

                // Menjalankan pencarian saat menekan Enter
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performSearch(searchInput.value);
                    }
                });
            }

            if (suggestionsContainer) {
                // Menjalankan pencarian saat salah satu saran diklik
                suggestionsContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('suggestion-item')) {
                        const value = e.target.textContent.replace('@', '');
                        performSearch(value);
                    }
                });
            }

            // Klik di luar untuk menutup semua
            document.addEventListener('click', (event) => {
                if (profileBtn && !profileBtn.parentElement.contains(event.target)) {
                    profileDropdown.classList.remove('show');
                }
                if (searchWrapper && !searchWrapper.contains(event.target)) {
                    if (suggestionsContainer) suggestionsContainer.style.display = 'none';
                }
                if (fabMainBtn && !fabMainBtn.parentElement.contains(event.target)) {
                    fabActions.classList.remove('open');
                    fabMainBtn.classList.remove('open');
                }
                if (event.target === serviceModal) {
                    serviceModal.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>