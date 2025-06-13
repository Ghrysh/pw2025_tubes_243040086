<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit();
}

$id = (int)$_SESSION['id'];

function getProfilUser($id, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getCategories($koneksi)
{
    $query = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = mysqli_query($koneksi, $query);
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

$profil = getProfilUser($id, $koneksi);
$categories = getCategories($koneksi);
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Unggah Karya Baru - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_upload_image.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <main class="main-container">
        <div class="upload-card">
            <div class="card-header">
                <h1 class="card-title">Buat Postingan Baru</h1>
                <p class="card-subtitle">Bagikan karya terbaikmu kepada dunia</p>
            </div>

            <?php
            if (isset($_SESSION['uploadMsg']) && !empty($_SESSION['uploadMsg'])) {
                echo '<div class="status-message-wrapper">' . $_SESSION['uploadMsg'] . '</div>';
                unset($_SESSION['uploadMsg']);
            }
            ?>

            <form method="post" enctype="multipart/form-data" action="../../controller/uploadimage_controller.php" class="upload-form">
                <div class="form-left">
                    <label for="image" class="image-upload-label">
                        <div id="image-upload-frame" class="image-upload-frame">
                            <div id="upload-placeholder" class="upload-placeholder">
                                <i class='bx bx-cloud-upload upload-icon'></i>
                                <span class="placeholder-text-main">Klik untuk mengunggah</span>
                                <span class="placeholder-text-secondary">atau seret dan lepas gambar</span>
                                <span class="placeholder-text-format">PNG, JPG, atau GIF (Maks. 5MB)</span>
                            </div>
                            <img id="preview" class="img-preview" src="#" alt="Pratinjau Gambar" />
                        </div>
                    </label>
                    <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/gif" required />
                </div>

                <div class="form-right">
                    <div class="form-group">
                        <label for="title">Judul</label>
                        <input type="text" id="title" name="title" placeholder="Contoh: Matahari Terbenam di Pantai" required />
                    </div>
                    <div class="form-group">
                        <label for="caption">Keterangan</label>
                        <textarea id="caption" name="caption" placeholder="Deskripsikan karyamu di sini..." rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <div class="select-wrapper">
                            <select id="category" name="category" required>
                                <option value="" disabled selected>Pilih kategori...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Unggah Sekarang</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Elemen DOM ---
            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profile-menu');
            const imageInput = document.getElementById('image');
            const preview = document.getElementById('preview');
            const frame = document.getElementById('image-upload-frame');
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

            // --- Logika Unggah Gambar & Drag-Drop ---
            const handleFileSelect = (file) => {
                // [PENTING] Validasi file adalah gambar
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.setAttribute('src', e.target.result);
                        // Tambahkan kelas ini untuk memicu perubahan CSS
                        frame.classList.add('has-image');
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Opsional: berikan feedback jika file bukan gambar
                    alert('Harap pilih file gambar (PNG, JPG, atau GIF).');
                }
            };

            if (imageInput) {
                imageInput.addEventListener('change', () => handleFileSelect(imageInput.files[0]));

                frame.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    frame.classList.add('is-dragging');
                });
                frame.addEventListener('dragleave', () => frame.classList.remove('is-dragging'));
                frame.addEventListener('drop', (e) => {
                    e.preventDefault();
                    frame.classList.remove('is-dragging');
                    if (e.dataTransfer.files.length > 0) {
                        // Set file dari drop ke input file
                        imageInput.files = e.dataTransfer.files;
                        // Panggil handler utama
                        handleFileSelect(e.dataTransfer.files[0]);
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

            // Klik di luar untuk menutup semua
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