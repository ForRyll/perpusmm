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

// Update semua pesan menjadi "sudah_dibaca = 1"
if($conn->query("UPDATE kontak_pesan SET sudah_dibaca = 1 WHERE sudah_dibaca = 0")) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal update database']);
}
?>