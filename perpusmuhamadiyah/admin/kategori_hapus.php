<?php
session_start();
include "../config.php";

// Pastikan admin login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ambil dan validasi ID dari URL
$id = $_GET['id'] ?? 0;
if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
    // Set pesan error di session untuk ditampilkan di halaman kategori
    $_SESSION['error_message'] = "ID Kategori tidak valid.";
    header("Location: kategori.php");
    exit;
}

// 1. Cek apakah kategori ini masih digunakan oleh buku
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM buku WHERE kategori_id = ?");
if (!$check_stmt) {
    // Error saat menyiapkan query
    $_SESSION['error_message'] = "Gagal menyiapkan pengecekan buku: " . $conn->error;
    header("Location: kategori.php");
    exit;
}

$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();
$check_stmt->close();

$book_count = $row['count'] ?? 0; // Jumlah buku yang menggunakan kategori ini

// 2. Jika TIDAK ada buku yang menggunakan, baru hapus kategorinya
if ($book_count == 0) {
    $delete_stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
    if (!$delete_stmt) {
         // Error saat menyiapkan query hapus
         $_SESSION['error_message'] = "Gagal menyiapkan penghapusan kategori: " . $conn->error;
    } else {
        $delete_stmt->bind_param("i", $id);
        if ($delete_stmt->execute()) {
            // Berhasil dihapus
            $_SESSION['success_message'] = "Kategori berhasil dihapus.";
        } else {
            // Gagal menghapus (error lain, jarang terjadi jika cek pertama berhasil)
            $_SESSION['error_message'] = "Gagal menghapus kategori: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
} else {
    // 3. Jika MASIH ADA buku yang menggunakan, jangan hapus dan beri pesan
    $_SESSION['error_message'] = "Kategori tidak dapat dihapus karena masih digunakan oleh $book_count buku. Harap pindahkan buku ke kategori lain atau hapus buku tersebut terlebih dahulu.";
}

// Selalu redirect kembali ke halaman kategori
header("Location: kategori.php");
exit;
?>