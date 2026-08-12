<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// =================== Filter & Search ===================
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : '';
$filter_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$perPage = 10; // buku per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// Ambil daftar kategori untuk filter
$kategori_list = $conn->query("SELECT * FROM kategori ORDER BY nama");

// Build query
$where = "1=1";
if($search) $where .= " AND (b.judul LIKE '%".$conn->real_escape_string($search)."%' OR b.penulis LIKE '%".$conn->real_escape_string($search)."%')";
if($filter_tahun) $where .= " AND b.tahun=".$filter_tahun;
if($filter_kategori) $where .= " AND b.kategori_id=".$filter_kategori;

// Hitung total buku
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM buku b WHERE $where");
$totalRow = $totalQuery->fetch_assoc();
$totalBuku = $totalRow['total'];
$totalPage = ceil($totalBuku / $perPage);

// Ambil data buku dengan limit pagination
$buku = $conn->query("
    SELECT b.*, k.nama as kategori_nama 
    FROM buku b 
    LEFT JOIN kategori k ON b.kategori_id = k.id
    WHERE $where
    ORDER BY b.aktif DESC, b.id DESC
    LIMIT $start, $perPage
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Buku - E-Perpus</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Supaya hanya tabel yang bisa di-scroll */
    .tabel-scroll {
        max-height: 70vh;
        overflow-y: auto;
    }
    /* Sticky header tabel */
    table thead tr {
        position: sticky;
        top: 0;
        background-color: #fef08a;
        z-index: 10;
    }
</style>
</head>
<body class="bg-yellow-50 min-h-screen flex">

<!-- Sidebar -->
<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0">
    <div class="flex items-center mb-6">
        <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo">
        <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span>
    </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200 bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<!-- Main Content -->
<main class="flex-grow p-6 ml-64">

    <div class="flex justify-between items-center mb-4 sticky top-0 bg-yellow-50 py-2">
        <h1 class="text-2xl font-bold text-yellow-800">Daftar Buku</h1>
        <div class="flex space-x-2">
            <a href="tambah_buku.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Tambah Buku
            </a>
            <a href="kategori.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Tambah Kategori Buku
            </a>
        </div>
    </div>

    <!-- Filter & Search -->
    <form method="GET" class="flex flex-col md:flex-row md:items-center md:space-x-4 mb-4 sticky top-14 bg-yellow-50 py-2">
        <input type="text" name="search" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search); ?>" 
               class="flex-grow px-4 py-2 border border-yellow-300 rounded focus:outline-none focus:ring-2 focus:ring-yellow-400 mb-2 md:mb-0">
        <select name="tahun" class="px-3 py-2 border border-yellow-300 rounded mb-2 md:mb-0">
            <option value="">-- Tahun --</option>
            <?php for($y=1999;$y<=2025;$y++): ?>
                <option value="<?php echo $y; ?>" <?php if($filter_tahun==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
        </select>
        <select name="kategori" class="px-3 py-2 border border-yellow-300 rounded mb-2 md:mb-0">
            <option value="">-- Kategori --</option>
            <?php while($k=$kategori_list->fetch_assoc()): ?>
                <option value="<?php echo $k['id']; ?>" <?php if($filter_kategori==$k['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($k['nama']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Filter</button>
    </form>

    <!-- Scrollable Table -->
    <div class="tabel-scroll bg-white rounded-lg shadow">
        <table class="w-full border-collapse">
            <thead class="bg-yellow-200 text-yellow-800">
                <tr>
                    <th class="p-2">Gambar</th>
                    <th class="p-2">Judul</th>
                    <th class="p-2">Penulis</th>
                    <th class="p-2">Tahun</th>
                    <th class="p-2">Kategori</th>
                    <th class="p-2">Rak</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if($buku->num_rows==0): ?>
                    <tr>
                        <td colspan="8" class="text-center p-4">Belum ada buku yang sesuai filter.</td>
                    </tr>
                <?php else: ?>
                    <?php while($row=$buku->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-yellow-50">
                            <td class="p-2 text-center">
                                <?php if($row['gambar'] && file_exists("../uploads/".$row['gambar'])): ?>
                                    <img src="../uploads/<?php echo $row['gambar']; ?>" class="h-16 w-16 object-cover rounded mx-auto">
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2"><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($row['penulis']); ?></td>
                            <td class="p-2 text-center"><?php echo htmlspecialchars($row['tahun']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($row['kategori_nama'] ?? '-'); ?></td>
                            <td class="p-2 text-center"><?php echo htmlspecialchars($row['rak'] ?? '-'); ?></td>
                            <td class="p-2 font-semibold text-center
                                <?php 
                                    if($row['aktif']==0) echo 'text-gray-400'; 
                                    elseif($row['status']=='Tersedia') echo 'text-green-700'; 
                                    else echo 'text-red-700';
                                ?>">
                                <?php 
                                    if($row['aktif']==0) echo 'Nonaktif'; 
                                    else echo $row['status']; 
                                ?>
                            </td>
                            <td class="p-2 text-center space-x-2">
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline">Edit</a>
                                <?php if($row['aktif']==1 && $row['status']=='Tersedia'): ?>
                                    <button data-href="nonaktifkan_buku.php?id=<?php echo $row['id']; ?>" class="text-red-500 hover:underline hapusBtn">Nonaktifkan</button>
                                <?php elseif($row['aktif']==0): ?>
                                    <a href="aktifkan_buku.php?id=<?php echo $row['id']; ?>" class="text-green-500 hover:underline">Aktifkan</a>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Sedang Dipinjam</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center space-x-2">
        <?php for($p=1;$p<=$totalPage;$p++): ?>
            <a href="?<?php 
                $params = $_GET;
                $params['page']=$p;
                echo http_build_query($params);
            ?>" class="px-3 py-1 rounded 
                <?php echo ($p==$page)?'bg-yellow-500 text-white':'bg-yellow-200 text-yellow-800 hover:bg-yellow-300'; ?>">
                <?php echo $p; ?>
            </a>
        <?php endfor; ?>
    </div>

</main>

<!-- Modal Konfirmasi Nonaktifkan -->
<div id="modalHapus" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full text-center">
        <p class="mb-4 text-gray-800 font-medium">Yakin ingin menonaktifkan buku ini?</p>
        <div class="flex justify-center space-x-4">
            <button id="batalHapus" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Batal</button>
            <a href="#" id="konfirmasiHapus" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Nonaktifkan</a>
        </div>
    </div>
</div>

<script>
// Modal konfirmasi Nonaktifkan
const hapusBtns = document.querySelectorAll('.hapusBtn');
const modal = document.getElementById('modalHapus');
const konfirmasiBtn = document.getElementById('konfirmasiHapus');
const batalBtn = document.getElementById('batalHapus');

hapusBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        modal.classList.remove('hidden');
        konfirmasiBtn.href = btn.getAttribute('data-href');
    });
});

batalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
});

modal.addEventListener('click', (e) => {
    if(e.target === modal){
        modal.classList.add('hidden');
    }
});
</script>

</body>
</html>
