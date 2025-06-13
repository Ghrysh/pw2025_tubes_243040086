<?php
// [SESSION & KONEKSI] Mulai sesi dan koneksi ke database
session_start();
require_once '../../../config/inc_koneksi.php';

// [CEK LOGIN] Pastikan user sudah login
if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}
$currentUserId = (int)$_SESSION['id'];

// [CEK USERNAME] Pastikan ada parameter username
if (!isset($_GET['username'])) {
    header("Location: profile_user.php");
    exit;
}
$viewedUsername = $_GET['username'];

// [FUNGSI] Ambil detail profil publik user
function getPublicProfileDetails($username, $koneksi)
{
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT l.id, l.username, up.nama_lengkap, up.tentang, up.situs_web, up.foto 
         FROM login l
         LEFT JOIN user_profiles up ON l.id = up.user_id
         WHERE l.username = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// [FUNGSI] Ambil daftar karya user
function getUserPosts($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT id, image_path, title FROM images WHERE user_id = ? ORDER BY uploaded_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// [FUNGSI] Cek status follow
function checkFollowStatus($followerId, $followingId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $followerId, $followingId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $isFollowing = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $isFollowing;
}

// [FUNGSI] Hitung jumlah followers dan following
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

// [AMBIL DATA PROFIL YANG DILIHAT]
$viewedProfile = getPublicProfileDetails($viewedUsername, $koneksi);

if (!$viewedProfile) {
    die("Profil tidak ditemukan.");
}
$viewedUserId = (int)$viewedProfile['id'];

// [AMBIL KARYA, JUMLAH POST, FOLLOW COUNT]
$posts_result = getUserPosts($viewedUserId, $koneksi);
$post_count = mysqli_num_rows($posts_result);
$followCounts = getFollowCounts($viewedUserId, $koneksi);

// [CEK PROFIL SENDIRI & STATUS FOLLOW]
$isOwnProfile = ($currentUserId === $viewedUserId);
$isFollowing = false;
if (!$isOwnProfile) {
    $isFollowing = checkFollowStatus($currentUserId, $viewedUserId, $koneksi);
}

// [AMBIL FOTO PROFIL SENDIRI]
$stmt_myprofile = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_myprofile, "i", $currentUserId);
mysqli_stmt_execute($stmt_myprofile);
$myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_myprofile));
mysqli_stmt_close($stmt_myprofile);

// [ATUR PATH FOTO PROFIL DEFAULT]
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = ($myProfile && !empty($myProfile['foto'])) ? htmlspecialchars($myProfile['foto']) : $defaultPhotoPath;
$viewedUserPhoto = !empty($viewedProfile['foto']) ? htmlspecialchars($viewedProfile['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- [HEAD] Metadata dan CSS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($viewedProfile['nama_lengkap'] ?? $viewedProfile['username']); ?> - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_public_profile.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    // [NAVBAR]
    include('../navbar/navbar.php');
    ?>

    <main class="main-container-profile">
        <!-- [SEKSI PROFIL UTAMA] -->
        <section class="profile-hero">
            <div class="profile-main-info">
                <div class="profile-avatar-wrapper"><img src="<?php echo $viewedUserPhoto; ?>" alt="Foto Profil" class="profile-avatar" /></div>
                <div class="profile-text-content">
                    <div class="profile-name-actions">
                        <h1 class="profile-name"><?php echo htmlspecialchars($viewedProfile['nama_lengkap'] ?? $viewedProfile['username']); ?></h1>
                        <div class="profile-actions-desktop">
                            <?php if ($isOwnProfile): ?><a href="editprofile_user.php" class="btn btn-secondary">Edit Profil</a>
                            <?php else: ?><button class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?> follow-btn" data-user-id="<?php echo $viewedUserId; ?>"><?php echo $isFollowing ? 'Mengikuti' : 'Ikuti'; ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h2 class="profile-username">@<?php echo htmlspecialchars($viewedProfile['username']); ?></h2>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <strong><?php echo $post_count; ?></strong>
                            <span>Karya</span>
                        </div>
                        <a href="followers.php?username=<?php echo htmlspecialchars($viewedUsername); ?>&type=followers" class="stat-item">
                            <strong id="follower-count"><?php echo $followCounts['followers']; ?></strong>
                            <span>Pengikut</span>
                        </a>
                        <a href="followers.php?username=<?php echo htmlspecialchars($viewedUsername); ?>&type=following" class="stat-item">
                            <strong><?php echo $followCounts['following']; ?></strong>
                            <span>Mengikuti</span>
                        </a>
                    </div>
                    <p class="profile-bio"><?php echo !empty($viewedProfile['tentang']) ? nl2br(htmlspecialchars($viewedProfile['tentang'])) : 'Bio belum diatur.'; ?></p>
                    <?php if (!empty($viewedProfile['situs_web'])): ?><a href="<?php echo htmlspecialchars($viewedProfile['situs_web']); ?>" class="profile-website" target="_blank" rel="noopener noreferrer"><i class='bx bx-link-alt'></i> <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $viewedProfile['situs_web'])); ?></a><?php endif; ?>
                </div>
            </div>
            <div class="profile-actions-mobile">
                <?php if ($isOwnProfile): ?><a href="editprofile_user.php" class="btn btn-secondary">Edit Profil</a>
                <?php else: ?><button class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?> follow-btn" data-user-id="<?php echo $viewedUserId; ?>"><?php echo $isFollowing ? 'Mengikuti' : 'Ikuti'; ?></button>
                <?php endif; ?>
            </div>
        </section>

        <!-- [SEKSI GALERI KARYA] -->
        <section class="profile-gallery-section">
            <div class="profile-tabs"><button class="tab-btn active">Karya (<?php echo $post_count; ?>)</button></div>
            <div class="image-gallery">
                <?php if ($post_count > 0): ?>
                    <?php mysqli_data_seek($posts_result, 0); ?>
                    <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                        <div class="gallery-card">
                            <a href="image_detail.php?id=<?php echo $post['id']; ?>" class="gallery-link">
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="gallery-image" loading="lazy" />
                                <div class="gallery-overlay">
                                    <h4 class="overlay-title"><?php echo htmlspecialchars($post['title']); ?></h4>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="gallery-message full-width">Pengguna ini belum mengunggah karya apa pun.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        // [JAVASCRIPT UTAMA: Dropdown, Follow, Search]
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profile-menu');
            const searchInput = document.getElementById('searchInput');
            const suggestionsContainer = document.getElementById('searchSuggestions');
            const searchWrapper = document.querySelector('.search-wrapper');
            const followButtons = document.querySelectorAll('.follow-btn');
            let searchTimeout = null;

            // [DROPDOWN PROFIL]
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
            }

            // [AKSI FOLLOW/UNFOLLOW]
            followButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userIdToFollow = this.dataset.userId;
                    const followerCountElement = document.getElementById('follower-count');
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
                                alert(data.message || data.debug?.error_message || 'Terjadi kesalahan.');
                            }
                            if (followerCountElement && data.new_follower_count !== undefined) {
                                followerCountElement.textContent = data.new_follower_count;
                            }
                        })
                        .catch(error => {
                            console.error('Follow Error:', error);
                            alert('Tidak dapat terhubung ke server.');
                        })
                        .finally(() => {
                            document.querySelectorAll(`.follow-btn[data-user-id="${userIdToFollow}"]`).forEach(btn => {
                                btn.disabled = false;
                            });
                        });
                });
            });

            // [LOGIKA SEARCH & SUGGESTION]
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
                container.innerHTML = html;
                container.style.display = html ? 'block' : 'none';
            };

            const handleSearchInput = (term, container) => {
                if (!term) {
                    container.style.display = 'none';
                    return;
                }
                fetch(`/Gallery_Seni_Online/app/views/user/get_search_suggestions.php?term=${encodeURIComponent(term)}`)
                    .then(res => res.json())
                    .then(data => renderSuggestions(data, container))
                    .catch(err => console.error("Fetch suggestions error:", err));
            };

            // [FUNGSI PENCARIAN]
            const performSearch = (term) => {
                const searchTerm = term.trim();
                if (searchTerm !== '') {
                    window.location.href = `dashboard_user.php?search=${encodeURIComponent(searchTerm)}`;
                }
            };

            if (searchInput) {
                // [SUGGESTION SAAT KETIK]
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => handleSearchInput(searchInput.value.trim(), suggestionsContainer), 300);
                });

                // [PENCARIAN SAAT ENTER]
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performSearch(searchInput.value);
                    }
                });
            }

            if (suggestionsContainer) {
                // [PENCARIAN DARI SUGGESTION]
                suggestionsContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('suggestion-item')) {
                        const value = e.target.textContent.replace('@', '');
                        performSearch(value);
                    }
                });
            }

            // [TUTUP DROPDOWN & SUGGESTION KETIKA KLIK DI LUAR]
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