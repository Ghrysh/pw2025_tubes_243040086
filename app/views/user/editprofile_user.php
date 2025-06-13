<?php
session_start();
require_once '../../../config/inc_koneksi.php';
require_once '../../controller/editprofile_controller.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit();
}

$profil = getProfilUser($_SESSION['id'], $koneksi);
$user = getUser($_SESSION['id'], $koneksi);
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_editprofile.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php if (!$user): ?>
        <div class="error-page-container">
            <h2>Oops! Terjadi Kesalahan</h2>
            <p>Kami tidak dapat menemukan data profil Anda. Silakan coba login kembali.</p>
            <a href="../login/logout.php" class="btn btn-primary">Kembali ke Halaman Login</a>
        </div>
    <?php else: ?>

        <?php
        include('../navbar/navbar.php');
        ?>

        <main class="main-container">
            <form action="../../controller/editprofile_controller.php" method="post" enctype="multipart/form-data" class="edit-profile-card">
                <div class="card-header">
                    <h1 class="card-title">Edit Profil</h1>
                    <p class="card-subtitle">Perbarui informasi profil dan detail akun Anda.</p>
                </div>

                <div class="form-content">
                    <div class="form-section photo-section">
                        <h2 class="form-section-title">Foto Profil</h2>
                        <label for="foto" class="photo-upload-label">
                            <img src="<?php echo $userPhoto; ?>" alt="Foto Profil" class="photo-preview" id="photoPreview">
                            <div class="photo-overlay">
                                <i class='bx bx-camera'></i>
                                <span>Ganti Foto</span>
                            </div>
                        </label>
                        <input type="file" id="foto" name="foto" accept="image/*" class="photo-input">
                        <?php if (!empty($profil['foto'])): ?>
                            <button type="submit" name="hapus_foto" class="btn btn-danger-outline" formnovalidate>Hapus Foto</button>
                        <?php endif; ?>
                    </div>

                    <div class="form-section details-section">
                        <h2 class="form-section-title">Detail Publik</h2>
                        <div class="form-group"><label for="nama_lengkap">Nama Lengkap</label><input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($profil['nama_lengkap'] ?? ''); ?>" placeholder="Masukkan nama lengkap Anda"></div>
                        <div class="form-group"><label for="tentang">Tentang Anda (Bio)</label><textarea id="tentang" name="tentang" rows="4" placeholder="Tuliskan sesuatu tentang diri Anda..."><?php echo htmlspecialchars($profil['tentang'] ?? ''); ?></textarea></div>
                        <div class="form-group"><label for="situs_web">Situs Web</label><input type="url" id="situs_web" name="situs_web" value="<?php echo htmlspecialchars($profil['situs_web'] ?? ''); ?>" placeholder="https://website-anda.com"></div>
                        <hr class="form-divider">
                        <h2 class="form-section-title">Informasi Akun</h2>
                        <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="Masukkan username unik"></div>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="profile_user.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const profileBtn = document.querySelector('.profile-btn');
                const profileDropdown = document.getElementById('profile-menu');
                const photoInput = document.getElementById('foto');
                const photoPreview = document.getElementById('photoPreview');
                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                const searchWrapper = document.querySelector('.search-wrapper');
                let searchTimeout = null;

                // Dropdown profil
                if (profileBtn) {
                    profileBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        profileDropdown.classList.toggle('show');
                    });
                }

                // Pratinjau foto
                if (photoInput) {
                    photoInput.addEventListener('change', function() {
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                photoPreview.setAttribute('src', e.target.result);
                            }
                            reader.readAsDataURL(file);
                        }
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
    <?php endif; ?>
</body>

</html>