<?php
session_start();
include "../config.php";

// Error reporting untuk debugging (hapus atau beri komentar saat production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fungsi ketika buku dikembalikan
if (isset($_POST['kembalikan'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    // Validasi $peminjaman_id
    if (!filter_var($peminjaman_id, FILTER_VALIDATE_INT)) {
         // Handle error, mungkin redirect atau tampilkan pesan
         die("ID Peminjaman tidak valid.");
    }

    // Ambil detail peminjaman
    $stmt_get = $conn->prepare("SELECT * FROM peminjaman WHERE id=?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $peminjaman_id);
        $stmt_get->execute();
        $res = $stmt_get->get_result();
        $row = $res->fetch_assoc();
        $stmt_get->close();

        if ($row) {
            $buku_id = $row['buku_id'];
            // Pastikan tanggal valid
            if ($row['tanggal_pinjam'] && $row['tanggal_pinjam'] != '0000-00-00') {
                 try {
                     $tanggal_pinjam = new DateTime($row['tanggal_pinjam']);
                     $tanggal_kembali = new DateTime(); // tanggal saat ini
                     $batas = (clone $tanggal_pinjam)->modify('+7 days');

                     // Hitung keterlambatan
                     $status_pengembalian = "Tepat Waktu";
                     $status_denda = "Tidak Ada";
                     $total_denda = 0;
                     $denda_per_hari = 1000;

                     if ($tanggal_kembali > $batas) {
                         $selisih = $batas->diff($tanggal_kembali)->days;
                         $total_denda = $selisih * $denda_per_hari;
                         $status_pengembalian = "Terlambat";
                         $status_denda = "Ada Denda";
                     }

                     // Update tabel peminjaman
                     $update = $conn->prepare("UPDATE peminjaman
                        SET status='Kembali', tanggal_kembali=?, status_pengembalian=?,
                            total_denda=?, status_denda=?
                        WHERE id=?");
                     if ($update) {
                         $tgl_kembali_str = $tanggal_kembali->format('Y-m-d');
                         $update->bind_param("ssisi", $tgl_kembali_str, $status_pengembalian, $total_denda, $status_denda, $peminjaman_id);
                         $update->execute();
                         $update->close();

                         // Update status buku kembali ke tersedia
                         $stmt_buku = $conn->prepare("UPDATE buku SET status='Tersedia' WHERE id=?");
                         if ($stmt_buku) {
                             $stmt_buku->bind_param("i", $buku_id);
                             $stmt_buku->execute();
                             $stmt_buku->close();
                         } else { error_log("Gagal prepare update buku: " . $conn->error); }

                     } else { error_log("Gagal prepare update peminjaman: " . $conn->error); }

                 } catch (Exception $e) {
                     error_log("Error DateTime saat kembalikan: " . $e->getMessage());
                 }
            } else { error_log("Tanggal pinjam tidak valid untuk ID: " . $peminjaman_id); }
        } else { error_log("Data peminjaman tidak ditemukan untuk ID: " . $peminjaman_id); }
    } else { error_log("Gagal prepare get peminjaman: " . $conn->error); }

    // Redirect kembali (hapus parameter 'kembalikan' jika ada)
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?'); // URL dasar
    $query_params = $_GET;
    unset($query_params['kembalikan']); // Hapus parameter POST
    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }
    header("Location: " . $redirect_url);
    exit;
}

// --- LOGIKA FILTER & PENCARIAN (DIPERBARUI) ---
$peminjaman_id_filter = isset($_GET['peminjaman_id']) ? (int)$_GET['peminjaman_id'] : 0;
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? ''; // Untuk filter 'terlambat' dari dashboard

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page - 1) * $perPage;

$params = [];
$param_types = "";
$where_clauses = ["p.status='Dipinjam'"]; // Default: hanya tampilkan yang 'Dipinjam'
$limit_sql = "LIMIT ?, ?";
$pagination_params = [$start, $perPage]; // Parameter untuk LIMIT
$show_search_form = true; // Default: tampilkan form search

// Prioritaskan filter by ID Peminjaman
if ($peminjaman_id_filter > 0) {
    $where_clauses = ["p.id = ?"]; // Ganti kondisi WHERE utama
    $params = [$peminjaman_id_filter];
    $param_types = "i";
    $limit_sql = ""; // Tidak perlu LIMIT jika hanya satu ID
    $pagination_params = []; // Tidak perlu parameter pagination
    $show_search_form = false; // Sembunyikan form search
    $totalPage = 1; // Hanya ada 1 halaman/data
    $totalData = 1; // Diasumsikan 1 data jika ID ditemukan
} else {
    // Jika tidak ada ID, gunakan filter 'terlambat' atau 'search'
    if ($filter === 'terlambat') {
        $today = date('Y-m-d');
        // Pastikan tanggal pinjam valid sebelum DATE_ADD
        $where_clauses[] = "p.tanggal_pinjam IS NOT NULL AND p.tanggal_pinjam > '0000-00-00' AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < ?";
        $params[] = $today;
        $param_types .= "s";
    }

    if ($search) {
        $where_clauses[] = "(p.user_nis LIKE ? OR p.user_name LIKE ? OR p.user_class LIKE ? OR b.judul LIKE ?)";
        $like = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        $param_types .= "ssss";
    }

    // Hitung Total Data (hanya jika tidak filter by ID)
    $count_sql = "SELECT COUNT(*) as total
                  FROM peminjaman p
                  LEFT JOIN buku b ON p.buku_id=b.id
                  WHERE " . implode(" AND ", $where_clauses); // Gabungkan klausa

    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        if (!empty($params)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        $count_stmt->execute();
        $totalRow = $count_stmt->get_result()->fetch_assoc();
        $totalData = $totalRow['total'];
        $totalPage = ceil($totalData / $perPage);
        $count_stmt->close();
    } else {
        error_log("Gagal prepare count query: " . $conn->error);
        $totalData = 0;
        $totalPage = 0;
    }
}

// Gabungkan klausa WHERE
$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Ambil Data Peminjaman
$sql = "SELECT p.*, b.judul
        FROM peminjaman p
        LEFT JOIN buku b ON p.buku_id=b.id
        $where_sql
        ORDER BY p.tanggal_pinjam DESC
        $limit_sql"; // Tambah LIMIT jika perlu

// Gabungkan parameter WHERE dengan parameter LIMIT (jika ada)
$final_params = array_merge($params, $pagination_params);
$final_param_types = $param_types . (empty($pagination_params) ? "" : "ii"); // Tambah 'ii' jika ada LIMIT

$stmt_get_data = $conn->prepare($sql);
$peminjaman = null; // Inisialisasi

if ($stmt_get_data) {
    if (!empty($final_params)) {
        $stmt_get_data->bind_param($final_param_types, ...$final_params);
    }
    $stmt_get_data->execute();
    $peminjaman = $stmt_get_data->get_result();
     // Cek jika filter by ID tapi tidak ada hasil, mungkin ID salah
     if ($peminjaman_id_filter > 0 && $peminjaman->num_rows === 0) {
         $totalData = 0; // Set total data jadi 0
     }
    $stmt_get_data->close();
} else {
    error_log("Gagal prepare get data peminjaman: " . $conn->error);
}

$today_dt = new DateTime(); // Digunakan di loop tabel
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Peminjaman - E-Perpus</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .tabel-scroll {
        /* Tentukan tinggi maksimal tabel, sisakan ruang untuk pagination */
        max-height: calc(100vh - 280px); /* 100vh dikurangi ~tinggi header/filter/padding */
        overflow-y: auto;
    }
    /* Sticky header tabel */
    .tabel-scroll table thead tr {
        position: sticky;
        top: 0;
        /* Pastikan background sama dengan class thead */
        background-color: #fef08a; /* bg-yellow-200 */
        z-index: 10;
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
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64 flex flex-col h-screen">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-yellow-800">Daftar Peminjaman Buku Siswa</h1>
        <a href="tambah_peminjam.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
            + Tambah Peminjam
        </a>
    </div>

    <form method="GET" class="mb-4 flex space-x-2">
        <input type="text" name="search" placeholder="Cari Nama, NIS, Kelas, atau Buku..." 
               value="<?php echo htmlspecialchars($search ?? ''); ?>" 
               class="flex-grow px-4 py-2 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">Cari</button>
        <a href="peminjaman.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">Reset</a>
    </form>

    <div class="tabel-scroll overflow-x-auto bg-white rounded-lg shadow flex-grow">
        <table class="w-full text-left border-collapse min-w-max"> <thead class="bg-yellow-200 text-yellow-800">
                <tr>
                    <th class="p-2 border-b">NIS</th>
                    <th class="p-2 border-b">Nama</th>
                    <th class="p-2 border-b">Kelas</th>
                    <th class="p-2 border-b">Buku</th>
                    <th class="p-2 border-b">Tanggal Pinjam</th>
                    <th class="p-2 border-b">Batas Kembali</th>
                    <th class="p-2 border-b">Keterlambatan (hari)</th>
                    <th class="p-2 border-b">Denda (Rp)</th>
                    <th class="p-2 border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($peminjaman->num_rows == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center p-4 text-gray-500">
                            <?php echo $search ? 'Data tidak ditemukan.' : 'Belum ada peminjaman aktif.'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $peminjaman->fetch_assoc()): ?>
                        <?php 
                            $tanggal_pinjam = new DateTime($row['tanggal_pinjam']);
                            $batas_kembali = (clone $tanggal_pinjam)->modify('+7 days');
                            $hari_ini = new DateTime();
                            
                            // Perhitungan keterlambatan yang lebih akurat
                            $terlambat_hari = 0;
                            if ($hari_ini > $batas_kembali) {
                                $selisih = $batas_kembali->diff($hari_ini);
                                $terlambat_hari = $selisih->days;
                            }

                            $denda = $terlambat_hari * 1000;
                            $warna = $terlambat_hari > 0 ? 'text-red-600 font-semibold' : 'text-green-600';
                        ?>
                        <tr class="border-b hover:bg-yellow-50">
                            <td class="p-2"><?php echo htmlspecialchars($row['user_nis']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($row['user_class']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td class="p-2"><?php echo $row['tanggal_pinjam']; ?></td>
                            <td class="p-2 <?php echo $warna; ?>"><?php echo $batas_kembali->format('Y-m-d'); ?></td>
                            <td class="p-2 text-center <?php echo $warna; ?>"><?php echo $terlambat_hari > 0 ? $terlambat_hari : '-'; ?></td>
                            <td class="p-2 text-center <?php echo $warna; ?>"><?php echo $denda > 0 ? number_format($denda, 0, ',', '.') : '-'; ?></td>
                            <td class="p-2 text-center">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <input type="hidden" name="peminjaman_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="kembalikan"
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded"
                                        onclick="return confirm('Yakin ingin mengembalikan buku ini?');">
                                        Kembalikan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-center space-x-2">
        <?php 
        // Hanya tampilkan pagination jika total halaman > 1
        if ($totalPage > 1):
            for($p=1; $p<=$totalPage; $p++): 
                $queryParams = $_GET; // Ambil semua parameter GET saat ini (termasuk 'search')
                $queryParams['page'] = $p; // Set/ganti parameter 'page'
        ?>
            <a href="?<?php echo http_build_query($queryParams); ?>" 
               class="px-3 py-1 rounded 
                      <?php echo ($p == $page) ? 'bg-yellow-500 text-white' : 'bg-yellow-200 text-yellow-800 hover:bg-yellow-300'; ?>">
                <?php echo $p; ?>
            </a>
        <?php 
            endfor; 
        endif;
        ?>
    </div>

</main>
</body>
</html>