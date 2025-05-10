<?php
session_start();
require_once '../../config/inc_koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = urlencode("Mohon masukkan email dan kata sandi.");
        header("Location: ../views/login/login.php?error=$error");
        exit;
    }

    $stmt = $koneksi->prepare("SELECT id, email, password FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $dbemail, $dbpassword_hash);
        $stmt->fetch();

        if (password_verify($password, $dbpassword_hash)) {
            $_SESSION['userid'] = $id;
            $_SESSION['email'] = $dbemail;
            header("Location: ../views/user/dashboard_user.php");
            exit;
        } else {
            $error = urlencode("Email atau kata sandi salah.");
            header("Location: ../views/login/login.php??error=$error");
            exit;
        }
    } else {
        $error = urlencode("Email atau kata sandi salah.");
        header("Location: ../views/login/login.php??error=$error");
        exit;
    }

    $stmt->close();
}

$conn->close();
