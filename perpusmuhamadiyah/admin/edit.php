<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);

// Ambil data buku
$stmt = $conn->prepare("SELECT * FROM buku WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0){
    // Sebaiknya redirect kembali ke daftar buku
    header("Location: buku.php");
    exit;
}
$buku = $result->fetch_assoc();

// Ambil daftar kategori
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama ASC");

// Proses edit
if(isset($_POST['edit'])){
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $tahun = $_POST['tahun'];
    $kategori_id = $_POST['kategori_id'];
    $rak = $_POST['rak'];
    $status = $_POST['status'];

    // Cek apakah gambar diupload
    $gambar = $buku['gambar']; // Default: gambar lama
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0){
        $upload_dir = "../uploads/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = uniqid() . "." . $ext;
        
        if(move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar)){
            // Hapus gambar lama jika berhasil upload yang baru
            if($buku['gambar'] && file_exists($upload_dir . $buku['gambar'])){
                unlink($upload_dir . $buku['gambar']);
            }
        } else {
            $gambar = $buku['gambar']; // Gagal upload, kembalikan ke gambar lama
        }
    }

    $stmt_update = $conn->prepare("UPDATE buku SET judul=?, penulis=?, tahun=?, kategori_id=?, rak=?, status=?, gambar=? WHERE id=?");
    $stmt_update->bind_param("ssiisssi", $judul, $penulis, $tahun, $kategori_id, $rak, $status, $gambar, $id);
    
    if($stmt_update->execute()){
        $success = "Buku berhasil diperbarui!";
        // reload data
        $buku['judul'] = $judul;
        $buku['penulis'] = $penulis;
        $buku['tahun'] = $tahun;
        $buku['kategori_id'] = $kategori_id;
        $buku['rak'] = $rak;
        $buku['status'] = $status;
        $buku['gambar'] = $gambar;
    } else {
        $error = "Gagal memperbarui buku!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Buku - E-Perpus Admin</title>
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
    .choices__inner {
        background-color: #fff;
        border-radius: 0.5rem; /* rounded-lg */
        border: 1px solid #fcd34d; /* border-yellow-300 */
        padding-top: 0.65rem;
        padding-bottom: 0.65rem;
        padding-left: 1rem; /* px-4 */
        min-height: auto;
        font-size: 1rem;
        color: #374151; /* text-gray-700 */
    }
    .choices__list--dropdown {
        border-radius: 0.5rem; /* rounded-lg */
        border: 1px solid #fcd34d; /* border-yellow-300 */
        margin-top: 0.25rem;
    }
    .choices__input {
        background-color: #fff;
        font-size: 1rem;
        padding: 0;
        margin: 0;
    }
    .choices.is-focused .choices__inner {
        border-color: #facc15; /* ring-yellow-400 */
        box-shadow: 0 0 0 2px #facc15; /* ring-2 */
    }
    .choices__list--single {
        padding: 0;
    }
    .choices__item--choice {
        padding: 0.5rem 1rem;
    }
</style>

</head>
<body class="bg-yellow-50 min-h-screen flex">

<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0">
    <div class="flex items-center mb-6">
        <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo">
        <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span>
    </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64">
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-yellow-800 mb-6">Edit Buku</h1>

        <?php 
            // Notifikasi yang konsisten
            if(isset($error)) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$error</p></div>";
            if(isset($success)) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$success</p></div>";
        ?>

        <form method="POST" enctype="multipart/form-data" class="flex flex-col space-y-4 bg-white p-6 rounded-xl shadow">
            
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 mb-1">Judul Buku</label>
                <input type="text" name="judul" id="judul" placeholder="Judul Buku" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700"
                    value="<?php echo htmlspecialchars($buku['judul']); ?>">
            </div>

            <div>
                <label for="penulis" class="block text-sm font-medium text-gray-700 mb-1">Penulis</label>
                <input type="text" name="penulis" id="penulis" placeholder="Penulis" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700"
                    value="<?php echo htmlspecialchars($buku['penulis']); ?>">
            </div>
            
            <div>
                <label for="tahun" class="block text-sm font-medium text-gray-700 mb-1">Tahun Terbit</label>
                <input type="number" name="tahun" id="tahun" placeholder="Tahun Terbit" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700"
                    value="<?php echo htmlspecialchars($buku['tahun']); ?>">
            </div>
            
            <div>
                <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                <select name="kategori_id" id="kategori_select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while($row = $kategori->fetch_assoc()){ ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo ($row['id']==$buku['kategori_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nama']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div>
                <label for="rak" class="block text-sm font-medium text-gray-700 mb-1">Posisi Rak</label>
                <input type="text" name="rak" id="rak" placeholder="Posisi Rak" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700"
                    value="<?php echo htmlspecialchars($buku['rak']); ?>">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Buku</label>
                <select name="status" id="status" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700 bg-white">
                    <option value="Tersedia" <?php echo ($buku['status']=='Tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                    <option value="Dipinjam" <?php echo ($buku['status']=='Dipinjam') ? 'selected' : ''; ?>>Dipinjam</option>
                </select>
            </div>

            <div>
                <label for="gambar" class="block text-sm font-medium text-gray-700 mb-1">Ganti Gambar Sampul</label>
                <?php if($buku['gambar'] && file_exists("../uploads/".$buku['gambar'])): ?>
                    <div class="mb-2">
                        <span class="text-sm text-gray-500">Gambar saat ini:</span>
                        <img src="../uploads/<?php echo $buku['gambar']; ?>" alt="Gambar Buku" class="h-24 w-auto object-cover rounded mt-1">
                    </div>
                <?php endif; ?>
                
                <input type="file" name="gambar" id="gambar" accept="image/*"
                    class="w-full text-gray-700 border border-yellow-300 rounded-lg cursor-pointer bg-white focus:outline-none
                           file:mr-4 file:py-3 file:px-4 file:rounded-l-lg file:border-0
                           file:text-sm file:font-semibold file:bg-yellow-100 file:text-yellow-800 
                           hover:file:bg-yellow-200">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti gambar.</p>
            </div>

            <div class="flex space-x-4 pt-2">
                <a href="buku.php" 
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 rounded-lg font-medium text-center transition duration-200">
                    Kembali
                </a>
                <button type="submit" name="edit" 
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-lg font-medium transition duration-200 shadow">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Choices.js pada <select> dengan id 'kategori_select'
    const kategoriSelect = new Choices('#kategori_select', {
      searchEnabled: true,      // Aktifkan pencarian
      shouldSort: false,      // Gunakan urutan dari server (bukan A-Z)
      removeItemButton: false,  // Tidak perlu tombol hapus
      // Teks placeholder tidak diperlukan karena nilainya sudah dipilih
    });
  });
</script>

</body>
</html>