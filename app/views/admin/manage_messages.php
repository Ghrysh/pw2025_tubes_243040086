<?php
session_start();
require_once '../../../config/inc_koneksi.php';

// =================================================================
// PROSES LOGIKA PHP
// =================================================================

// Logika Hapus Pesan
if (isset($_GET['delete'])) {
    $msg_id = (int) $_GET['delete'];
    $query_delete = "DELETE FROM messages WHERE id = $msg_id";
    mysqli_query($koneksi, $query_delete);
    header("Location: manage_messages.php?status=deleted");
    exit;
}

// Ambil profil admin yang sedang login untuk header
$admin_id = (int) ($_SESSION['id'] ?? 0);
$profil_query = "SELECT * FROM user_profiles WHERE user_id = $admin_id";
$profil_result = mysqli_query($koneksi, $profil_query);
$profil = mysqli_fetch_assoc($profil_result);

// Ambil semua data pesan dari pengguna
// PENTING: Sesuaikan nama tabel jika berbeda di database Anda.
$query_messages = "
    SELECT 
        messages.id, 
        login.username, 
        login.email, 
        messages.subject, 
        messages.message, 
        messages.created_at 
    FROM messages 
    JOIN login ON messages.user_id = login.id 
    ORDER BY messages.created_at DESC
";
$result_messages = mysqli_query($koneksi, $query_messages);
$messages = [];
if ($result_messages) {
    while ($row = mysqli_fetch_assoc($result_messages)) {
        $messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/assets/css/style_manage_messages_admin.css">
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Pesan Masuk - MizuPix Admin</title>
</head>

<body>
    <aside class="sidebar">
        <a href="dashboard_admin.php" class="sidebar__logo">
            <img src="../../../public/assets/img/loading_logo.png" alt="MizuPix Logo">
            <span>MizuPix</span>
        </a>

        <nav class="sidebar__nav">
            <ul>
                <li><a href="dashboard_admin.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
                <li><a href="manage_images.php"><i class='bx bxs-image-alt'></i><span>Gambar</span></a></li>
                <li><a href="manage_users.php"><i class='bx bxs-user-account'></i><span>Pengguna</span></a></li>
                <li><a href="manage_categories.php"><i class='bx bxs-category-alt'></i><span>Kategori</span></a></li>
                <li><a href="manage_messages.php" class="active"><i class='bx bxs-chat'></i><span>Pesan</span></a></li>
                <li><a href="manage_account.php"><i class='bx bxs-user-plus'></i><span>Kelola Akun</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header__title">
                <h1>Pesan Masuk</h1>
                <p>Lihat semua pesan yang dikirim oleh pengguna</p>
            </div>
            <div class="header__profile">
                <button class="profile-btn">
                    <?php if (!empty($profil['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($profil['foto']); ?>" alt="Foto Profil" class="profile-icon" />
                    <?php else: ?>
                        <i class='bx bxs-user-circle'></i>
                    <?php endif; ?>
                </button>
                <div class="profile-dropdown">
                    <a href="../../views/admin/profile_admin.php">Profile</a>
                    <a href="../login/logout.php" class="logout">Logout</a>
                </div>
            </div>
        </header>

        <section class="widget-container">
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Pesan Masuk</h3>
                    <div class="search-container">
                        <i class='bx bx-search'></i>
                        <input type="text" id="searchInput" placeholder="Cari pesan...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="content-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="username">Pengirim</th>
                                    <th class="sortable" data-sort="subject">Subjek</th>
                                    <th>Pesan</th>
                                    <th class="sortable" data-sort="created_at">Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="messageTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesData = <?php echo json_encode($messages); ?>;
            const tableBody = document.getElementById('messageTableBody');
            const searchInput = document.getElementById('searchInput');

            let currentSort = {
                key: 'created_at',
                direction: 'desc'
            };

            // Fungsi untuk mengganti newline (\n) dengan <br>
            function nl2br(str) {
                return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
            }

            function renderTable(data) {
                tableBody.innerHTML = '';
                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem;">Tidak ada pesan untuk ditampilkan.</td></tr>`;
                    return;
                }

                data.forEach(msg => {
                    const tr = document.createElement('tr');
                    const date = new Date(msg.created_at);
                    const formattedDate = date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }) + ', ' + date.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    tr.innerHTML = `
                    <td>
                        <div class="sender-info">
                            <span class="sender-name">${msg.username}</span>
                            <span class="sender-email">${msg.email}</span>
                        </div>
                    </td>
                    <td>${msg.subject}</td>
                    <td class="td-message">${nl2br(msg.message)}</td>
                    <td>${formattedDate}</td>
                    <td>
                        <div class="action-buttons">
                            <a href="manage_messages.php?delete=${msg.id}" class="btn-action btn-delete" title="Hapus Pesan" onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');"><i class='bx bxs-trash'></i></a>
                        </div>
                    </td>
                `;
                    tableBody.appendChild(tr);
                });
            }

            function sortData(data, key, direction) {
                data.sort((a, b) => {
                    const valA = a[key] ? a[key].toString().toLowerCase() : '';
                    const valB = b[key] ? b[key].toString().toLowerCase() : '';
                    let comparison = 0;
                    if (valA > valB) comparison = 1;
                    else if (valA < valB) comparison = -1;
                    return direction === 'asc' ? comparison : -comparison;
                });
                return data;
            }

            searchInput.addEventListener('keyup', () => {
                const searchTerm = searchInput.value.toLowerCase();
                const filteredData = messagesData.filter(msg => {
                    return (msg.username.toLowerCase().includes(searchTerm) ||
                        msg.email.toLowerCase().includes(searchTerm) ||
                        msg.subject.toLowerCase().includes(searchTerm) ||
                        msg.message.toLowerCase().includes(searchTerm)
                    );
                });
                renderTable(sortData(filteredData, currentSort.key, currentSort.direction));
            });

            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', () => {
                    const sortKey = header.getAttribute('data-sort');
                    if (currentSort.key === sortKey) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.key = sortKey;
                        currentSort.direction = 'asc';
                    }
                    document.querySelectorAll('.sortable').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
                    header.classList.add(`sort-${currentSort.direction}`);
                    searchInput.dispatchEvent(new Event('keyup'));
                });
            });

            const sortedInitialData = sortData(messagesData, currentSort.key, currentSort.direction);
            document.querySelector(`[data-sort="${currentSort.key}"]`).classList.add(`sort-${currentSort.direction}`);
            renderTable(sortedInitialData);

            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.querySelector('.profile-dropdown');
            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', () => {
                    profileDropdown.classList.toggle('show');
                });
                window.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>

</html>