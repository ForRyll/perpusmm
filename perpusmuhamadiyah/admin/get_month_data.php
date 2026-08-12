<?php
include "../config.php";

// Set header ke JSON di awal
header('Content-Type: application/json');

// 1. AMBIL INPUT BULAN & TAHUN
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$today = date('Y-m-d');

// Inisialisasi array respons
$data = [];

// -----------------------------------------------------------------
// BAGIAN 1: STATISTIK KESELURUHAN (OVERALL) UNTUK KARTU
// -----------------------------------------------------------------
$data['totalBuku'] = $conn->query("SELECT COUNT(*) AS total FROM buku")->fetch_assoc()['total'];
$data['tersedia'] = $conn->query("SELECT COUNT(*) AS total FROM buku WHERE status='Tersedia'")->fetch_assoc()['total'];
$data['dipinjam'] = $conn->query("SELECT COUNT(*) AS total FROM buku WHERE status='Dipinjam'")->fetch_assoc()['total'];

// -----------------------------------------------------------------
// BAGIAN 2: STATISTIK SPESIFIK BULANAN (UNTUK KARTU & PDF)
// -----------------------------------------------------------------

// Ambil total peminjaman HANYA di bulan ini
$data['totalPeminjaman'] = $conn->query("SELECT COUNT(*) AS total FROM peminjaman WHERE MONTH(tanggal_pinjam)=$month AND YEAR(tanggal_pinjam)=$year")->fetch_assoc()['total'];

// Ambil detail keterlambatan HANYA di bulan ini (untuk PDF dan hitungan kartu)
$lateDetailsQuery = $conn->query("
    SELECT 
        p.user_nis, 
        p.user_name, 
        p.tanggal_pinjam, 
        p.tanggal_kembali,
        CASE 
            WHEN p.status = 'Dipinjam' AND DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            WHEN p.status = 'Kembali' AND DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            ELSE 0
        END AS denda
    FROM peminjaman p
    WHERE 
        YEAR(p.tanggal_pinjam) = $year
        AND MONTH(p.tanggal_pinjam) = $month
        AND (
            (p.status = 'Dipinjam' AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today')
            OR
            (p.status = 'Kembali' AND p.tanggal_kembali > DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY))
        )
    ORDER BY p.tanggal_pinjam ASC
");

$lateStudentDetails = $lateDetailsQuery->fetch_all(MYSQLI_ASSOC);

// Kirim data ini untuk PDF
$data['lateStudentDetails'] = $lateStudentDetails;

// Hitung jumlah telat dan total denda dari data di atas
$totalDendaBulanan = 0;
foreach ($lateStudentDetails as $row) {
    $totalDendaBulanan += (int)$row['denda'];
}

// [PERBAIKAN] Ini adalah data untuk kartu "Siswa Telat" dan "Total Denda"
$data['jumlahTelat'] = count($lateStudentDetails);
$data['totalDenda'] = $totalDendaBulanan;


// -----------------------------------------------------------------
// BAGIAN 3: STATISTIK TAHUNAN (UNTUK GRAFIK)
// -----------------------------------------------------------------

// Kueri ini mengambil data untuk SEMUA BULAN dalam TAHUN YANG DIPILIH
$laporanBulanQuery = $conn->query("
    SELECT 
        MONTH(p.tanggal_pinjam) AS bulan,
        COUNT(*) AS total_pinjam,
        SUM(CASE WHEN p.tanggal_kembali IS NOT NULL 
                 AND p.tanggal_kembali <= DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)
             THEN 1 ELSE 0 END) AS tepat_waktu,
        SUM(CASE WHEN p.tanggal_kembali IS NOT NULL 
                 AND p.tanggal_kembali > DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)
             THEN 1 ELSE 0 END) AS terlambat
    FROM peminjaman p
    WHERE YEAR(p.tanggal_pinjam) = $year
    GROUP BY MONTH(p.tanggal_pinjam)
    ORDER BY bulan ASC
");

// Buat "peta" untuk menyimpan data per bulan
$totalPinjamMap = [];
$tepatWaktuMap = [];
$terlambatMap = [];

while($row = $laporanBulanQuery->fetch_assoc()){
    $bulanNum = (int)$row['bulan'];
    $totalPinjamMap[$bulanNum] = (int)$row['total_pinjam'];
    $tepatWaktuMap[$bulanNum] = (int)$row['tepat_waktu'];
    $terlambatMap[$bulanNum] = (int)$row['terlambat'];
}

// Inisialisasi array final untuk grafik
$data['labels'] = [];
$data['totalPinjam'] = [];
$data['tepatWaktu'] = [];
$data['terlambat'] = [];

// Loop 12 bulan untuk mengisi data grafik
for ($i = 1; $i <= 12; $i++) {
    // [PERBAIKAN] Tambahkan tahun ($year) yang dipilih ke label
    $data['labels'][] = date('F', mktime(0, 0, 0, $i, 1)) . " " . $year;
    $data['totalPinjam'][] = isset($totalPinjamMap[$i]) ? $totalPinjamMap[$i] : 0;
    $data['tepatWaktu'][] = isset($tepatWaktuMap[$i]) ? $tepatWaktuMap[$i] : 0;
    $data['terlambat'][] = isset($terlambatMap[$i]) ? $terlambatMap[$i] : 0;
}


// -----------------------------------------------------------------
// BAGIAN 4: KIRIM RESPONS
// -----------------------------------------------------------------

echo json_encode($data);
exit;
?>