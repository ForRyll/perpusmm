<?php
session_start();
// Sesuaikan path config.php jika perlu
include "../config.php"; 

if(!isset($_SESSION['admin'])){
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

header('Content-Type: application/json');

// 1. Cek notif keterlambatan baru
$today = date('Y-m-d');
$notifQuery = $conn->query("
    SELECT p.id as peminjaman_id, p.user_nis, p.user_name, b.judul,
           DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) as terlambat_hari,
           (DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000) as denda
    FROM peminjaman p
    LEFT JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'Dipinjam'
    AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today'
    AND p.notif_seen = 0
    ORDER BY p.tanggal_pinjam ASC
");
$newLoanList = $notifQuery->fetch_all(MYSQLI_ASSOC);
$newLoanCount = count($newLoanList);

// 2. Cek pesan baru
$pesanBaruQuery = $conn->query("SELECT COUNT(*) as total FROM kontak_pesan WHERE sudah_dibaca = 0");
$newMessageCount = $pesanBaruQuery->fetch_assoc()['total'];

// 3. Ambil semua pesan jika ada yang baru (agar modal up-to-date)
$allPesanList = null;
if ($newMessageCount > 0) {
     $semuaPesanQuery = $conn->query("SELECT * FROM kontak_pesan ORDER BY tanggal_kirim DESC");
     $allPesanList = $semuaPesanQuery->fetch_all(MYSQLI_ASSOC);
}


// 4. Kirim data sebagai JSON
echo json_encode([
    'newLoanNotifs' => $newLoanCount,
    'newMessageNotifs' => $newMessageCount,
    'allLateLoans' => $newLoanList, // Mengirim list baru untuk dropdown
    'allMessages' => $allPesanList // Mengirim list baru untuk modal
]);

?>