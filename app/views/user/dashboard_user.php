<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}

$id = (int)$_SESSION['id'];

// --- Functions ---
function getMyProfile($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "SELECT up.nama_lengkap, up.foto FROM user_profiles up WHERE up.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getCategories($koneksi)
{
    $categories = [];
    $result = mysqli_query($koneksi, "SELECT name FROM categories ORDER BY name ASC");
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

function getAllImagesWithBookmarkStatus($userId, $koneksi, $category = 'all', $search = '', $limit = 10, $page = 1, $sort = 'latest')
{
    $images = [];
    // Query dasar dengan penghitungan jumlah suka
    $base_query = "
        SELECT 
            i.id, i.image_path, i.title, i.caption,
            up.nama_lengkap, up.foto as user_photo,
            l.username,
            (SELECT COUNT(*) FROM bookmarks b WHERE b.image_id = i.id AND b.user_id = ?) as is_bookmarked,
            COUNT(lk.id) as like_count
        FROM images i
        JOIN user_profiles up ON i.user_id = up.user_id
        JOIN login l ON i.user_id = l.id
        JOIN categories c ON i.category_id = c.id
        LEFT JOIN likes lk ON i.id = lk.image_id
    ";

    $params = [$userId];
    $types = "i";
    $conditions = [];

    if ($category !== 'all') {
        $conditions[] = "c.name = ?";
        $params[] = $category;
        $types .= "s";
    }

    if (!empty($search)) {
        $conditions[] = "(i.title LIKE CONCAT('%', ?, '%') OR l.username LIKE CONCAT('%', ?, '%'))";
        array_push($params, $search, $search);
        $types .= "ss";
    }

    if (!empty($conditions)) {
        $base_query .= " WHERE " . implode(' AND ', $conditions);
    }

    $base_query .= " GROUP BY i.id, i.image_path, i.title, i.caption, up.nama_lengkap, up.foto, l.username ";

    // Logika sorting
    switch ($sort) {
        case 'popular':
            $base_query .= " ORDER BY like_count DESC, i.uploaded_at DESC";
            break;
        case 'oldest':
            $base_query .= " ORDER BY i.uploaded_at ASC";
            break;
        default:
            $base_query .= " ORDER BY i.uploaded_at DESC";
            break;
    }

    $offset = ($page - 1) * $limit;
    $final_query = $base_query . " LIMIT ? OFFSET ?";
    array_push($params, $limit, $offset);
    $types .= "ii";

    $stmt = mysqli_prepare($koneksi, $final_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $images[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    return $images;
}

// [DIPERBAIKI 1/3] Ambil parameter pencarian dari URL untuk pemuatan halaman awal
$searchQuery = $_GET['search'] ?? '';
$sortQuery = $_GET['sort'] ?? 'latest';
$categoryQuery = $_GET['category'] ?? 'all';

// Logika untuk menangani request AJAX
if (isset($_GET['ajax'])) {
    usleep(500000); // Delay untuk simulasi loading
    $sort = $_GET['sort'] ?? 'latest';
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? 'all';
    $page = $_GET['page'] ?? 1;
    $images = getAllImagesWithBookmarkStatus($id, $koneksi, $category, $search, 10, $page, $sort);

    $defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
    $GLOBALS['defaultPhotoPathGlobal'] = $defaultPhotoPath;

    include 'image_gallery.php';
    exit;
}

$profil = getMyProfile($id, $koneksi);
$categories = getCategories($koneksi);
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath;
$namaDepan = !empty($profil['nama_lengkap']) ? htmlspecialchars($profil['nama_lengkap']) : 'User';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dasbor - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/style_dashboard_user.css">
</head>

<body>
    <header class="header-nav">
        <nav class="navbar" role="navigation" aria-label="Main navigation">
            <a href="#" class="logo-link" aria-label="Homepage">
                <img src="../../../public/assets/img/logo.png" alt="MizuPix Logo" class="logo-img">
                <span class="logo-text">MizuPix</span>
            </a>
            <div class="search-wrapper">
                <div class="search-container">
                    <i class='bx bx-search search-icon'></i>
                    <input type="search" id="searchInput" class="search-input" placeholder="Cari karya, seniman, atau kategori..." autocomplete="off" value="<?php echo htmlspecialchars($searchQuery); ?>" />
                </div>
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
            <div class="nav-actions">
                <a href="../user/bookmarks_user.php" class="icon-button" aria-label="Gambar tersimpan" title="Tersimpan"><i class='bx bxs-bookmark'></i></a>
                <div class="profile-container">
                    <button class="profile-btn" aria-haspopup="true" aria-expanded="false" title="Profil pengguna">
                        <img src="<?php echo !empty($profil['foto']) ? htmlspecialchars($profil['foto']) : $defaultPhotoPath; ?>" alt="Foto Profil" class="profile-icon" />
                    </button>
                    <div class="profile-dropdown" id="profile-menu" role="menu">
                        <a href="../user/notification_user.php" role="menuitem" class="notification-link">Notifikasi
                            <span class="unread-indicator-menu" id="menuUnreadIndicator" style="display: none;"></span>
                        </a>
                        <a href="../user/profile_user.php" role="menuitem">Profil</a>
                        <a href="../login/logout.php" role="menuitem" class="logout-link">Keluar</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="category-bar-wrapper">
        <div class="category-bar">
            <button class="category-button active" data-category="all">Semua</button>
            <?php foreach ($categories as $category): ?>
                <button class="category-button" data-category="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="fab-menu">
        <a href="uploadimage_user.php" class="fab-button" title="Unggah Gambar"><i class='bx bx-plus'></i></a>
    </div>

    <button id="scrollTopBtn" title="Kembali ke atas"><i class='bx bx-arrow-to-top'></i></button>

    <main class="main-container">
        <div class="page-controls">
            <div class="welcome-header">
                <h1>Selamat datang, <?php $namaDepan = (!empty(trim($profil['nama_lengkap'] ?? ''))) ? explode(' ', trim($profil['nama_lengkap']))[0] : 'User';
                                    echo htmlspecialchars($namaDepan); ?>!</h1>
                <p>Temukan inspirasi seni tanpa batas hari ini.</p>
            </div>

            <div class="sorting-options">
                <label class="sort-label">Urutkan:</label>
                <div class="sort-pills" id="sortPillsContainer">
                    <button class="sort-pill active" data-sort="latest">Terbaru</button>
                    <button class="sort-pill" data-sort="popular">Populer</button>
                    <button class="sort-pill" data-sort="oldest">Terlama</button>
                </div>
            </div>
        </div>

        <div class="image-gallery" id="galleryContainer">
            <?php
            if (!empty($initial_images)) {
                $GLOBALS['defaultPhotoPathGlobal'] = $defaultPhotoPath;
                $images = $initial_images;
                include 'image_gallery.php';
            } else {
                echo '<p class="gallery-message initial">Belum ada gambar untuk ditampilkan.</p>';
            }
            ?>
        </div>

        <div id="loader" class="loader-container"></div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- State Management ---
            let currentPage = 1,
                isLoading = false,
                hasMore = true,
                searchTimeout = null;
            let currentCategory = '<?php echo htmlspecialchars($categoryQuery); ?>';
            let currentSearchTerm = '<?php echo htmlspecialchars($searchQuery, ENT_QUOTES); ?>';
            let currentSort = '<?php echo htmlspecialchars($sortQuery); ?>';

            // --- DOM Elements ---
            const galleryContainer = document.getElementById('galleryContainer');
            const loader = document.getElementById('loader');
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            const searchInput = document.getElementById('searchInput');
            const suggestionsContainer = document.getElementById('searchSuggestions');
            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profile-menu');
            const searchWrapper = document.querySelector('.search-wrapper');
            const profileUnreadIndicator = document.getElementById('profileUnreadIndicator');
            const menuUnreadIndicator = document.getElementById('menuUnreadIndicator');
            const sortContainer = document.getElementById('sortPillsContainer');

            // --- Helper Functions ---
            const showSkeletonLoaders = () => {
                const skeletonHTML = `<div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-footer"><div class="skeleton-avatar"></div><div class="skeleton-text"></div></div></div>`.repeat(10);
                loader.innerHTML = skeletonHTML;
            };
            const hideSkeletonLoaders = () => {
                loader.innerHTML = '';
            };

            // --- Rebind Event Listeners for Dynamic Content ---
            const rebindAllEventListeners = () => {
                // [PENTING] Logika untuk membuat tombol bookmark berfungsi pada gambar yang baru dimuat
                document.querySelectorAll('.save-btn:not(.bound)').forEach(button => {
                    button.classList.add('bound');
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const imageId = this.dataset.imageId;
                        const icon = this.querySelector('i');
                        const text = this.querySelector('span');

                        fetch('../../controller/bookmark_handle_controller.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    image_id: imageId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'saved') {
                                    this.classList.add('saved');
                                    icon.className = 'bx bxs-bookmark';
                                    if (text) text.textContent = 'Tersimpan';
                                } else if (data.status === 'removed') {
                                    this.classList.remove('saved');
                                    icon.className = 'bx bx-bookmark';
                                    if (text) text.textContent = 'Simpan';
                                }
                            })
                            .catch(error => console.error('Bookmark Error:', error));
                    });
                });
            };

            // --- Core Function to Fetch Images ---
            const fetchImages = (isNewQuery = false) => {
                if (isLoading || (!hasMore && !isNewQuery)) return;
                isLoading = true;
                if (isNewQuery) {
                    currentPage = 1;
                    hasMore = true;
                    galleryContainer.innerHTML = '';
                }
                showSkeletonLoaders();
                const params = new URLSearchParams({
                    ajax: 1,
                    category: currentCategory,
                    search: currentSearchTerm,
                    page: currentPage,
                    sort: currentSort
                });

                // Pastikan endpoint ini benar, sesuai dengan file PHP yang menangani AJAX
                fetch(`dashboard_user.php?${params.toString()}`)
                    .then(response => response.text())
                    .then(html => {
                        hideSkeletonLoaders();
                        galleryContainer.style.columnCount = '';

                        if (html.trim()) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            Array.from(tempDiv.children).forEach((child, index) => {
                                child.style.animationDelay = `${index * 50}ms`;
                                galleryContainer.appendChild(child);
                            });
                            currentPage++;
                            hasMore = true;
                        } else {
                            hasMore = false;
                            if (isNewQuery && galleryContainer.innerHTML === '') {
                                galleryContainer.innerHTML = '<p class="gallery-message">Tidak ada hasil yang ditemukan.</p>';
                                galleryContainer.style.columnCount = '1';
                            }
                        }
                        isLoading = false;
                        rebindAllEventListeners(); // Panggil rebind di sini setelah gambar baru ditambahkan
                    })
                    .catch(err => {
                        console.error('Fetch Error:', err);
                        hideSkeletonLoaders();
                        isLoading = false;
                    });
            };

            // --- Initial Page Load ---
            fetchImages(true);

            // --- Event Listeners Setup ---

            // Infinite Scroll Observer
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !isLoading) fetchImages();
            }, {
                rootMargin: '400px'
            });
            if (loader) observer.observe(loader);

            // Sorting Pills
            if (sortContainer) {
                sortContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('sort-pill') && !e.target.classList.contains('active')) {
                        this.querySelector('.sort-pill.active').classList.remove('active');
                        e.target.classList.add('active');
                        currentSort = e.target.dataset.sort;
                        fetchImages(true);
                    }
                });
            }

            // Scroll to Top Button
            window.onscroll = () => {
                if (scrollTopBtn) scrollTopBtn.classList.toggle('show', document.body.scrollTop > 200 || document.documentElement.scrollTop > 200);
            };
            if (scrollTopBtn) scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Category Filtering
            document.querySelectorAll('.category-button').forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelector('.category-button.active').classList.remove('active');
                    this.classList.add('active');
                    currentCategory = this.dataset.category;
                    currentSearchTerm = '';
                    searchInput.value = '';
                    fetchImages(true);
                });
            });

            // --- Search Logic ---
            const renderSuggestions = (data, container) => {
                if (!container) return;
                let html = '';

                // Fungsi untuk membuat satu baris suggestion
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
                // Pastikan endpoint get_search_suggestions.php ada dan path-nya benar
                fetch(`/Gallery_Seni_Online/app/views/user/get_search_suggestions.php?term=${encodeURIComponent(term)}`)
                    .then(res => res.json())
                    .then(data => renderSuggestions(data, container))
                    .catch(err => console.error("Fetch suggestions error:", err));
            };

            const performSearch = (term) => {
                currentSearchTerm = term.trim();
                // Panggil fungsi utama untuk memuat gambar dengan query baru
                fetchImages(true);
            };

            if (searchInput) {
                searchInput.addEventListener('focus', () => {
                    if (searchInput.value) {
                        handleSearchInput(searchInput.value.trim(), suggestionsContainer);
                    }
                });

                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => handleSearchInput(searchInput.value.trim(), suggestionsContainer), 300);
                });

                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performSearch(searchInput.value);
                        if (suggestionsContainer) suggestionsContainer.style.display = 'none';
                    }
                });
            }

            if (suggestionsContainer) {
                suggestionsContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('suggestion-item')) {
                        const value = e.target.textContent.replace('@', '');

                        // Set nilai input dan langsung lakukan pencarian
                        searchInput.value = value;
                        performSearch(value);

                        // Sembunyikan container setelah dipilih
                        suggestionsContainer.style.display = 'none';
                    }
                });
            }

            // Notifikasi
            fetch('../../controller/notification_handler.php?action=unread_count')
                .then(res => res.json())
                .then(data => {
                    if (data && data.unread_count > 0) {
                        if (profileUnreadIndicator) profileUnreadIndicator.style.display = 'block';
                        if (menuUnreadIndicator) menuUnreadIndicator.style.display = 'block';
                    }
                });

            // Dropdown & Global Click Handler
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
            }

            document.addEventListener('click', (e) => {
                if (profileBtn && !profileBtn.parentElement.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                }
                if (searchWrapper && !searchWrapper.contains(e.target)) {
                    if (suggestionsContainer) suggestionsContainer.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>