<?php
// ====== Inisialisasi & Koneksi Database ======
require_once '../../../config/inc_koneksi.php';
require('fpdf/fpdf.php');

// ====== Query Data Statistik ======

// Total Pengguna
$result_users = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM login");
$total_users = mysqli_fetch_assoc($result_users)['total'];

// Total Gambar dalam 30 hari terakhir
$result_images = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM images WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$total_images_30_days = mysqli_fetch_assoc($result_images)['total'];

// Kategori Terpopuler (Top 5)
$query_kategori = "
    SELECT c.name AS nama_kategori, COUNT(i.id) AS jumlah_gambar
    FROM categories c
    LEFT JOIN images i ON c.id = i.category_id
    GROUP BY c.id, c.name
    ORDER BY jumlah_gambar DESC
    LIMIT 5
";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$kategori_populer = [];
while ($row = mysqli_fetch_assoc($result_kategori)) {
    $kategori_populer[] = $row;
}

// ====== Definisi Kelas PDF (FPDF) ======

class PDF extends FPDF
{
    // Header Halaman
    function Header()
    {
        $this->Image('../../../public/assets/img/loading_logo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Laporan Statistik MizuPix', 0, 0, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 10, 'Dicetak pada: ' . date('d M Y'), 0, 0, 'R');
        $this->Ln(20);
    }

    // Footer Halaman
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Judul Chapter
    function ChapterTitle($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 6, $title, 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Isi Chapter
    function ChapterBody($data)
    {
        $this->SetFont('Arial', '', 12);
        foreach ($data as $label => $value) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(80, 8, $label);
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 8, ': ' . $value);
            $this->Ln();
        }
        $this->Ln(5);
    }

    // Tabel Laporan
    function ReportTable($header, $data)
    {
        $w = array(120, 70);
        $this->SetFont('Arial', 'B', 12);
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
        $this->Ln();
        $this->SetFont('Arial', '', 12);
        foreach ($data as $row) {
            $this->Cell($w[0], 6, $row['nama_kategori'], 'LR');
            $this->Cell($w[1], 6, $row['jumlah_gambar'] . ' gambar', 'LR', 0, 'R');
            $this->Ln();
        }
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(10);
    }
}

// ====== Inisialisasi & Pembuatan PDF ======

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// ====== Konten Laporan PDF ======

$pdf->ChapterTitle('Ringkasan Umum Website');
$summary_data = [
    'Total Pengguna Terdaftar' => $total_users,
    'Total Gambar Diupload (30 Hari Terakhir)' => $total_images_30_days
];
$pdf->ChapterBody($summary_data);

$pdf->ChapterTitle('Peringkat Kategori Terpopuler');
$header_table = array('Nama Kategori', 'Jumlah Gambar');
$pdf->ReportTable($header_table, $kategori_populer);

// ====== Output PDF ke Browser ======

$pdf->Output('D', 'Laporan_MizuPix_' . date('Y-m-d') . '.pdf');
