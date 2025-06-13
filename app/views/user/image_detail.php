<?php
session_start();
require_once '../../../config/inc_koneksi.php';
require_once '../../controller/detail_controller.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard_user.php");
    exit;
}

$imageId = (int)$_GET['id'];
$currentUserId = $_SESSION['id'] ?? null;

$image = getImageDetails($imageId, $currentUserId, $koneksi);
if (!$image) {
    die("Gambar tidak ditemukan.");
}

$comments = getComments($imageId, $koneksi);
$recommendedImages = getRecommendedImages($imageId, 12, $koneksi);

$myProfile = null;
if ($currentUserId) {
    $stmt_myprofile = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_myprofile, "i", $currentUserId);
    mysqli_stmt_execute($stmt_myprofile);
    $myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_myprofile));
    mysqli_stmt_close($stmt_myprofile);
}
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = ($myProfile && !empty($myProfile['foto'])) ? htmlspecialchars($myProfile['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($image['title']); ?> - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_detail_gambar.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <main class="main-container">
        <div class="detail-card">
            <div class="image-display-container">
                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
            </div>

            <div class="info-sidebar">
                <div class="action-header">
                    <div class="header-icons">
                        <button class="icon-button share-btn" title="Bagikan"><i class='bx bx-share'></i></button>
                        <a href="<?php echo htmlspecialchars($image['image_path']); ?>" download class="icon-button" title="Unduh"><i class='bx bxs-download'></i></a>
                    </div>
                    <button class="btn btn-primary save-btn <?php echo $image['is_bookmarked'] ? 'saved' : ''; ?>" data-image-id="<?php echo $image['id']; ?>">
                        <i class='bx <?php echo $image['is_bookmarked'] ? 'bxs-bookmark' : 'bx-bookmark'; ?>'></i>
                        <span><?php echo $image['is_bookmarked'] ? 'Tersimpan' : 'Simpan'; ?></span>
                    </button>
                </div>

                <div class="uploader-info">
                    <a href="public_profile.php?username=<?php echo htmlspecialchars($image['username']); ?>" class="author-link">
                        <img src="<?php echo htmlspecialchars($image['user_photo'] ?? $defaultPhotoPath); ?>" alt="Author Avatar" class="author-avatar">
                        <div class="author-text">
                            <span class="author-name"><?php echo htmlspecialchars($image['nama_lengkap']); ?></span>
                            <span class="author-username">@<?php echo htmlspecialchars($image['username']); ?></span>
                        </div>
                    </a>
                </div>

                <div class="scrollable-content">
                    <div class="image-info">
                        <h1 class="image-title"><?php echo htmlspecialchars($image['title']); ?></h1>
                        <p class="image-description"><?php echo nl2br(htmlspecialchars($image['caption'] ?? 'Tidak ada deskripsi.')); ?></p>
                    </div>

                    <div class="comment-section">
                        <h3 id="comment-count-display"><?php echo $image['comment_count']; ?> Komentar</h3>
                        <div class="comment-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <a href="public_profile.php?username=<?php echo htmlspecialchars($comment['username']); ?>">
                                        <img src="<?php echo htmlspecialchars($comment['user_photo'] ?? $defaultPhotoPath); ?>" class="commenter-avatar">
                                    </a>
                                    <div class="comment-content">
                                        <a href="public_profile.php?username=<?php echo htmlspecialchars($comment['username']); ?>" class="commenter-name">
                                            <?php echo htmlspecialchars($comment['username']); ?>
                                        </a>
                                        <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="action-footer">
                    <div class="like-info">
                        <strong><?php echo $image['like_count']; ?></strong> Suka
                    </div>
                    <form class="comment-form" data-image-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo $userPhoto; ?>" class="my-avatar">
                        <input type="text" name="comment_text" placeholder="Tambahkan komentar..." required autocomplete="off">
                        <button type="submit" class="send-btn" title="Kirim"><i class='bx bxs-send'></i></button>
                    </form>
                    <button class="like-btn <?php echo $image['has_liked'] ? 'liked' : ''; ?>" data-image-id="<?php echo $image['id']; ?>" title="Sukai">
                        <i class='bx <?php echo $image['has_liked'] ? 'bxs-heart' : 'bx-heart'; ?>'></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="recommendation-section">
            <h2>Karya lain yang mungkin Anda sukai</h2>
            <div class="image-gallery">
                <?php foreach ($recommendedImages as $rec_image): ?>
                    <div class="gallery-card">
                        <a href="image_detail.php?id=<?php echo $rec_image['id']; ?>" class="gallery-link">
                            <img src="<?php echo htmlspecialchars($rec_image['image_path']); ?>" alt="<?php echo htmlspecialchars($rec_image['title']); ?>" class="gallery-image" loading="lazy" />
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <div id="toast-notification" class="toast-notification"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Elemen DOM ---
            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profile-menu');
            const likeBtn = document.querySelector('.like-btn');
            const saveBtn = document.querySelector('.save-btn');
            const commentForm = document.querySelector('.comment-form');
            const shareBtn = document.querySelector('.share-btn');
            const searchInput = document.getElementById('searchInput');
            const suggestionsContainer = document.getElementById('searchSuggestions');
            const searchWrapper = document.querySelector('.search-wrapper');
            let searchTimeout = null;

            // --- Dropdown Profil ---
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
            }

            // --- Aksi Like ---
            if (likeBtn) {
                likeBtn.addEventListener('click', function() {
                    const imageId = this.dataset.imageId;
                    fetch('../../controller/like_controller.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                image_id: imageId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'liked') {
                                this.classList.add('liked');
                                this.querySelector('i').className = 'bx bxs-heart';
                            } else if (data.status === 'unliked') {
                                this.classList.remove('liked');
                                this.querySelector('i').className = 'bx bx-heart';
                            }
                            // Update jumlah like
                            const likeCountElement = document.querySelector('.like-info strong');
                            if (likeCountElement) {
                                likeCountElement.textContent = data.like_count;
                            }
                        }).catch(err => console.error("Like error:", err));
                });
            }

            // --- Aksi Simpan/Bookmark ---
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    const imageId = this.dataset.imageId;
                    const btnText = this.querySelector('span');
                    const btnIcon = this.querySelector('i');

                    fetch('../../controller/bookmark_handle_controller.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                image_id: imageId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'saved') {
                                this.classList.add('saved');
                                btnIcon.className = 'bx bxs-bookmark';
                                btnText.textContent = 'Tersimpan';
                            } else if (data.status === 'removed') {
                                this.classList.remove('saved');
                                btnIcon.className = 'bx bx-bookmark';
                                btnText.textContent = 'Simpan';
                            }
                        }).catch(err => console.error("Bookmark error:", err));
                });
            }

            // --- Aksi Komentar ---
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const imageId = this.dataset.imageId;
                    const commentInput = this.querySelector('input[name="comment_text"]');
                    const commentText = commentInput.value.trim();

                    if (commentText === "") return;

                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.disabled = true;

                    fetch('../../controller/comment_controller.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                image_id: imageId,
                                comment_text: commentText
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const commentList = document.querySelector('.comment-list');
                                // Tambahkan komentar baru ke daftar
                                commentList.insertAdjacentHTML('afterbegin', data.comment_html);

                                // Perbarui jumlah komentar
                                const commentCountElement = document.getElementById('comment-count-display');
                                if (commentCountElement) {
                                    commentCountElement.textContent = `${data.comment_count} Komentar`;
                                }

                                // Kosongkan input
                                commentInput.value = '';
                            } else {
                                alert(data.message || 'Gagal mengirim komentar.');
                            }
                        })
                        .catch(error => console.error('Comment Fetch Error:', error))
                        .finally(() => {
                            submitButton.disabled = false; // Aktifkan kembali tombol
                        });
                });
            }

            // --- [DIPERBAIKI] Logika Notifikasi Toast & Salin Link ---
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast-notification');
                if (toast) {
                    toast.textContent = message;
                    toast.className = 'toast-notification show';
                    if (type === 'error') {
                        toast.classList.add('error');
                    }
                    setTimeout(() => {
                        toast.className = 'toast-notification';
                    }, 3000);
                }
            }

            function fallbackCopyTextToClipboard(text) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.top = 0;
                textArea.style.left = 0;
                textArea.style.width = '2em';
                textArea.style.height = '2em';
                textArea.style.padding = 0;
                textArea.style.border = 'none';
                textArea.style.outline = 'none';
                textArea.style.boxShadow = 'none';
                textArea.style.background = 'transparent';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        showToast('Link disalin ke clipboard!');
                    } else {
                        showToast('Gagal menyalin link.', 'error');
                    }
                } catch (err) {
                    showToast('Gagal menyalin link.', 'error');
                }
                document.body.removeChild(textArea);
            }

            function copyLinkToClipboard() {
                const textToCopy = window.location.href;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        showToast('Link disalin ke clipboard!');
                    }, function(err) {
                        fallbackCopyTextToClipboard(textToCopy);
                    });
                } else {
                    fallbackCopyTextToClipboard(textToCopy);
                }
            }

            if (shareBtn) {
                shareBtn.addEventListener('click', copyLinkToClipboard);
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