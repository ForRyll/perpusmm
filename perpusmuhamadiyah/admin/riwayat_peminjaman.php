<?php
session_start();
include "../config.php";

// Matikan strict mode
$conn->query("SET sql_mode=''");

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// --- LOGIKA FILTER ---
$peminjaman_id = isset($_GET['peminjaman_id']) ? (int)$_GET['peminjaman_id'] : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$show_filter_form = true;
$today_dt = new DateTime(); 

if ($peminjaman_id > 0) {
    // Mode Detail: Tampilkan 1 peminjaman spesifik
    $stmt = $conn->prepare("
        SELECT p.*, b.judul 
        FROM peminjaman p
        LEFT JOIN buku b ON p.buku_id = b.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $peminjaman_id);
    $stmt->execute();
    $riwayat = $stmt->get_result();
    $show_filter_form = false; 
} else {
    // Mode List: Filter Bulan/Tahun + Keyword
    // Logika Query: 
    // 1. Pinjam di bulan ini OR
    // 2. Kembali di bulan ini OR
    // 3. Masih dipinjam (sejak bulan lalu/sekarang) agar status telat terlihat
    
    $query = "
        SELECT p.*, b.judul 
        FROM peminjaman p
        LEFT JOIN buku b ON p.buku_id = b.id
        WHERE 
        p.tanggal_pinjam IS NOT NULL AND p.tanggal_pinjam > '0000-00-00' AND
        (
            (MONTH(p.tanggal_pinjam) = ? AND YEAR(p.tanggal_pinjam) = ?) 
            OR 
            (MONTH(p.tanggal_kembali) = ? AND YEAR(p.tanggal_kembali) = ?)
            OR
            (p.status = 'Dipinjam' AND (YEAR(p.tanggal_pinjam) < ? OR (YEAR(p.tanggal_pinjam) = ? AND MONTH(p.tanggal_pinjam) <= ?)))
        )
    ";
    
    $params = [
        $filter_bulan, $filter_tahun, 
        $filter_bulan, $filter_tahun,
        $filter_tahun, $filter_tahun, $filter_bulan
    ];
    $types = "iiiiiii";

    if($keyword !== ''){
        $query .= " AND (p.user_name LIKE ? OR p.user_nis LIKE ? OR b.judul LIKE ?)";
        $like_keyword = "%$keyword%";
        $params[] = $like_keyword;
        $params[] = $like_keyword;
        $params[] = $like_keyword;
        $types .= "sss";
    }

    $query .= " ORDER BY p.tanggal_pinjam DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $riwayat = $stmt->get_result();
}

$denda_per_hari = 1000;
$total_denda_bulan = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Peminjaman - E-Perpus</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 min-h-screen flex">

<aside class="bg-yellow-100 w-64 fixed top-0 left-0 h-full p-6 flex flex-col shadow-lg">
    <div class="flex items-center mb-6">
        <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo">
        <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span>
    </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow ml-64 flex flex-col h-screen">
    <div class="flex justify-between items-center p-6 pb-0">
        <h1 class="text-2xl font-bold text-yellow-800">
            <?php echo ($peminjaman_id > 0) ? 'Detail Peminjaman' : 'Riwayat Peminjaman'; ?>
        </h1>
        <a href="dashboard.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">← Kembali ke Dashboard</a>
    </div>

    <?php if ($show_filter_form): ?>
    <form method="GET" class="flex flex-col md:flex-row md:items-center justify-between bg-white m-6 mt-4 p-4 rounded-lg shadow gap-4">
        <div class="flex items-center gap-2">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama, NIS, atau judul..." class="border border-yellow-300 rounded px-3 py-2 w-64 focus:ring-2 focus:ring-yellow-400 focus:outline-none">
            <select name="bulan" class="border border-yellow-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:outline-none bg-white">
                <?php for($m=1; $m<=12; $m++): $selected = ($filter_bulan == $m) ? 'selected' : ''; echo "<option value='$m' $selected>".date('F', mktime(0,0,0,$m,1))."</option>"; endfor; ?>
            </select>
            <select name="tahun" class="border border-yellow-300 rounded px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:outline-none bg-white">
                <?php $thn = date('Y'); for($t=$thn-3; $t<=$thn+1; $t++): $selected = ($filter_tahun == $t) ? 'selected' : ''; echo "<option value='$t' $selected>$t</option>"; endfor; ?>
            </select>
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Filter</button>
        </div>
        <a href="riwayat_peminjaman.php" class="text-yellow-700 hover:underline">Reset Filter</a>
    </form>
    <?php endif; ?>

    <div class="flex-grow overflow-auto mx-6 bg-white rounded-lg shadow <?php echo !$show_filter_form ? 'mt-6' : ''; ?>">
        <table class="w-full min-w-max">
            <thead class="bg-yellow-200 text-yellow-800 sticky top-0">
                <tr>
                    <th class="p-2 text-left">Nama Siswa</th>
                    <th class="p-2 text-left">NIS</th>
                    <th class="p-2 text-left">Judul Buku</th>
                    <th class="p-2 text-center">Tanggal Pinjam</th>
                    <th class="p-2 text-center">Tanggal Kembali</th>
                    <th class="p-2 text-center">Status</th>
                    <th class="p-2 text-center">Denda (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($riwayat->num_rows == 0): ?>
                    <tr><td colspan="7" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php while($r = $riwayat->fetch_assoc()): ?>
                        <?php
                            $tgl_pinjam_str = $r['tanggal_pinjam'];
                            $tgl_kembali_str = $r['tanggal_kembali'];
                            $status_db = $r['status'];
                            
                            $status_tampil = "";
                            $class_status = "";
                            $denda_hitung = 0;
                            $tgl_kembali_tampil = "-";

                            if ($tgl_pinjam_str && $tgl_pinjam_str > '0000-00-00') {
                                try {
                                    $tgl_pinjam_obj = new DateTime($tgl_pinjam_str);
                                    $batas_waktu = (clone $tgl_pinjam_obj)->modify('+7 days');

                                    if ($status_db == 'Dipinjam') {
                                        if ($today_dt > $batas_waktu) {
                                            $selisih = $batas_waktu->diff($today_dt)->days;
                                            $denda_hitung = $selisih * 1000;
                                            $status_tampil = "Belum Kembali (Telat $selisih hari)";
                                            $class_status = "text-red-600 font-bold";
                                        } else {
                                            $status_tampil = "Sedang Dipinjam";
                                            $class_status = "text-blue-600 font-semibold";
                                        }
                                        $tgl_kembali_tampil = '<span class="text-gray-400 italic">Belum</span>';

                                    } elseif ($status_db == 'Kembali') {
                                        if ($tgl_kembali_str && $tgl_kembali_str > '0000-00-00') {
                                            $tgl_kembali_obj = new DateTime($tgl_kembali_str);
                                            $tgl_kembali_tampil = htmlspecialchars($tgl_kembali_str);

                                            if ($tgl_kembali_obj > $batas_waktu) {
                                                // Gunakan denda dari DB jika ada, fallback ke hitungan
                                                $denda_hitung = ($r['total_denda'] > 0) ? $r['total_denda'] : ($batas_waktu->diff($tgl_kembali_obj)->days * 1000);
                                                $status_tampil = "Dikembalikan (Telat)";
                                                $class_status = "text-orange-600 font-semibold";
                                            } else {
                                                $status_tampil = "Tepat Waktu";
                                                $class_status = "text-green-600 font-bold";
                                            }
                                        } else {
                                            $status_tampil = "Dikembalikan (?)";
                                            $class_status = "text-gray-500";
                                        }
                                    }
                                } catch (Exception $e) { $status_tampil = "Error"; }
                            } else { $status_tampil = "Invalid"; }

                            if ($show_filter_form) { $total_denda_bulan += $denda_hitung; }
                        ?>
                        <tr class="border-b hover:bg-yellow-50">
                            <td class="p-2"><?= htmlspecialchars($r['user_name']); ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['user_nis']); ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['judul']); ?></td>
                            <td class="p-2 text-center"><?= htmlspecialchars($tgl_pinjam_str); ?></td>
                            <td class="p-2 text-center"><?= $tgl_kembali_tampil; ?></td>
                            <td class="p-2 text-center <?= $class_status; ?>"><?= $status_tampil; ?></td>
                            <td class="p-2 text-center font-mono">
                                <?= $denda_hitung > 0 ? "Rp " . number_format($denda_hitung, 0, ',', '.') : '-' ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($show_filter_form): ?>
    <div class="bg-white m-6 mt-4 p-4 rounded-lg shadow flex justify-between items-center">
        <?php
            // LOGIKA NAVIGASI BULAN YANG BENAR
            $prev_month = $filter_bulan - 1; 
            $next_month = $filter_bulan + 1;
            $prev_year = $filter_tahun; 
            $next_year = $filter_tahun;

            if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
            if ($next_month > 12) { $next_month = 1; $next_year++; }

            // Pertahankan keyword pencarian jika ada
            $nav_params = []; 
            if ($keyword !== '') { $nav_params['keyword'] = $keyword; }
        ?>
        <div class="flex items-center gap-4">
            <?php 
                $prev_params = array_merge($nav_params, ['bulan' => $prev_month, 'tahun' => $prev_year]); 
                // Gunakan http_build_query agar URL aman
                $prev_url = "?" . http_build_query($prev_params);
            ?>
            <a href="<?= $prev_url ?>" class="text-yellow-800 font-semibold hover:text-yellow-600 bg-yellow-100 px-3 py-1 rounded">&lt; Sebelumnya</a>
            
            <span class="text-yellow-800 font-bold text-lg w-40 text-center">
                <?= date('F Y', mktime(0,0,0,$filter_bulan,1,$filter_tahun)) ?>
            </span>
            
            <?php 
                $next_params = array_merge($nav_params, ['bulan' => $next_month, 'tahun' => $next_year]); 
                $next_url = "?" . http_build_query($next_params);
            ?>
            <a href="<?= $next_url ?>" class="text-yellow-800 font-semibold hover:text-yellow-600 bg-yellow-100 px-3 py-1 rounded">Berikutnya &gt;</a>
        </div>
        <div class="text-yellow-800 font-bold text-lg">
            Estimasi Denda: Rp <?= number_format($total_denda_bulan, 0, ',', '.') ?>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>