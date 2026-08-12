<?php
include "../config.php";

// Set halaman saat ini untuk navbar
$currentPage = 'koleksi';

// Ambil semua kategori untuk dropdown filter
$kategori_list = $conn->query("SELECT * FROM kategori ORDER BY nama ASC");

// Ambil input search & filter kategori
$search = $_GET['search'] ?? '';
$filter_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

// --- LOGIKA PAGINATION ---
$perPage = 18; // 6 buku per baris, 3 baris
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $perPage;

// --- LOGIKA QUERY ---
$baseQuery = "FROM buku LEFT JOIN kategori ON buku.kategori_id=kategori.id WHERE buku.aktif=1";
$whereClauses = [];
$params = [];
$types = "";

if(!empty($search)){
    $whereClauses[] = "(buku.judul LIKE ? OR buku.penulis LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ss";
}
if($filter_kategori > 0){
    $whereClauses[] = "kategori.id = ?";
    $params[] = $filter_kategori;
    $types .= "i";
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = " AND " . implode(" AND ", $whereClauses);
}

// 1. Query Hitung Total Data
$totalQueryStr = "SELECT COUNT(buku.id) as total " . $baseQuery . $whereSql;
$totalStmt = $conn->prepare($totalQueryStr);
if (!$totalStmt) { die("Query count prepare failed: " . $conn->error); }
if (!empty($params)) { $totalStmt->bind_param($types, ...$params); }
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalBuku = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
$totalPage = ceil($totalBuku / $perPage);
$totalStmt->close();

// 2. Query Ambil Data dengan LIMIT
$dataQueryStr = "SELECT buku.*, kategori.nama AS kategori, kategori.id as kategori_id " . $baseQuery . $whereSql . " ORDER BY buku.judul ASC LIMIT ?, ?";
// Buat array parameter baru untuk data query
$dataParams = $params;
$dataParams[] = $start;
$dataParams[] = $perPage;
$dataTypes = $types . "ii";

$dataStmt = $conn->prepare($dataQueryStr);
if (!$dataStmt) { die("Query data prepare failed: " . $conn->error); }
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$buku_all = $dataStmt->get_result();
if (!$buku_all) { die("Query data execution failed: " . $dataStmt->error); }
$dataStmt->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Koleksi Buku - E-Perpustakaan Modern</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Style CSS sama seperti beranda.php */
#bukuPopup { backdrop-filter: blur(5px); }
.clear-btn { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background-color: transparent; border: none; padding: 0.5rem; cursor: pointer; display: none; color: #9ca3af; line-height: 1; }
.clear-btn:hover { color: #374151; }
#searchInput { padding-right: 2.5rem; }
@keyframes scaleUp { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.animate-scale-up { animation: scaleUp 0.2s ease-out forwards; }
.modal { transition: opacity 0.3s ease; }
.modal-content { transition: transform 0.3s ease, opacity 0.3s ease; }
/* Style untuk Pagination */
.pagination-link { display: inline-block; padding: 0.5rem 1rem; margin: 0 0.25rem; border-radius: 0.375rem; font-weight: 500; transition: background-color 0.2s; }
.pagination-link-active { background-color: #f59e0b; color: white; }
.pagination-link-default { background-color: #fef3c7; color: #b45309; }
.pagination-link-default:hover { background-color: #fde68a; }
.pagination-link-disabled { background-color: #fefce8; color: #d6d3d1; cursor: not-allowed; }
</style>
</head>
<body class="bg-yellow-50 text-gray-800 font-sans flex flex-col min-h-screen">

<header class="bg-yellow-100 shadow-md sticky top-0 z-50">
    <div class="container mx-auto flex items-center justify-between px-4 py-2 relative">
        <div class="flex items-center space-x-3">
            <div class="p-0.5 bg-yellow-50 rounded-full shadow-md"> <img src="../images/aeter.png" alt="Logo" class="h-14 w-14 object-contain"> </div>
            <span class="text-xl font-bold text-yellow-800 tracking-wide">E-Perpustakaan</span>
        </div>
        <div class="md:hidden"> <button id="hamburger-btn" class="text-yellow-800 hover:text-yellow-600 focus:outline-none p-2"> <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg> </button> </div>
        <nav id="nav-menu" class="hidden md:flex md:items-center md:space-x-6 absolute md:relative top-full left-0 w-full md:w-auto bg-yellow-100 md:bg-transparent shadow-md md:shadow-none py-4 md:py-0 flex-col md:flex-row space-y-2 md:space-y-0 items-center">
            <a href="beranda.php" class="text-yellow-800 hover:text-yellow-600 w-full md:w-auto text-center md:text-left py-2 md:py-0 px-4 md:px-0 <?php echo ($currentPage == 'beranda') ? 'font-bold underline underline-offset-4' : 'font-medium'; ?>">Beranda</a>
            <a href="koleksi.php" class="text-yellow-800 hover:text-yellow-600 w-full md:w-auto text-center md:text-left py-2 md:py-0 px-4 md:px-0 <?php echo ($currentPage == 'koleksi') ? 'font-bold underline underline-offset-4' : 'font-medium'; ?>">Koleksi</a>
            <a href="tentang.php" class="text-yellow-800 hover:text-yellow-600 w-full md:w-auto text-center md:text-left py-2 md:py-0 px-4 md:px-0 <?php echo ($currentPage == 'tentang') ? 'font-bold underline underline-offset-4' : 'font-medium'; ?>">Tentang</a>
            <a href="kontak.php" class="text-yellow-800 hover:text-yellow-600 w-full md:w-auto text-center md:text-left py-2 md:py-0 px-4 md:px-0 <?php echo ($currentPage == 'kontak') ? 'font-bold underline underline-offset-4' : 'font-medium'; ?>">Kontak</a>
        </nav>
    </div>
</header>

<section class="bg-yellow-50 py-10 shadow-inner sticky top-[76px] z-40">
    <div class="max-w-5xl mx-auto text-center px-4">
        <h2 class="text-2xl font-semibold text-yellow-800 mb-4">Temukan Koleksi Kami</h2>
        <form action="" method="GET" class="flex flex-col md:flex-row items-center justify-center gap-3">
            <div class="relative flex-grow w-full md:w-auto">
                <input type="text" name="search" placeholder="Cari judul atau pengarang..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-5 py-3 pr-10 border border-yellow-300 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700 shadow-sm" id="searchInput">
                <button type="button" class="clear-btn" id="clearBtn" aria-label="Hapus pencarian"> <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg> </button>
            </div>
            <select name="kategori" class="w-full md:w-auto px-4 py-3 border border-yellow-300 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700 shadow-sm bg-white appearance-none">
                <option value="">Semua Kategori</option>
                <?php if ($kategori_list && $kategori_list->num_rows > 0): $kategori_list->data_seek(0); while($k = $kategori_list->fetch_assoc()): ?> <option value="<?php echo $k['id']; ?>" <?php echo ($filter_kategori==$k['id'])?'selected':''; ?>> <?php echo htmlspecialchars($k['nama']); ?> </option> <?php endwhile; endif; ?>
            </select>
            <button type="submit" class="w-full md:w-auto bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-full font-medium transition duration-200 shadow-md"> Cari </button>
        </form>
    </div>
</section>

<main class="flex-grow pt-6 pb-12 px-6 max-w-6xl mx-auto space-y-10 w-full">
    <?php if ($buku_all && $buku_all->num_rows > 0): ?>
        <h3 class="text-xl font-bold text-yellow-800 mb-4 border-l-4 border-yellow-500 pl-2">
            <?php echo (!empty($search) || $filter_kategori > 0) ? 'Hasil Pencarian' : 'Semua Koleksi'; ?>
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php while ($row = $buku_all->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg overflow-hidden flex flex-col cursor-pointer transform hover:scale-105 transition duration-300" onclick="openBukuPopup(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                <div class="h-48 bg-gray-100 flex items-center justify-center overflow-hidden relative"> <?php if (!empty($row['gambar']) && file_exists("../uploads/" . $row['gambar'])): ?> <img src="../uploads/<?php echo $row['gambar']; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>" class="object-cover h-full w-full transition duration-300 group-hover:scale-110"> <?php else: ?> <span class="text-gray-400 text-xs px-2 text-center">Gambar tidak tersedia</span> <?php endif; ?> </div>
                <div class="p-3 flex flex-col flex-grow"> <h4 class="text-sm font-semibold text-yellow-800 mb-1 truncate" title="<?php echo htmlspecialchars($row['judul']); ?>"><?php echo htmlspecialchars($row['judul']); ?></h4> <p class="text-gray-600 text-xs mb-1 truncate">Penulis: <?php echo htmlspecialchars($row['penulis']); ?></p> <span class="font-semibold text-center py-1 rounded text-xs mt-auto <?php echo ($row['status'] == 'Tersedia') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"> <?php echo $row['status']; ?> </span> </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="mt-8 flex justify-center space-x-2">
            <?php
            if ($totalPage > 1):
                // Buat query string dasar
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseLink = "?" . http_build_query($queryParams);
                $separator = !empty($queryParams) ? "&" : "";
                
                // Tombol "Sebelumnya"
                if ($page > 1): ?>
                    <a href="<?php echo $baseLink . $separator . 'page=' . ($page - 1); ?>" class="pagination-link pagination-link-default">&lt;</a>
                <?php else: ?>
                    <span class="pagination-link pagination-link-disabled">&lt;</span>
                <?php endif;

                // Tampilkan nomor halaman
                for($p=1; $p<=$totalPage; $p++):
                    $isActive = ($p == $page);
                    $link = $baseLink . $separator . "page=" . $p;
            ?>
                <a href="<?php echo $link; ?>"
                   class="pagination-link <?php echo $isActive ? 'pagination-link-active' : 'pagination-link-default'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php
                endfor;

                // Tombol "Berikutnya"
                if ($page < $totalPage): ?>
                    <a href="<?php echo $baseLink . $separator . 'page=' . ($page + 1); ?>" class="pagination-link pagination-link-default">&gt;</a>
                <?php else: ?>
                    <span class="pagination-link pagination-link-disabled">&gt;</span>
                <?php endif;
            
            endif;
            ?>
        </div>
        
    <?php else: ?>
        <p class="text-gray-600 italic text-center text-lg"><?php echo (!empty($search) || $filter_kategori > 0) ? 'Buku tidak ditemukan.' : 'Belum ada buku di koleksi.'; ?></p>
    <?php endif; ?>
</main>

<div id="bukuPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[100] p-4 modal opacity-0 scale-95">
    <div class="bg-yellow-50 rounded-xl shadow-2xl max-w-lg w-full p-6 relative modal-content transform scale-95 opacity-0" onclick="event.stopPropagation()">
        <button onclick="closeBukuPopup()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 font-bold text-2xl z-10">&times;</button>
        <div id="bukuPopupContent" class="flex flex-col items-center max-h-[80vh] overflow-y-auto"></div>
    </div>
</div>

<footer class="bg-yellow-600 text-white shadow-inner-top mt-auto">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col md:flex-row justify-center md:justify-start items-start gap-8 md:gap-16 mb-8">

            <div class="w-full md:w-auto flex items-center space-x-8 md:justify-start justify-center text-center md:text-left pr-4">
                <img src="../images/aeter.png" alt="Logo" class="h-24 w-24 flex-shrink-0">
                <div>
                    <p class="font-semibold text-base">MTs Muhammadiyah 1 Purbalingga</p>
                    
                    <address class="text-sm text-white leading-tight not-italic mt-1">
                        <strong>Lokasi:</strong><br>
                        Jl. Piere Tendean No.1, Purbalingga, Purbalingga Kidul, Kec. Purbalingga,<br> 
                        <br>Kab. Purbalingga, JAWA TENGAH</br>
                        53313
                    </address>

                    <a href="https://www.google.com/maps/place/MTs+Muhammadiyah+01+Purbalingga/@-7.3906618,109.3607027,17z/data=!3m1!4b1!4m6!3m5!1s0x2e6559784e7f85f3:0x48ddd69061afe451!8m2!3d-7.3906671!4d109.3632776!16s%2Fg%2F11pvs16v_t?entry=ttu&g_ep=EgoyMDI1MTAxNC4wIKXMDSoASAFQAw%3D%3D" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="text-sm text-white hover:text-yellow-300 transition duration-200 mt-3 inline-flex items-center font-medium">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Lihat di Peta
                    </a>
                    </div>
            </div>

            <div class="w-full sm:w-auto text-center md:text-left">
                <h4 class="font-semibold text-base mb-3 uppercase tracking-wider">Tautan Cepat</h4>
                <nav class="flex flex-col space-y-2">
                    <a href="beranda.php" class="hover:text-yellow-300 text-sm transition duration-200">Beranda</a>
                    <a href="koleksi.php" class="hover:text-yellow-300 text-sm transition duration-200">Koleksi</a>
                    <a href="tentang.php" class="hover:text-yellow-300 text-sm transition duration-200">Tentang</a>
                    <a href="kontak.php" class="hover:text-yellow-300 text-sm transition duration-200">Kontak</a>
                </nav>
            </div>

            <div class="w-full sm:w-auto text-center md:text-left">
                <h4 class="font-semibold text-base mb-3 uppercase tracking-wider">Ikuti Kami</h4>
                <div class="flex space-x-4 justify-center md:justify-start">
                    <a href="#" target="_blank" rel="noopener noreferrer" class="text-white hover:text-yellow-300 transition duration-300 transform hover:scale-110" title="Instagram"> <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.012-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218 1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.465 2.525c.636-.247 1.363-.416 2.427-.465C8.917 2.013 9.27 2 11.685 2h.63zM12 6.865a5.135 5.135 0 100 10.27 5.135 5.135 0 000-10.27zm0 8.468a3.333 3.333 0 110-6.666 3.333 3.333 0 010 6.666zm5.338-9.87a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z" clip-rule="evenodd" /></svg> </a>
                    <a href="#" target="_blank" rel="noopener noreferrer" class="text-white hover:text-yellow-300 transition duration-300 transform hover:scale-110" title="YouTube"> <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.78 22 12 22 12s0 3.22-.418 4.814a2.504 2.504 0 01-1.768 1.768c-1.594.419-7.814.419-7.814.419s-6.22 0-7.814-.419a2.505 2.505 0 01-1.768-1.768C2 15.22 2 12 2 12s0-3.22.418-4.814a2.505 2.505 0 011.768-1.768C5.78 5 11.998 5 11.998 5s6.22 0 7.814.418zM9.999 15.199l4.987-3.2-4.987-3.2v6.4z" clip-rule="evenodd" /></svg> </a>
                    <a href="#" target="_blank" rel="noopener noreferrer" class="text-white hover:text-yellow-300 transition duration-300 transform hover:scale-110" title="WhatsApp"> <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.861 9.861 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg> </a>
                    <a href="#" target="_blank" rel="noopener noreferrer" class="text-white hover:text-yellow-300 transition duration-300 transform hover:scale-110" title="Facebook"> <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg> </a>
                </div>
            </div>
        
        </div>

        <div class="mt-8 pt-6 border-t border-yellow-500 text-center text-sm text-yellow-100">
            <p>&copy; <?php echo date('Y'); ?> E-Perpustakaan by AetherLib. MTs Muhammadiyah 01 Purbalingga.</p>
        </div>
    </div>
</footer>

<div id="bukuPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[100] p-4 modal opacity-0 scale-95">
    <div class="bg-yellow-50 rounded-xl shadow-2xl max-w-lg w-full p-6 relative modal-content transform scale-95 opacity-0" onclick="event.stopPropagation()">
        <button onclick="closeBukuPopup()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 font-bold text-2xl z-10">&times;</button>
        <div id="bukuPopupContent" class="flex flex-col items-center max-h-[80vh] overflow-y-auto"></div>
    </div>
</div>

<script>
// Fungsi Popup Buku (dengan animasi)
function openBukuPopup(buku){
    const gambarSrc = (buku.gambar && buku.gambar !== "") ? `../uploads/${buku.gambar}` : '../images/no-image.png';
    const content = ` <img src="${gambarSrc}" class="h-64 w-auto mb-4 object-cover rounded-lg shadow-lg" alt="${buku.judul}" onerror="this.onerror=null; this.src='../images/no-image.png'; this.classList.add('p-4');"> <h3 class="text-2xl font-bold text-yellow-800 mb-2 text-center">${buku.judul}</h3> <p class="text-gray-700 mb-1"><strong>Penulis:</strong> ${buku.penulis}</p> <p class="text-gray-700 mb-1"><strong>Tahun:</strong> ${buku.tahun}</p> <p class="text-gray-700 mb-1"><strong>Kategori:</strong> ${buku.kategori || '-'}</p> <p class="text-gray-700 mb-1"><strong>Rak:</strong> ${buku.rak}</p> <p class="text-gray-700 mb-3"><strong>Status:</strong> <span class="${buku.status=='Tersedia' ? 'text-green-700 font-semibold' : 'text-red-700 font-semibold'}">${buku.status}</span></p> <div class="flex justify-center space-x-3 mt-3"> <button onclick="closeBukuPopup()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 shadow-sm">Tutup</button> ${buku.status=='Tersedia' ? '<button class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 shadow-md">Pinjam Buku</button>' : ''} </div> `;
    document.getElementById('bukuPopupContent').innerHTML = content;
    const popup = document.getElementById('bukuPopup');
    const modalContent = popup.querySelector('.modal-content');
    popup.classList.remove('hidden','opacity-0','scale-95'); popup.classList.add('flex');
    modalContent.classList.remove('opacity-0','scale-95'); void modalContent.offsetWidth;
    modalContent.classList.add('opacity-100','scale-100');
}
function closeBukuPopup(){
    const popup = document.getElementById('bukuPopup');
    const modalContent = popup.querySelector('.modal-content');
    modalContent.classList.remove('opacity-100','scale-100');
    modalContent.classList.add('opacity-0','scale-95');
    popup.classList.add('opacity-0');
    setTimeout(() => { popup.classList.add('hidden'); popup.classList.remove('flex', 'opacity-0'); }, 300);
}
document.getElementById('bukuPopup').addEventListener('click', function(event) { if (event.target === this) { closeBukuPopup(); } });
function scrollCarousel(wrapper, amount){ const container = wrapper.querySelector('.carousel-container'); container.scrollBy({ left: amount, behavior: 'smooth' }); }

// Tombol X hapus search (LOGIKA DIPERBAIKI)
const searchInput = document.getElementById('searchInput');
const clearBtn = document.getElementById('clearBtn');
if (searchInput && clearBtn) {
    const toggleClearButton = () => {
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
    };
    searchInput.addEventListener('input', toggleClearButton);
    clearBtn.addEventListener('click', (e)=>{
         e.preventDefault(); // Mencegah form submit
         searchInput.value = '';
         toggleClearButton();
         searchInput.focus();
         searchInput.form.submit(); // Langsung submit form untuk reset
    });
    toggleClearButton(); // Cek saat load
}

// --- SCRIPT UNTUK HAMBURGER MENU ---
const hamburgerBtn = document.getElementById('hamburger-btn');
const navMenu = document.getElementById('nav-menu');
const hamburgerIconPath = hamburgerBtn ? hamburgerBtn.querySelector('svg path') : null;
if (hamburgerBtn && navMenu && hamburgerIconPath) {
    hamburgerBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        navMenu.classList.toggle('hidden');
        if (navMenu.classList.contains('hidden')) { hamburgerIconPath.setAttribute('d', 'M4 6h16M4 12h16m-7 6h7'); } else { hamburgerIconPath.setAttribute('d', 'M6 18L18 6M6 6l12 12'); }
    });
    document.addEventListener('click', function(event) {
        if (!navMenu.classList.contains('hidden') && !navMenu.contains(event.target) && !hamburgerBtn.contains(event.target)) {
            navMenu.classList.add('hidden');
            hamburgerIconPath.setAttribute('d', 'M4 6h16M4 12h16m-7 6h7');
        }
    });
}
</script>
</body>
</html>