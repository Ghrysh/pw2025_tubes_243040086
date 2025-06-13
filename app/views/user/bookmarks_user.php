<?php
session_start();
require_once '../../../config/inc_koneksi.php';
require_once '../../controller/bookmark_controller.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}

$id = (int)$_SESSION['id'];
$searchQuery = $_GET['search'] ?? '';

function getMyProfile($id, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

$profil = getMyProfile($id, $koneksi);
$bookmarkedImages = getBookmarkedImages($id, $koneksi, $searchQuery);
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gambar Tersimpan - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_bookmark_user.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <main class="main-container">
        <div class="page-header">
            <h1>Gambar Tersimpan</h1>
            <p>Semua inspirasi yang telah Anda kumpulkan di satu tempat.</p>
        </div>

        <div class="image-gallery">
            <?php if (!empty($bookmarkedImages)): ?>
                <?php foreach ($bookmarkedImages as $image): ?>
                    <div class="gallery-card" data-card-id="<?php echo $image['id']; ?>">
                        <a href="image_detail.php?id=<?php echo $image['id']; ?>" class="gallery-image-link">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" class="gallery-image" loading="lazy" />
                        </a>
                        <div class="gallery-overlay">
                            <button class="overlay-button unsave-btn" data-image-id="<?php echo $image['id']; ?>" title="Hapus dari Bookmark">
                                <i class='bx bxs-bookmark-minus'></i>
                            </button>
                        </div>
                        <div class="card-footer">
                            <a href="public_profile.php?username=<?php echo htmlspecialchars($image['username']); ?>" class="author-link">
                                <img src="<?php echo htmlspecialchars($image['user_photo'] ?? $defaultPhotoPath); ?>" alt="User Photo" class="author-avatar">
                                <span class="author-name"><?php echo htmlspecialchars($image['nama_lengkap']); ?></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class='bx bx-bookmark-alt-minus'></i>
                    <h2>Belum Ada yang Disimpan</h2>
                    <?php if (!empty($searchQuery)): ?>
                        <p>Tidak ada gambar tersimpan yang cocok dengan kata kunci "<?php echo htmlspecialchars($searchQuery); ?>".</p>
                        <a href="bookmarks_user.php" class="btn btn-secondary">Tampilkan Semua</a>
                    <?php else: ?>
                        <p>Klik ikon bookmark pada gambar untuk menyimpannya di sini.</p>
                        <a href="dashboard_user.php" class="btn btn-primary">Mulai Menjelajah</a>
                    <?php endif; ?>
                </div>
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

            document.addEventListener('click', (event) => {
                if (profileBtn && !profileBtn.parentElement.contains(event.target)) {
                    profileDropdown.classList.remove('show');
                }
            });

            // Script untuk menangani penghapusan bookmark
            document.querySelectorAll('.unsave-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    // e.preventDefault(); // Tidak lagi wajib, tapi bagus untuk ada
                    const card = this.closest('.gallery-card');
                    const imageId = this.dataset.imageId;

                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';

                    fetch('../../controller/bookmark_handle_controller.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                image_id: imageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'removed') {
                                setTimeout(() => {
                                    card.remove();
                                    // ... Logika cek galeri kosong tidak berubah ...
                                }, 400);
                            } else {
                                card.style.opacity = '1';
                                card.style.transform = 'scale(1)';
                            }
                        })
                        .catch(error => {
                            card.style.opacity = '1';
                            card.style.transform = 'scale(1)';
                            alert('Terjadi kesalahan. Gagal menghapus bookmark.');
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