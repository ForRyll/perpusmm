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

// Aktifkan buku
$stmt = $conn->prepare("UPDATE buku SET aktif=1 WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: buku.php");
exit;
