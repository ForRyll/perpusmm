<?php
session_start();
include "../config.php";

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// === RESET OTOMATIS DI AWAL BULAN ===
// ... (Kode reset Anda tidak berubah) ...
$bulanSekarang = date('m');
$tahunSekarang = date('Y');
if (!isset($_SESSION['last_reset_month']) || $_SESSION['last_reset_month'] != $bulanSekarang) {
    $conn->query("UPDATE peminjaman SET status_denda='reset' WHERE YEAR(tanggal_pinjam)=$tahunSekarang AND MONTH(tanggal_pinjam) < $bulanSekarang");
    $_SESSION['last_reset_month'] = $bulanSekarang;
}


// --- STATISTIK ---
// Query 1: Statistik Total (Sudah Bersih)
$totalBuku = $conn->query("SELECT COUNT(*) as total FROM buku")->fetch_assoc()['total'];
$tersedia = $conn->query("SELECT COUNT(*) as total FROM buku WHERE status='Tersedia'")->fetch_assoc()['total'];
$dipinjam = $conn->query("SELECT COUNT(*) as total FROM buku WHERE status='Dipinjam'")->fetch_assoc()['total'];
$totalPeminjaman = $conn->query("SELECT COUNT(*) as total FROM peminjaman")->fetch_assoc()['total'];

// Query 2: Statistik Keterlambatan Total (Sudah Bersih)
$today = date('Y-m-d');
$dendaQuery = $conn->query("
    SELECT 
        CASE 
            WHEN p.status = 'Dipinjam' THEN DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY))
            ELSE DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY))
        END AS terlambat_hari,
        CASE 
            WHEN p.status = 'Dipinjam' AND DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            WHEN p.status = 'Kembali' AND DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            ELSE 0
        END AS denda
    FROM peminjaman p
    WHERE 
        (p.status = 'Dipinjam' AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today')
        OR
        (p.status = 'Kembali' AND p.tanggal_kembali > DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY))
");

$totalDenda = 0;
while($row = $dendaQuery->fetch_assoc()){
    if($row['terlambat_hari'] > 0){
        $totalDenda += $row['denda'];
    }
}

// Query 3: Notifikasi KETERLAMBATAN BARU (Sudah Bersih)
$notifQuery = $conn->query("
    SELECT p.id as peminjaman_id, p.user_nis, p.user_name, b.judul,
           DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) as terlambat_hari,
           (DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000) as denda
    FROM peminjaman p
    LEFT JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'Dipinjam'
    AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today'
    AND p.notif_seen = 0
    ORDER BY p.tanggal_pinjam ASC
");
$notifList = $notifQuery->fetch_all(MYSQLI_ASSOC);
$jumlahNotifBaru = count($notifList);

// Query 5 (BARU): Daftar SEMUA siswa yang SAAT INI terlambat (Untuk MODAL)
$siswaTelatQuery = $conn->query("
    SELECT p.id as peminjaman_id, p.user_nis, p.user_name, b.judul,
           DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) as terlambat_hari
    FROM peminjaman p
    LEFT JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'Dipinjam'
    AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today'
    ORDER BY p.tanggal_pinjam ASC
");
$siswaTelatList = $siswaTelatQuery->fetch_all(MYSQLI_ASSOC);


// Query 6: Laporan bulanan (Sudah Bersih)
// ... (Kode Laporan Bulanan Anda tidak berubah) ...
$currentYear = date('Y');
$laporanBulanQuery = $conn->query("
    SELECT 
        MONTH(p.tanggal_pinjam) AS bulan,
        COUNT(*) AS total_pinjam,
        SUM(CASE WHEN p.tanggal_kembali IS NOT NULL 
                AND p.tanggal_kembali <= DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)
             THEN 1 ELSE 0 END) AS tepat_waktu,
        SUM(CASE WHEN p.tanggal_kembali IS NOT NULL 
                AND p.tanggal_kembali > DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)
             THEN 1 ELSE 0 END) AS terlambat
    FROM peminjaman p
    WHERE YEAR(p.tanggal_pinjam)=YEAR(CURDATE())
    GROUP BY MONTH(p.tanggal_pinjam)
    ORDER BY bulan ASC
");

// ... (Kode Inisialisasi Array 12 Bulan Anda tidak berubah) ...
$bulan = [];
$totalPinjam = array_fill(0, 12, 0);
$tepatWaktu = array_fill(0, 12, 0);
$terlambat = array_fill(0, 12, 0);
$tahunGrafik = date('Y');
$namaBulanIndonesia = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
for ($i = 1; $i <= 12; $i++) {
    $bulan[] = $namaBulanIndonesia[$i] . " " . $tahunGrafik;
}
while($row = $laporanBulanQuery->fetch_assoc()){
    $idx = (int)$row['bulan'] - 1; 
    if(isset($totalPinjam[$idx])) {
        $totalPinjam[$idx] = (int)$row['total_pinjam'];
        $tepatWaktu[$idx] = (int)$row['tepat_waktu'];
        $terlambat[$idx] = (int)$row['terlambat'];
    }
}


// [BARU] Query 7: Detail keterlambatan untuk PDF
// ... (Kode Query 7 Anda tidak berubah) ...
$currentMonth = date('m');
$initialLateDetailsQuery = $conn->query("
    SELECT 
        p.user_nis, 
        p.user_name, 
        p.tanggal_pinjam, 
        p.tanggal_kembali,
        CASE 
            WHEN p.status = 'Dipinjam' AND DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF('$today', DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            WHEN p.status = 'Kembali' AND DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) > 0 
                THEN (DATEDIFF(p.tanggal_kembali, DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY)) * 1000)
            ELSE 0
        END AS denda
    FROM peminjaman p
    WHERE 
        YEAR(p.tanggal_pinjam) = $currentYear
        AND MONTH(p.tanggal_pinjam) = $currentMonth
        AND (
            (p.status = 'Dipinjam' AND DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY) < '$today')
            OR
            (p.status = 'Kembali' AND p.tanggal_kembali > DATE_ADD(p.tanggal_pinjam, INTERVAL 7 DAY))
        )
    ORDER BY p.tanggal_pinjam ASC
");
$initialLateStudentDetails = $initialLateDetailsQuery->fetch_all(MYSQLI_ASSOC);
$jumlahSiswaTelatBulanIni = count($initialLateStudentDetails);


// ===========================================
// === [BARU] QUERY PESAN MASUK ===
// ===========================================
// [BARU] Query 8: Ambil JML PESAN BARU (belum dibaca)
$pesanBaruQuery = $conn->query("SELECT COUNT(*) as total FROM kontak_pesan WHERE sudah_dibaca = 0");
$jumlahPesanBaru = $pesanBaruQuery->fetch_assoc()['total'];

// [BARU] Query 9: Ambil SEMUA Pesan (untuk modal)
$semuaPesanQuery = $conn->query("SELECT * FROM kontak_pesan ORDER BY tanggal_kirim DESC");
$semuaPesanList = $semuaPesanQuery->fetch_all(MYSQLI_ASSOC);
// ===========================================

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - E-Perpus</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<style>
    /* ... (CSS tidak berubah) ... */
    #toastNotif { transition: opacity 0.3s, transform 0.3s, visibility 0.3s; opacity: 0; transform: translateY(20px); visibility: hidden; }
    #toastNotif.show { opacity: 1; transform: translateY(0); visibility: visible; }
    .modal { transition: opacity 0.3s ease; }
    .modal-content { transition: transform 0.3s ease; }
</style>
</head>
<body class="bg-yellow-50 min-h-screen flex">

<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0 shadow-lg">
    <div class="flex items-center mb-6">
        <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo">
        <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span>
    </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64 relative overflow-y-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-yellow-800 mb-1">Dashboard Admin</h1>
            <div class="flex items-center space-x-2 text-yellow-700 font-semibold">
                <button id="prevMonth" class="p-2 rounded-full hover:bg-yellow-200">&lt;</button>
                <span id="currentMonth" class="text-lg font-bold"></span>
                <button id="nextMonth" class="p-2 rounded-full hover:bg-yellow-200">&gt;</button>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <a id="downloadButton" href="#" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg shadow text-sm font-semibold flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Download Recap</span>
            </a>
            <a href="riwayat_peminjaman.php" 
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                📘 Riwayat Peminjaman
            </a>

            <button id="pesanBell" class="relative cursor-pointer p-2">
                <img src="../images/surat.png" alt="Pesan" class="w-8 h-8"> 
                <?php if($jumlahPesanBaru > 0): ?>
                    <span id="pesanDot" class="absolute top-1 right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?php echo $jumlahPesanBaru; ?>
                    </span>
                <?php endif; ?>
            </button>

            <button id="notifBell" class="relative cursor-pointer p-2">
                <img src="../images/notifikasi.png" alt="Notif" class="w-8 h-8">
                <?php if($jumlahNotifBaru > 0): ?>
                    <span id="notifDot" class="absolute top-1 right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?php echo $jumlahNotifBaru; ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-6">
        <div class="bg-white shadow-md rounded-xl p-4 text-center">
            <h2 class="font-bold text-gray-600 text-sm">Total Buku</h2>
            <p id="statTotalBuku" class="text-yellow-800 text-3xl font-bold mt-2"><?php echo $totalBuku; ?></p>
        </div>
        <div class="bg-white shadow-md rounded-xl p-4 text-center">
            <h2 class="font-bold text-gray-600 text-sm">Tersedia</h2>
            <p id="statTersedia" class="text-green-600 text-3xl font-bold mt-2"><?php echo $tersedia; ?></p>
        </div>
        <a href="peminjaman.php" class="block bg-white shadow-md rounded-xl p-4 text-center hover:shadow-lg transition-shadow duration-200">
            <h2 class="font-bold text-gray-600 text-sm">Dipinjam</h2>
            <p id="statDipinjam" class="text-red-600 text-3xl font-bold mt-2"><?php echo $dipinjam; ?></p>
        </a>
        <div class="bg-white shadow-md rounded-xl p-4 text-center">
            <h2 class="font-bold text-gray-600 text-sm">Total Peminjaman</h2>
            <p id="statTotalPeminjaman" class="text-yellow-800 text-3xl font-bold mt-2"><?php echo $totalPeminjaman; ?></p>
        </div>
        <button type="button" onclick="openSiswaTelatModal()" class="bg-white shadow-md rounded-xl p-4 text-center hover:shadow-lg transition-shadow duration-200">
            <h2 class="font-bold text-gray-600 text-sm">Siswa Telat</h2>
            <p id="statJumlahTelat" class="text-red-600 text-3xl font-bold mt-2"><?php echo $jumlahSiswaTelatBulanIni; ?></p>
        </button>
        <div class="bg-white shadow-md rounded-xl p-4 text-center">
            <h2 class="font-bold text-gray-600 text-sm">Total Denda</h2>
            <p id="statTotalDenda" class="text-red-600 text-3xl font-bold mt-2">Rp <?php echo number_format($totalDenda,0,',','.'); ?></p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md mb-6">
        <h2 class="text-xl font-bold text-yellow-800 mb-4 text-center">Tren Peminjaman Bulanan</h2>
        <div class="w-full" style="height:350px;">
            <canvas id="laporanChart"></canvas>
        </div>
    </div>
    
    <div id="notifDropdown" class="hidden bg-white border rounded-lg shadow-lg absolute right-6 top-20 w-80 max-h-96 overflow-y-auto z-50">
        <div class="p-3 font-bold text-yellow-800 border-b">Notifikasi Keterlambatan</div>
        <div id="notifDropdownList">
            </div>
    </div>
</main>

<div id="toastNotif" class="fixed bottom-6 right-6 bg-white shadow-lg rounded-lg p-4 max-w-sm border-l-4 border-red-500">
    <button onclick="hideToast(event)" class="absolute top-1 right-2 text-gray-400 hover:text-gray-700 font-bold">&times;</button>
    <div class="flex items-center cursor-pointer" onclick="openNotif()">
        <div class="flex-shrink-0">
            <img src="../images/notifikasi.png" alt="Notif" class="w-8 h-8">
        </div>
        <div class="ml-3">
            <p class="font-bold text-red-600">Ada Keterlambatan!</p>
            <p class="text-sm text-gray-700">Terdapat <span id="toastCount" class="font-bold">0</span> siswa yang terlambat.</p>
        </div>
    </div>
</div>

<div id="siswaTelatModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[100] p-4 modal">
    <div class="bg-yellow-50 rounded-xl shadow-2xl max-w-lg w-full relative modal-content transform scale-95 opacity-0">
        <button onclick="closeSiswaTelatModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 font-bold text-2xl z-10">&times;</button>
        <div class="p-6">
            <h3 class="text-xl font-bold text-yellow-800 mb-4 border-b pb-2">Daftar Siswa Terlambat (Saat Ini)</h3>
            <div class="max-h-[60vh] overflow-y-auto pr-2">
                <ul id="siswaTelatList" class="space-y-3">
                    </ul>
            </div>
            <div class="mt-4 text-center">
                 <button onclick="closeSiswaTelatModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 shadow-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div id="pesanModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[101] p-4 modal"> <div class="bg-yellow-50 rounded-xl shadow-2xl max-w-lg w-full relative modal-content transform scale-95 opacity-0">
        <button onclick="closePesanModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 font-bold text-2xl z-10">&times;</button>
        <div class="p-6">
            <h3 class="text-xl font-bold text-yellow-800 mb-4 border-b pb-2">Pesan Masuk dari Siswa</h3>
            <div class="max-h-[60vh] overflow-y-auto pr-2">
                <ul id="pesanListContainer" class="space-y-3">
                    </ul>
            </div>
            <div class="mt-4 text-center">
                <button onclick="closePesanModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 shadow-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script>
// === [MODIFIKASI] Data dari PHP untuk JS ===
const initialNotifList = <?php echo json_encode($notifList); ?>;
const allSiswaTelatList = <?php echo json_encode($siswaTelatList); ?>; 
let allPesanList = <?php echo json_encode($semuaPesanList); ?>; // <-- [BARU] Dibuat 'let' agar bisa di-update
const initialPesanBaruCount = <?php echo $jumlahPesanBaru; ?>; // <-- [BARU]

// [MODIFIKASI] Data untuk PDF
// ... (Data PDF Anda tidak berubah) ...
let currentDataForDownload = {
    labels: <?php echo json_encode($bulan); ?>, 
    totalPinjam: <?php echo json_encode($totalPinjam); ?>,
    tepatWaktu: <?php echo json_encode($tepatWaktu); ?>,
    terlambat: <?php echo json_encode($terlambat); ?>,
    totalBuku: <?php echo $totalBuku; ?>,
    tersedia: <?php echo $tersedia; ?>,
    dipinjam: <?php echo $dipinjam; ?>,
    totalPeminjaman: <?php echo $totalPeminjaman; ?>,
    jumlahTelat: <?php echo $jumlahSiswaTelatBulanIni; ?>, 
    totalDenda: <?php echo $totalDenda; ?>,
    lateStudentDetails: <?php echo json_encode($initialLateStudentDetails); ?> 
};
const { jsPDF } = window.jspdf;


// === Grafik ChartJS (Tidak Berubah) ===
// ... (Kode ChartJS Anda tidak berubah) ...
const ctx = document.getElementById('laporanChart');
const laporanChart = new Chart(ctx, {
    type: 'line', 
    data: { 
        labels: currentDataForDownload.labels, 
        datasets: [ 
            {label:'Total Pinjaman',data: currentDataForDownload.totalPinjam, borderColor:'#D97706',backgroundColor:'rgba(245, 158, 11, 0.2)',tension:0.4,fill:true}, 
            {label:'Tepat Waktu',data: currentDataForDownload.tepatWaktu, borderColor:'#059669',backgroundColor:'rgba(16, 185, 129, 0.2)',tension:0.4,fill:true}, 
            {label:'Terlambat',data: currentDataForDownload.terlambat, borderColor:'#DC2626',backgroundColor:'rgba(239, 68, 68, 0.2)',tension:0.4,fill:true} 
        ] 
    }, 
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } }, elements: { line: { borderWidth: 2 }, point: { radius: 3 } } }
});


// === Navigasi Bulan Dinamis (Tidak Berubah) ===
// ... (Kode Navigasi Bulan Anda tidak berubah) ...
const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
let currentMonthIndex = new Date().getMonth(); 
let currentYear = new Date().getFullYear(); 
const currentMonthSpan = document.getElementById('currentMonth');
function updateMonthLabel(){ 
    currentMonthSpan.textContent = monthNames[currentMonthIndex] + " " + currentYear; 
    fetchMonthlyData(); 
}
document.getElementById('prevMonth').onclick = () => { 
    currentMonthIndex--; 
    if(currentMonthIndex < 0){ currentMonthIndex = 11; currentYear--; } 
    updateMonthLabel(); 
};
document.getElementById('nextMonth').onclick = () => { 
    currentMonthIndex++; 
    if(currentMonthIndex > 11){ currentMonthIndex = 0; currentYear++; } 
    updateMonthLabel(); 
};
function fetchMonthlyData(){ 
    fetch(`get_month_data.php?month=${currentMonthIndex + 1}&year=${currentYear}`)
    .then(res => res.json())
    .then(data => { 
        currentDataForDownload = data; 
        
        // Update Kartu Statistik
        document.getElementById('statTotalBuku').textContent = data.totalBuku; 
        document.getElementById('statTersedia').textContent = data.tersedia; 
        document.getElementById('statDipinjam').textContent = data.dipinjam; 
        document.getElementById('statTotalPeminjaman').textContent = data.totalPeminjaman; 
        document.getElementById('statJumlahTelat').textContent = data.jumlahTelat; 
        document.getElementById('statTotalDenda').textContent = "Rp " + parseInt(data.totalDenda).toLocaleString('id-ID'); 
        
        // Update Grafik
        laporanChart.data.labels = data.labels; 
        laporanChart.data.datasets[0].data = data.totalPinjam; 
        laporanChart.data.datasets[1].data = data.tepatWaktu; 
        laporanChart.data.datasets[2].data = data.terlambat; 
        laporanChart.update(); 
    }).catch(err => { 
        console.error("Gagal mengambil data bulanan:", err); 
    }); 
}
currentMonthSpan.textContent = monthNames[currentMonthIndex] + " " + currentYear;


// === LOGIKA NOTIFIKASI KETERLAMBATAN (Tidak Berubah) ===
// ... (Kode Notifikasi Keterlambatan Anda tidak berubah) ...
const notifBell = document.getElementById('notifBell');
const notifDot = document.getElementById('notifDot');
const toastNotif = document.getElementById('toastNotif');
const toastCount = document.getElementById('toastCount');
const notifDropdown = document.getElementById('notifDropdown');
const notifDropdownList = document.getElementById('notifDropdownList');
let notifAjaxSent = false; 
function hideToast(event) { if (event) event.stopPropagation(); toastNotif.classList.remove('show'); }
if (initialNotifList.length > 0) {
    toastCount.textContent = initialNotifList.length;
    setTimeout(() => { toastNotif.classList.add('show'); }, 100);
    setTimeout(hideToast, 7000); 
}
function openNotif() { hideToast(); populateAndToggleDropdown(); }
function populateAndToggleDropdown() {
    if (notifDropdownList.innerHTML.trim() === '') {
         if (initialNotifList.length === 0) {
             notifDropdownList.innerHTML = '<div class="p-3 text-center text-gray-500">Tidak ada notifikasi baru.</div>';
         } else {
             let listHTML = '';
             const max_list = 10;
             for (let i = 0; i < initialNotifList.length && i < max_list; i++) {
                 const n = initialNotifList[i];
                 const dendaFormatted = parseInt(n.denda).toLocaleString('id-ID');
                 const safeName = n.user_name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                 const safeJudul = n.judul.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                 
                 listHTML += `
                     <a href="riwayat_peminjaman.php?search=${safeName}" class="block p-3 border-b hover:bg-yellow-50">
                         <p class="font-semibold text-yellow-800">${safeName}</p>
                         <p class="text-sm text-gray-600">${safeJudul}</p>
                         <p class="text-sm text-red-600 font-medium">${n.terlambat_hari} hari terlambat (Rp ${dendaFormatted})</p>
                     </a>`;
             }
             if (initialNotifList.length > max_list) {
                 listHTML += `<div class="p-3 text-center text-gray-500 text-sm">...dan ${initialNotifList.length - max_list} notifikasi lainnya.</div>`;
             }
             notifDropdownList.innerHTML = listHTML;
         }
    }
    notifDropdown.classList.toggle('hidden');
    if (!notifDropdown.classList.contains('hidden') && notifDot && notifDot.style.display !== 'none' && !notifAjaxSent) {
        notifDot.style.display = 'none'; 
        notifAjaxSent = true; 
        fetch('mark_notif_read.php', { method: 'POST', keepalive: true }).catch(console.error);
    }
}
notifBell.addEventListener('click', function(e) { e.stopPropagation(); populateAndToggleDropdown(); });
window.addEventListener('click', function(e) {
    if (!notifDropdown.classList.contains('hidden')) {
        if (!notifBell.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    }
});


// === LOGIKA MODAL SISWA TELAT (Tidak Berubah) ===
// ... (Kode Modal Siswa Telat Anda tidak berubah) ...
const siswaTelatModal = document.getElementById('siswaTelatModal');
const siswaTelatListContainer = document.getElementById('siswaTelatList');
const modalContent = siswaTelatModal.querySelector('.modal-content');
function openSiswaTelatModal() {
    siswaTelatListContainer.innerHTML = ''; 
    if (allSiswaTelatList.length === 0) { // Menggunakan allSiswaTelatList (Query 5)
        siswaTelatListContainer.innerHTML = '<li class="text-gray-500 italic text-center">Tidak ada siswa yang terlambat saat ini.</li>';
    } else {
        allSiswaTelatList.forEach(siswa => {
            const safeName = siswa.user_name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            const safeJudul = siswa.judul.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            
            const li = document.createElement('li');
            li.className = 'bg-yellow-100 p-3 rounded-lg shadow-sm flex justify-between items-center';
            
            li.innerHTML = `
                <div>
                    <p class="font-semibold text-yellow-800">${safeName}</p>
                    <p class="text-sm text-gray-600">${safeJudul} (${siswa.terlambat_hari} hari)</p>
                </div>
                <a href="riwayat_peminjaman.php?search=${siswa.user_nis}" class="text-sm bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                    Lihat Riwayat
                </a>
            `;
            siswaTelatListContainer.appendChild(li);
        });
    }
    siswaTelatModal.classList.remove('hidden');
    siswaTelatModal.classList.add('flex');
    setTimeout(() => { modalContent.classList.remove('scale-95', 'opacity-0'); modalContent.classList.add('scale-100', 'opacity-100'); }, 10); 
}
function closeSiswaTelatModal() {
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { siswaTelatModal.classList.add('hidden'); siswaTelatModal.classList.remove('flex'); }, 300); 
}
siswaTelatModal.addEventListener('click', function(event) { if (event.target === this) { closeSiswaTelatModal(); } });


// === [BARU] LOGIKA MODAL PESAN MASUK ===
const pesanModal = document.getElementById('pesanModal');
const pesanListContainer = document.getElementById('pesanListContainer');
const pesanModalContent = pesanModal.querySelector('.modal-content');
const pesanBell = document.getElementById('pesanBell');
let pesanAjaxSent = false;

function openPesanModal() {
    const pesanDot = document.getElementById('pesanDot'); // Ambil dot di dalam fungsi
    pesanListContainer.innerHTML = ''; 
    
    if (allPesanList.length === 0) {
        pesanListContainer.innerHTML = '<li class="text-gray-500 italic text-center">Tidak ada pesan masuk.</li>';
    } else {
        allPesanList.forEach(pesan => {
            const safeNama = pesan.nama.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            const safeEmail = pesan.email.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            const safePesan = pesan.pesan.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>");
            
            // Format tanggal
            const tgl = new Date(pesan.tanggal_kirim);
            const tglFormatted = tgl.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

            const li = document.createElement('li');
            li.className = 'bg-yellow-100 p-3 rounded-lg shadow-sm';
            
            // Menandai pesan belum dibaca
            if (pesan.sudah_dibaca == 0) {
                li.classList.add('border-l-4', 'border-yellow-500');
            }

            li.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <p class="font-semibold text-yellow-800">${safeNama}</p>
                    <span class="text-xs text-gray-500">${tglFormatted}</span>
                </div>
                <p class="text-sm text-gray-600 mb-2 italic"><a href="mailto:${safeEmail}" class="hover:underline">${safeEmail}</a></p>
                <p class="text-sm text-gray-800">${safePesan}</p>
            `;
            pesanListContainer.appendChild(li);
        });
    }
    
    pesanModal.classList.remove('hidden');
    pesanModal.classList.add('flex');
    setTimeout(() => { pesanModalContent.classList.remove('scale-95', 'opacity-0'); pesanModalContent.classList.add('scale-100', 'opacity-100'); }, 10); 

    // Tandai sudah dibaca
    if (pesanDot && pesanDot.style.display !== 'none' && !pesanAjaxSent) {
        pesanDot.style.display = 'none'; 
        pesanAjaxSent = true; 
        fetch('mark_pesan_read.php', { method: 'POST', keepalive: true }).catch(console.error);
    }
}

function closePesanModal() {
    pesanModalContent.classList.remove('scale-100', 'opacity-100');
    pesanModalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { pesanModal.classList.add('hidden'); pesanModal.classList.remove('flex'); }, 300); 
}

// Event Listeners
pesanBell.addEventListener('click', openPesanModal);
pesanModal.addEventListener('click', function(event) { if (event.target === this) { closePesanModal(); } });


// === [BARU] LOGIKA REAL-TIME POLLING ===
function checkRealtimeUpdates() {
    // File PHP baru yang harus dibuat
    fetch('get_updates.php') 
    .then(res => res.json())
    .then(data => {
        // 1. Update Notif Keterlambatan
        const notifDotElement = document.getElementById('notifDot');
        if (data.newLoanNotifs > 0) {
            if (notifDotElement) {
                notifDotElement.textContent = data.newLoanNotifs;
                notifDotElement.style.display = 'flex';
            } else {
                // Jika notifDot tidak ada, buat baru
                const newNotifDot = document.createElement('span');
                newNotifDot.id = 'notifDot';
                newNotifDot.className = 'absolute top-1 right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center';
                newNotifDot.textContent = data.newLoanNotifs;
                document.getElementById('notifBell').appendChild(newNotifDot);
            }
            // Reset AJAX flag jika ada notif baru
            notifAjaxSent = false; 
            // Update data global dan reset dropdown
            window.initialNotifList = data.allLateLoans;
            document.getElementById('notifDropdownList').innerHTML = '';
        }

        // 2. Update Notif Pesan Masuk
        const pesanDotElement = document.getElementById('pesanDot');
        if (data.newMessageNotifs > 0) {
            if (pesanDotElement) {
                pesanDotElement.textContent = data.newMessageNotifs;
                pesanDotElement.style.display = 'flex';
            } else {
                // Jika pesanDot tidak ada, buat baru
                const newPesanDot = document.createElement('span');
                newPesanDot.id = 'pesanDot';
                newPesanDot.className = 'absolute top-1 right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center';
                newPesanDot.textContent = data.newMessageNotifs;
                document.getElementById('pesanBell').appendChild(newPesanDot);
            }
            // Reset AJAX flag jika ada notif baru
            pesanAjaxSent = false;
             // Update data global
            if (data.allMessages) {
                window.allPesanList = data.allMessages;
            }
        }
    })
    .catch(console.error);
}

// Panggil fungsi polling setiap 15 detik (15000 ms)
setInterval(checkRealtimeUpdates, 15000);



// === LOGIKA DOWNLOAD RECAP PDF (Tidak Berubah) ===
// ... (Kode PDF Anda tidak berubah) ...
async function getLogoBase64(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) return null; 
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        console.error("Gagal memuat logo:", error);
        return null;
    }
}

document.getElementById('downloadButton').addEventListener('click', async function(e) {
    e.preventDefault();

    const downloadBtn = e.currentTarget;
    const originalText = downloadBtn.innerHTML;
    downloadBtn.innerHTML = `<span>Mempersiapkan PDF...</span>`;
    downloadBtn.classList.add('opacity-70', 'cursor-not-allowed');

    try {
        const doc = new jsPDF();
        const logoBase64 = await getLogoBase64('../images/logo.png'); // Pastikan logo.png ada
        
        const monthYear = document.getElementById('currentMonth').textContent.trim();
        const filename = `Recap_E-Perpus_${monthYear.replace(' ', '_')}.pdf`;

        // 3. Buat Header Dokumen
        if (logoBase64) {
            doc.addImage(logoBase64, 'PNG', 15, 10, 25, 25);
        }
        
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Rekap Laporan E-Perpus", 45, 20);
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text(`Periode: ${monthYear}`, 45, 27);
        doc.line(15, 40, 195, 40); 

        // 4. Buat Bagian Statistik Utama
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text("Statistik Utama", 15, 50);
        
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const stats = [
            `Total Buku (Overall): ${currentDataForDownload.totalBuku}`,
            `Tersedia (Overall): ${currentDataForDownload.tersedia}`,
            `Dipinjam (Overall): ${currentDataForDownload.dipinjam}`,
            `Total Peminjaman (Bulan Ini): ${currentDataForDownload.totalPeminjaman}`,
            `Siswa Telat (Bulan Ini): ${currentDataForDownload.jumlahTelat}`,
            `Total Denda (Bulan Ini): Rp ${parseInt(currentDataForDownload.totalDenda).toLocaleString('id-ID')}`
        ];
        doc.text(stats, 15, 58); 

        // 5. Buat Tabel Tren Peminjaman (Hanya 1 Bulan)
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text("Statistik Peminjaman Bulanan", 15, 95);

        const tableHead = [["Bulan", "Tahun", "Total Pinjam", "Tepat Waktu", "Terlambat"]];
        const tableBody = [];

        const monthLabel = monthNames[currentMonthIndex]; 
        const yearLabel = currentYear; 

        const totalPinjamBulanIni = currentDataForDownload.totalPinjam[currentMonthIndex] || 0;
        const tepatWaktuBulanIni = currentDataForDownload.tepatWaktu[currentMonthIndex] || 0;
        const terlambatBulanIni = currentDataForDownload.terlambat[currentMonthIndex] || 0;

        tableBody.push([
            monthLabel,
            yearLabel,
            totalPinjamBulanIni,
            tepatWaktuBulanIni,
            terlambatBulanIni
        ]);
        
        doc.autoTable({
            head: tableHead,
            body: tableBody,
            startY: 102,
            theme: 'grid', 
            headStyles: { fillColor: [217, 119, 6], textColor: [255, 255, 255] },
            styles: { cellPadding: 2, fontSize: 9 }
        });

        // 6. Buat Tabel Detail Keterlambatan
        let finalY = doc.lastAutoTable.finalY + 15; 
        
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text("Detail Keterlambatan Siswa (Bulan Ini)", 15, finalY);

        const lateTableHead = [["NIS", "Nama Siswa", "Tgl. Pinjam", "Tgl. Kembali", "Denda"]];
        const lateTableBody = [];

        if (currentDataForDownload.lateStudentDetails && currentDataForDownload.lateStudentDetails.length > 0) {
            currentDataForDownload.lateStudentDetails.forEach(siswa => {
                lateTableBody.push([
                    siswa.user_nis,
                    siswa.user_name,
                    siswa.tanggal_pinjam,
                    siswa.tanggal_kembali || 'Belum Kembali',
                    `Rp ${parseInt(siswa.denda).toLocaleString('id-ID')}`
                ]);
            });
        } else {
            lateTableBody.push([ { content: "Tidak ada siswa terlambat untuk periode ini.", colSpan: 5, styles: { halign: 'center' } } ]);
        }
        
        doc.autoTable({
            head: lateTableHead,
            body: lateTableBody,
            startY: finalY + 7,
            theme: 'grid',
            headStyles: {
                fillColor: [220, 38, 38], // Merah
                textColor: [255, 255, 255]
            },
            styles: {
                cellPadding: 2,
                fontSize: 9
            }
        });

        // 7. Simpan PDF
        doc.save(filename);

    } catch (err) {
        console.error("Gagal membuat PDF:", err);
        alert("Terjadi kesalahan saat membuat PDF. Silakan cek console.");
    } finally {
        downloadBtn.innerHTML = originalText;
        downloadBtn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
});
</script>
</body>
</html>