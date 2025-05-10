<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: ../login/login.php");
    exit;
}

$username = "Pengguna"; // Placeholder, bisa diisi dengan data sesungguhnya dari DB
$email = $_SESSION['email'] ?? 'user@example.com';

// Contoh data gambar statis, nanti bisa diganti dari DB
$images = [
    ["url" => "https://source.unsplash.com/random/400x600?nature,water", "title" => "Latar Air 1"],
    ["url" => "https://source.unsplash.com/random/400x600?japan,mountains", "title" => "Gunung Jepang"],
    ["url" => "https://source.unsplash.com/random/400x600?japan,temple", "title" => "Kuil Jepang"],
    ["url" => "https://source.unsplash.com/random/400x600?waterfalls", "title" => "Air Terjun"],
    ["url" => "https://source.unsplash.com/random/400x600?japan,city", "title" => "Kota Jepang"],
    ["url" => "https://source.unsplash.com/random/400x600?flowers,sakura", "title" => "Sakura"],
    ["url" => "https://source.unsplash.com/random/400x600?sea", "title" => "Laut Tenang"],
    ["url" => "https://source.unsplash.com/random/400x600?architecture,japan", "title" => "Arsitektur Jepang"],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../../../public/assets/css/style_dashboard_user.css" />
    <title>Dashboard - Mizupix</title>
</head>

<body>
    <header>
        <h1>Mizupix</h1>
        <nav>
            <span><?php echo htmlspecialchars($username); ?></span>
            <form action="../login/logout.php" method="POST" style="margin:0;">
                <button type="submit">Keluar</button>
            </form>
        </nav>
    </header>
    <div class="container">
        <aside class="sidebar" role="complementary" aria-label="Profil Pengguna">
            <div class="profile-pic" title="Profil Pengguna"></div>
            <div class="profile-name"><?php echo htmlspecialchars($username); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
        </aside>
        <main class="content" role="main">
            <div class="grid" aria-label="Gallery Gambar">
                <?php foreach ($images as $img): ?>
                    <div class="grid-item" tabindex="0">
                        <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" loading="lazy" />
                        <div class="title"><?php echo htmlspecialchars($img['title']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>

</html>