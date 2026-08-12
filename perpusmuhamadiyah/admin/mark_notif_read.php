<?php
session_start();
// Sesuaikan path config.php jika perlu
include "../config.php"; 

// Hanya admin yang bisa
if(!isset($_SESSION['admin'])){
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$today = date('Y-m-d');

// Update semua notif keterlambatan menjadi "notif_seen = 1"
$sql = "
    UPDATE peminjaman 
    SET notif_seen = 1 
    WHERE status = 'Dipinjam'
    AND DATE_ADD(tanggal_pinjam, INTERVAL 7 DAY) < '$today'
    AND notif_seen = 0
";

if($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal update database']);
}
?>