<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}
$currentUserId = (int)$_SESSION['id'];

if (!isset($_GET['username'])) {
    header("Location: dashboard_user.php");
    exit;
}
$viewedUsername = $_GET['username'];
$type = $_GET['type'] ?? 'followers';

// 1. Dapatkan ID dari username yang profilnya dilihat
// [DIPERBAIKI] Menambahkan alias 'l.id' dan 'up.nama_lengkap' untuk mengatasi ambiguitas
$stmt_user = mysqli_prepare(
    $koneksi,
    "SELECT l.id, up.nama_lengkap 
     FROM login l
     LEFT JOIN user_profiles up ON l.id = up.user_id 
     WHERE l.username = ?"
);
mysqli_stmt_bind_param($stmt_user, "s", $viewedUsername);
mysqli_stmt_execute($stmt_user);
$viewedProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

if (!$viewedProfile) {
    die("Profil tidak ditemukan.");
}
$viewedUserId = (int)$viewedProfile['id'];

// 2. Siapkan query berdasarkan tipe (followers atau following)
$userList = [];
$query = "";
$pageTitle = "";

if ($type === 'followers') {
    $pageTitle = "Pengikut dari @" . htmlspecialchars($viewedUsername);
    $query = "SELECT l.id, l.username, up.nama_lengkap, up.foto
              FROM followers f
              JOIN login l ON f.follower_id = l.id
              LEFT JOIN user_profiles up ON l.id = up.user_id
              WHERE f.following_id = ?";
} else {
    $pageTitle = "Mengikuti @" . htmlspecialchars($viewedUsername);
    $query = "SELECT l.id, l.username, up.nama_lengkap, up.foto
              FROM followers f
              JOIN login l ON f.following_id = l.id
              LEFT JOIN user_profiles up ON l.id = up.user_id
              WHERE f.follower_id = ?";
    $type = 'following';
}

$stmt_list = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt_list, "i", $viewedUserId);
mysqli_stmt_execute($stmt_list);
$result = mysqli_stmt_get_result($stmt_list);
while ($row = mysqli_fetch_assoc($result)) {
    $userList[] = $row;
}
mysqli_stmt_close($stmt_list);

// 3. Dapatkan daftar siapa saja yang di-follow oleh PENGGUNA SAAT INI
$myFollowings = [];
$stmt_my_follows = mysqli_prepare($koneksi, "SELECT following_id FROM followers WHERE follower_id = ?");
mysqli_stmt_bind_param($stmt_my_follows, "i", $currentUserId);
mysqli_stmt_execute($stmt_my_follows);
$my_follows_result = mysqli_stmt_get_result($stmt_my_follows);
while ($row = mysqli_fetch_assoc($my_follows_result)) {
    $myFollowings[$row['following_id']] = true;
}
mysqli_stmt_close($stmt_my_follows);

// Data untuk Navbar
$stmt_myprofile = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_myprofile, "i", $currentUserId);
mysqli_stmt_execute($stmt_myprofile);
$myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_myprofile));
mysqli_stmt_close($stmt_myprofile);

$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = ($myProfile && !empty($myProfile['foto'])) ? htmlspecialchars($myProfile['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?> - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_followers.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <main class="main-container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($viewedProfile['nama_lengkap'] ?? $viewedUsername); ?></h1>
            <div class="profile-tabs">
                <a href="?username=<?php echo htmlspecialchars($viewedUsername); ?>&type=followers" class="tab-btn <?php echo $type === 'followers' ? 'active' : ''; ?>">Pengikut</a>
                <a href="?username=<?php echo htmlspecialchars($viewedUsername); ?>&type=following" class="tab-btn <?php echo $type === 'following' ? 'active' : ''; ?>">Mengikuti</a>
            </div>
        </div>

        <div class="user-list">
            <?php if (empty($userList)): ?>
                <p class="list-empty-message">
                    <?php echo $type === 'followers' ? 'Belum ada pengikut.' : 'Tidak mengikuti siapa pun.'; ?>
                </p>
            <?php else: ?>
                <?php foreach ($userList as $listUser): ?>
                    <div class="user-list-item">
                        <a href="public_profile.php?username=<?php echo htmlspecialchars($listUser['username']); ?>" class="user-info">
                            <img src="<?php echo !empty($listUser['foto']) ? htmlspecialchars($listUser['foto']) : $defaultPhotoPath; ?>" alt="Foto profil <?php echo htmlspecialchars($listUser['username']); ?>" class="user-avatar">
                            <div class="user-text">
                                <span class="user-name"><?php echo htmlspecialchars($listUser['nama_lengkap'] ?? $listUser['username']); ?></span>
                                <span class="user-username">@<?php echo htmlspecialchars($listUser['username']); ?></span>
                            </div>
                        </a>
                        <div class="user-action">
                            <?php
                            if ($currentUserId !== (int)$listUser['id']):
                                $isFollowing = isset($myFollowings[$listUser['id']]);
                            ?>
                                <button class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?> follow-btn" data-user-id="<?php echo $listUser['id']; ?>">
                                    <?php echo $isFollowing ? 'Mengikuti' : 'Ikuti'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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
            let searchTimeout = null;

            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
            }

            document.querySelectorAll('.follow-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userIdToFollow = this.dataset.userId;
                    this.disabled = true;
                    fetch('../../controller/follow_controller.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                following_id: userIdToFollow
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'followed') {
                                document.querySelectorAll(`.follow-btn[data-user-id="${userIdToFollow}"]`).forEach(btn => {
                                    btn.textContent = 'Mengikuti';
                                    btn.classList.remove('btn-primary');
                                    btn.classList.add('btn-secondary');
                                });
                            } else if (data.status === 'unfollowed') {
                                document.querySelectorAll(`.follow-btn[data-user-id="${userIdToFollow}"]`).forEach(btn => {
                                    btn.textContent = 'Ikuti';
                                    btn.classList.remove('btn-secondary');
                                    btn.classList.add('btn-primary');
                                });
                            } else {
                                alert(data.message || 'Terjadi kesalahan.');
                            }
                        })
                        .catch(error => console.error('Follow Error:', error))
                        .finally(() => {
                            this.disabled = false;
                        });
                });
            });

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

            // --- Klik di luar untuk menutup semua dropdown ---
            document.addEventListener('click', (event) => {
                if (profileBtn && !profileBtn.parentElement.contains(event.target)) {
                    profileDropdown.classList.remove('show');
                }
                if (searchWrapper && !searchWrapper.contains(event.target)) {
                    if (suggestionsContainer) suggestionsContainer.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>