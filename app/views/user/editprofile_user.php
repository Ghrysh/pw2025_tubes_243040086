<?php
// [SESSION & KONEKSI] Inisialisasi sesi dan koneksi database
session_start();
require_once '../../../config/inc_koneksi.php';
require_once '../../controller/editprofile_controller.php';

// [CEK LOGIN] Redirect jika user belum login
if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit();
}

// [AMBIL DATA USER] Mengambil data profil dan user dari database
$profil = getProfilUser($_SESSION['id'], $koneksi);
$user = getUser($_SESSION['id'], $koneksi);
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- [HEAD] Metadata, font, icon, dan stylesheet -->
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
        <!-- [ERROR PAGE] Jika data user tidak ditemukan -->
        <div class="error-page-container">
            <h2>Oops! Terjadi Kesalahan</h2>
            <p>Kami tidak dapat menemukan data profil Anda. Silakan coba login kembali.</p>
            <a href="../login/logout.php" class="btn btn-primary">Kembali ke Halaman Login</a>
        </div>
    <?php else: ?>

        <?php
        // [NAVBAR] Menyisipkan navbar
        include('../navbar/navbar.php');
        ?>

        <main class="main-container">
            <!-- [FORM EDIT PROFIL] Formulir untuk mengedit profil user -->
            <form action="../../controller/editprofile_controller.php" method="post" enctype="multipart/form-data" class="edit-profile-card">
                <div class="card-header">
                    <h1 class="card-title">Edit Profil</h1>
                    <p class="card-subtitle">Perbarui informasi profil dan detail akun Anda.</p>
                </div>

                <div class="form-content">
                    <div class="form-section photo-section">
                        <!-- [FOTO PROFIL] Upload dan preview foto profil -->
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
                        <!-- [DETAIL PUBLIK] Input nama, bio, dan website -->
                        <h2 class="form-section-title">Detail Publik</h2>
                        <div class="form-group"><label for="nama_lengkap">Nama Lengkap</label><input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($profil['nama_lengkap'] ?? ''); ?>" placeholder="Masukkan nama lengkap Anda"></div>
                        <div class="form-group"><label for="tentang">Tentang Anda (Bio)</label><textarea id="tentang" name="tentang" rows="4" placeholder="Tuliskan sesuatu tentang diri Anda..."><?php echo htmlspecialchars($profil['tentang'] ?? ''); ?></textarea></div>
                        <div class="form-group"><label for="situs_web">Situs Web</label><input type="url" id="situs_web" name="situs_web" value="<?php echo htmlspecialchars($profil['situs_web'] ?? ''); ?>" placeholder="https://website-anda.com"></div>
                        <hr class="form-divider">
                        <!-- [INFORMASI AKUN] Input username -->
                        <h2 class="form-section-title">Informasi Akun</h2>
                        <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="Masukkan username unik"></div>
                    </div>
                </div>

                <div class="card-footer">
                    <!-- [BUTTON AKSI] Tombol batal dan simpan -->
                    <a href="profile_user.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </main>

        <script>
            // [JAVASCRIPT UTAMA] Interaksi UI: dropdown profil, preview foto, dan pencarian
            document.addEventListener('DOMContentLoaded', function() {
                const profileBtn = document.querySelector('.profile-btn');
                const profileDropdown = document.getElementById('profile-menu');
                const photoInput = document.getElementById('foto');
                const photoPreview = document.getElementById('photoPreview');
                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                const searchWrapper = document.querySelector('.search-wrapper');
                let searchTimeout = null;

                // [DROPDOWN PROFIL] Tampilkan/sembunyikan menu profil
                if (profileBtn) {
                    profileBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        profileDropdown.classList.toggle('show');
                    });
                }

                // [PRATINJAU FOTO] Preview foto sebelum upload
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

                // [SUGGESTION SEARCH] Render dan fetch suggestion pencarian
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

                // [PERFORM SEARCH] Jalankan pencarian ke dashboard
                const performSearch = (term) => {
                    const searchTerm = term.trim();
                    if (searchTerm !== '') {
                        window.location.href = `dashboard_user.php?search=${encodeURIComponent(searchTerm)}`;
                    }
                };

                if (searchInput) {
                    // [INPUT SEARCH] Tampilkan suggestion saat mengetik
                    searchInput.addEventListener('input', () => {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => handleSearchInput(searchInput.value.trim(), suggestionsContainer), 300);
                    });

                    // [ENTER SEARCH] Jalankan pencarian saat enter
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            performSearch(searchInput.value);
                        }
                    });
                }

                if (suggestionsContainer) {
                    // [CLICK SUGGESTION] Jalankan pencarian saat suggestion diklik
                    suggestionsContainer.addEventListener('click', (e) => {
                        if (e.target.classList.contains('suggestion-item')) {
                            const value = e.target.textContent.replace('@', '');
                            performSearch(value);
                        }
                    });
                }

                // [TUTUP DROPDOWN] Klik di luar untuk menutup dropdown dan suggestion
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