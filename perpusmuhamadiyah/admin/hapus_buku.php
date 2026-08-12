<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Pastikan ada parameter id
if(!isset($_GET['id'])){
    header("Location: buku.php");
    exit;
}

$id = intval($_GET['id']);

// Ambil data buku
$stmt = $conn->prepare("SELECT judul, gambar, status FROM buku WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo "<script>
        alert('Buku tidak ditemukan!');
        window.location.href='buku.php';
    </script>";
    exit;
}

$buku = $result->fetch_assoc();

// Cek apakah buku sedang dipinjam
if($buku['status'] === 'Dipinjam'){
    echo "<script>
        alert('Buku ini sedang dipinjam dan tidak bisa dihapus!');
        window.location.href='buku.php';
    </script>";
    exit;
}

// Hapus gambar buku jika ada
if($buku['gambar'] && file_exists("../uploads/".$buku['gambar'])){
    unlink("../uploads/".$buku['gambar']);
}

// Hapus buku dari database
$stmt_delete = $conn->prepare("DELETE FROM buku WHERE id=?");
$stmt_delete->bind_param("i", $id);
$stmt_delete->execute();

echo "<script>
    alert('Buku \"$buku[judul]\" berhasil dihapus.');
    window.location.href='buku.php';
</script>";
exit;
?>
