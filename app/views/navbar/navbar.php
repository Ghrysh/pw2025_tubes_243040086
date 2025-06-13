<!-- Header Navigasi Utama -->
<header class="header-nav">
    <!-- Navbar Utama -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <!-- Logo dan Link ke Beranda -->
        <a href="dashboard_user.php" class="logo-link" aria-label="Homepage">
            <img src="../../../public/assets/img/logo.png" alt="MizuPix Logo" class="logo-img">
            <span class="logo-text">MizuPix</span>
        </a>
        <!-- Pencarian -->
        <div class="search-wrapper">
            <div class="search-container">
                <i class='bx bx-search search-icon'></i>
                <input type="search" id="searchInput" class="search-input" placeholder="Cari di MizuPix..." autocomplete="off" />
            </div>
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>
        <!-- Aksi Navigasi (Bookmark & Profil) -->
        <div class="nav-actions">
            <!-- Tombol Bookmark -->
            <a href="bookmarks_user.php" class="icon-button" aria-label="Gambar tersimpan" title="Tersimpan"><i class='bx bxs-bookmark'></i></a>
            <!-- Profil Pengguna dan Dropdown -->
            <div class="profile-container">
                <button class="profile-btn" aria-haspopup="true" aria-expanded="false" title="Profil pengguna">
                    <img src="<?php echo $userPhoto; ?>" alt="Foto Profil" class="profile-icon" />
                </button>
                <div class="profile-dropdown" id="profile-menu" role="menu">
                    <a href="../user/notification_user.php" role="menuitem" class="notification-link active">Notifikasi</a>
                    <a href="../user/profile_user.php" role="menuitem" class="profile-link active">Profil</a>
                    <a href="../login/logout.php" role="menuitem" class="logout-link active">Keluar</a>
                </div>
            </div>
        </div>
    </nav>
</header>