<?php
function editBuku($conn, $id, $judul, $penulis, $tahun, $kategori_id, $rak, $status, $fileGambar){
    // Ambil data buku lama
    $stmt = $conn->prepare("SELECT gambar FROM buku WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows == 0){
        return ["status"=>false, "msg"=>"Buku tidak ditemukan!"];
    }
    $buku = $result->fetch_assoc();
    $gambar = $buku['gambar']; // default gambar lama

    // Handle upload gambar baru jika ada
    if($fileGambar && $fileGambar['error'] == 0){
        $upload_dir = "../uploads/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($fileGambar['name'], PATHINFO_EXTENSION);
        $gambar = uniqid() . "." . $ext;
        if(!move_uploaded_file($fileGambar['tmp_name'], $upload_dir . $gambar)){
            return ["status"=>false, "msg"=>"Gagal mengupload gambar!"];
        }
    }

    // Update data buku termasuk posisi rak
    $stmt = $conn->prepare("UPDATE buku SET judul=?, penulis=?, tahun=?, kategori_id=?, rak=?, status=?, gambar=? WHERE id=?");
    $stmt->bind_param("ssiisssi", $judul, $penulis, $tahun, $kategori_id, $rak, $status, $gambar, $id);


    if($stmt->execute()){
        return ["status"=>true, "msg"=>"Buku berhasil diperbarui!"];
    } else {
        return ["status"=>false, "msg"=>"Gagal memperbarui buku!"];
    }
}
?>
