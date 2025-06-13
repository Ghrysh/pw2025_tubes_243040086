<?php
// Variabel koneksi
$host  = "localhost";
$user  = "root";
$pass  = "";
$db    = "db_artgallery";

// Membuat koneksi dengan gaya Object-Oriented
$koneksi = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// Set character set untuk menghindari masalah encoding
$koneksi->set_charset("utf8mb4");
