<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Ambil ID kategori
$id = $_GET['id'] ?? 0;

// Ambil data kategori
$stmt = $conn->prepare("SELECT * FROM kategori WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$kategori = $result->fetch_assoc();

if(!$kategori){
    header("Location: kategori.php");
    exit;
}

// Simpan nama kategori awal untuk ditampilkan di form
$nama_kategori = $kategori['nama'];

// Proses update
if(isset($_POST['update'])){
    $nama = $_POST['nama'];
    if(!empty($nama)){
        $stmt_update = $conn->prepare("UPDATE kategori SET nama=? WHERE id=?");
        $stmt_update->bind_param("si", $nama, $id);
        if($stmt_update->execute()){
            $success = "Kategori berhasil diperbarui!";
            // Perbarui nama di form agar menampilkan nilai baru
            $nama_kategori = $nama; 
        } else {
            $error = "Gagal memperbarui kategori!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Kategori - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 min-h-screen flex">

<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0">
    <div class="flex items-center mb-6">
        <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo">
        <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span>
    </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="kategori.php" class="text-yellow-800 font-medium px-3 py-2 rounded bg-yellow-200">Kelola Kategori</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64">
    <div class="max-w-md mx-auto">
        <h1 class="text-2xl font-bold text-yellow-800 mb-6">Edit Kategori</h1>

        <?php 
            // Pesan notifikasi yang konsisten
            if(isset($error)) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$error</p></div>";
            if(isset($success)) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$success</p></div>";
        ?>

        <form method="POST" class="flex flex-col space-y-4 bg-white p-6 rounded-xl shadow">
            <div>
                <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                <input type="text" name="nama" id="nama" placeholder="Nama Kategori" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700"
                    value="<?php echo htmlspecialchars($nama_kategori); ?>">
            </div>
            
            <div class="flex space-x-4 pt-2">
                <a href="kategori.php" 
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 rounded-lg font-medium text-center transition duration-200">
                    Kembali
                </a>
                <button type="submit" name="update" 
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-lg font-medium transition duration-200 shadow">
                    Update Kategori
                </button>
            </div>
        </form>
    </div>
</main>

</body>
</html>