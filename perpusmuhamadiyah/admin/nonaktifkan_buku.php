<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: buku.php");
    exit;
}

$id = intval($_GET['id']);

// Periksa buku ada atau tidak
$stmt = $conn->prepare("SELECT judul, status FROM buku WHERE id=?");
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

// Tandai buku sebagai tidak aktif
$stmt_update = $conn->prepare("UPDATE buku SET aktif=0 WHERE id=?");
$stmt_update->bind_param("i", $id);
$stmt_update->execute();

echo "<script>
    alert('Buku \"$buku[judul]\" berhasil dinonaktifkan.');
    window.location.href='buku.php';
</script>";
exit;
?>
