<?php
session_start();
require_once '../../../config/inc_koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login/login.php");
    exit;
}
$currentUserId = (int)$_SESSION['id'];

function getNotifications($userId, $koneksi)
{
    $defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
    $notifications = [];
    $query = "
        SELECT 
            n.id, n.type, n.target_id, n.is_read, n.created_at,
            a.username as actor_username, 
            ap.nama_lengkap as actor_name,
            ap.foto as actor_photo,
            i.image_path as target_image,
            i.id as image_id
        FROM notifications n
        JOIN login a ON n.actor_id = a.id
        LEFT JOIN user_profiles ap ON a.id = ap.user_id
        LEFT JOIN images i ON n.target_id = i.id AND (n.type IN ('like', 'comment'))
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['actor_photo'] = $row['actor_photo'] ?? $defaultPhotoPath;
        $notifications[] = $row;
    }
    return $notifications;
}

function markNotificationsAsRead($userId, $koneksi)
{
    $stmt = mysqli_prepare($koneksi, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
}

function groupNotificationsByTime($notifications)
{
    $grouped = [];
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    foreach ($notifications as $notif) {
        $date = new DateTime($notif['created_at'], new DateTimeZone('Asia/Jakarta'));
        $diff = $now->diff($date);
        $groupName = 'Lebih Lama';
        if ($diff->y == 0 && $diff->m == 0) {
            if ($diff->d == 0) $groupName = 'Hari Ini';
            elseif ($diff->d == 1) $groupName = 'Kemarin';
            elseif ($diff->d < 7) $groupName = 'Minggu Ini';
        }
        $grouped[$groupName][] = $notif;
    }
    return $grouped;
}

function submitComment($userId, $imageId, $commentText, $koneksi)
{
    // Insert the comment into the comments table
    $query = "INSERT INTO comments (user_id, image_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "iis", $userId, $imageId, $commentText);
    mysqli_stmt_execute($stmt);

    // Check if the comment was successfully inserted
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        // Create a notification for the comment
        createNotification($userId, $imageId, $koneksi);
    }
}

function createNotification($userId, $imageId, $koneksi)
{
    // Assuming the actor ID is the user who made the comment
    $actorId = $userId; // This could be different based on your logic

    // Insert the notification into the notifications table
    $query = "INSERT INTO notifications (user_id, actor_id, type, target_id, created_at) VALUES (?, ?, 'comment', ?, NOW())";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "iii", $userId, $actorId, $imageId);
    mysqli_stmt_execute($stmt);
}

// Handle the comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'], $_POST['image_id'])) {
    $imageId = (int)$_POST['image_id']; // Get the image ID from the form submission
    $commentText = $_POST['comment_text']; // Get the comment text from the form submission

    // Validate input
    if (!empty($imageId) && !empty($commentText)) {
        submitComment($currentUserId, $imageId, $commentText, $koneksi);
    }
}

$notifications = getNotifications($currentUserId, $koneksi);
$groupedNotifications = groupNotificationsByTime($notifications);
markNotificationsAsRead($currentUserId, $koneksi);

$stmt_myprofile = mysqli_prepare($koneksi, "SELECT foto FROM user_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_myprofile, "i", $currentUserId);
mysqli_stmt_execute($stmt_myprofile);
$myProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_myprofile));
$defaultPhotoPath = '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png';
$userPhoto = ($myProfile && !empty($myProfile['foto'])) ? htmlspecialchars($myProfile['foto']) : $defaultPhotoPath;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifikasi - MizuPix</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../../../public/assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../public/assets/css/notification.css">
    <link rel="stylesheet" href="../../../public/assets/css/style_navbar.css">
</head>

<body>
    <?php
    include('../navbar/navbar.php');
    ?>

    <main class="main-container">
        <div class="page-header">
            <h1>Notifikasi</h1>
            <p>Semua aktivitas terbaru yang berkaitan dengan akun Anda.</p>
        </div>

        <div class="notification-page-container">
            <?php if (empty($groupedNotifications)): ?>
                <div class="empty-state">
                    <i class='bx bx-bell-off'></i>
                    <h2>Tidak Ada Notifikasi</h2>
                    <p>Saat seseorang menyukai, mengomentari, atau mengikuti Anda, notifikasinya akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedNotifications as $groupName => $groupItems): ?>
                    <div class="notification-group">
                        <h2 class="notification-group-title"><?php echo $groupName; ?></h2>
                        <div class="notification-list-page">
                            <?php foreach ($groupItems as $notif):
                                $message = '';
                                $link = '#';
                                $iconClass = '';
                                $iconName = '';
                                $targetImageHTML = '';
                                $actorName = htmlspecialchars($notif['actor_name'] ?? $notif['actor_username']);
                                $actorUsername = htmlspecialchars($notif['actor_username']);

                                switch ($notif['type']) {
                                    case 'like':
                                        $message = "<strong>{$actorName}</strong> menyukai karya Anda.";
                                        $link = "image_detail.php?id=" . htmlspecialchars($notif['image_id'] ?? 0);
                                        $iconClass = 'like';
                                        $iconName = 'bxs-heart';
                                        if (!empty($notif['target_image'])) {
                                            $targetImageHTML = "<img src='" . htmlspecialchars($notif['target_image']) . "' class='notification-target-image'>";
                                        }
                                        break;
                                    case 'comment':
                                        $message = "<strong>{$actorName}</strong> mengomentari karya Anda.";
                                        $link = "image_detail.php?id=" . htmlspecialchars($notif['image_id'] ?? 0);
                                        $iconClass = 'comment';
                                        $iconName = 'bxs-message-square-dots';
                                        if (!empty($notif['target_image'])) {
                                            $targetImageHTML = "<img src='" . htmlspecialchars($notif['target_image']) . "' class='notification-target-image'>";
                                        }
                                        break;
                                    case 'follow':
                                        $message = "<strong>{$actorName}</strong> mulai mengikuti Anda.";
                                        $link = "public_profile.php?username=" . $actorUsername;
                                        $iconClass = 'follow';
                                        $iconName = 'bxs-user-plus';
                                        break;
                                }
                            ?>
                                <a href="<?php echo $link; ?>" class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                    <div class="notification-icon-wrapper type-<?php echo $iconClass; ?>"><i class='bx <?php echo $iconName; ?>'></i></div>
                                    <div class="notification-text">
                                        <?php echo $message; ?>
                                        <span class="notification-time"><?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?></span>
                                    </div>
                                    <?php if (!$notif['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                                    <?php echo $targetImageHTML; ?>
                                </a>

                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // [JS] Dropdown profil dan logika pencarian
        // [JS] Render saran pencarian
        // [JS] Handle input dan klik pada saran pencarian
        // [JS] Tutup dropdown jika klik di luar
    </script>
</body>

</html>