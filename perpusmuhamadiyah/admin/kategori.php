<?php
session_start(); // Pastikan ini ada di baris paling atas
include "../config.php";

// Error reporting (hapus saat production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Pastikan admin login
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// --- LOGIKA PIN/UNPIN KATEGORI ---
if(isset($_GET['pin_id'])){
    $pin_id = (int)$_GET['pin_id'];
    
    // 1. Cek status pin saat ini
    $stmt_check = $conn->prepare("SELECT is_pinned FROM kategori WHERE id = ?");
    if($stmt_check) {
        $stmt_check->bind_param("i", $pin_id);
        $stmt_check->execute();
        $current_status_res = $stmt_check->get_result();
        $current_status = $current_status_res ? $current_status_res->fetch_assoc() : null;
        $stmt_check->close();

        if ($current_status && $current_status['is_pinned'] == 1) {
            // 2. Jika SUDAH di-pin, UNPIN dia
            $stmt_unpin = $conn->prepare("UPDATE kategori SET is_pinned = 0 WHERE id = ?");
            if($stmt_unpin){
                $stmt_unpin->bind_param("i", $pin_id);
                $stmt_unpin->execute();
                $stmt_unpin->close();
            } else { $_SESSION['error_message'] = "Gagal unpin: " . $conn->error; }

        } elseif ($current_status) { // Hanya pin jika kategori ditemukan
            // 3. Jika BELUM di-pin, PIN dia (dan unpin yang lain)
            // 3a. Unpin semua kategori lain
            $conn->query("UPDATE kategori SET is_pinned = 0");
            
            // 3b. Pin kategori yang dipilih
            $stmt_pin = $conn->prepare("UPDATE kategori SET is_pinned = 1 WHERE id = ?");
            if($stmt_pin){
                 $stmt_pin->bind_param("i", $pin_id);
                 $stmt_pin->execute();
                 $stmt_pin->close();
            } else { $_SESSION['error_message'] = "Gagal pin: " . $conn->error; }
        } else {
             $_SESSION['error_message'] = "Kategori untuk pin tidak ditemukan.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal cek status pin: " . $conn->error;
    }

    // Redirect kembali ke halaman ini (untuk membersihkan URL)
    header("Location: kategori.php");
    exit;
}
// --- AKHIR LOGIKA PIN ---


// --- LOGIKA TAMBAH KATEGORI ---
if(isset($_POST['tambah'])){
    $nama = trim($_POST['nama']); // Trim spasi
    if(!empty($nama)){
        // Cek duplikasi (opsional tapi bagus)
        $check_dup = $conn->prepare("SELECT id FROM kategori WHERE nama = ?");
        if($check_dup){
            $check_dup->bind_param("s", $nama);
            $check_dup->execute();
            $check_dup->store_result();
            if ($check_dup->num_rows == 0) {
                 // Tidak ada duplikat, insert
                 $stmt = $conn->prepare("INSERT INTO kategori (nama) VALUES (?)");
                 if ($stmt) {
                     $stmt->bind_param("s", $nama);
                     if($stmt->execute()){
                         $_SESSION['success_message'] = "Kategori '$nama' berhasil ditambahkan!";
                     } else {
                         $_SESSION['error_message'] = "Gagal menambahkan kategori: " . $stmt->error;
                     }
                     $stmt->close();
                 } else { $_SESSION['error_message'] = "Gagal prepare insert: " . $conn->error; }
            } else {
                 $_SESSION['error_message'] = "Kategori '$nama' sudah ada.";
            }
            $check_dup->close();
        } else { $_SESSION['error_message'] = "Gagal cek duplikasi: " . $conn->error; }
    } else {
         $_SESSION['error_message'] = "Nama kategori tidak boleh kosong.";
    }
    // Redirect setelah POST untuk mencegah resubmit
    header("Location: kategori.php");
    exit;
}
// --- AKHIR LOGIKA TAMBAH ---

// --- Ambil pesan dari session ---
$error = $_SESSION['error_message'] ?? null;
$success = $_SESSION['success_message'] ?? null;
// Hapus pesan dari session setelah diambil
unset($_SESSION['error_message'], $_SESSION['success_message']);
// --- END Ambil pesan ---

// Ambil semua kategori, yang di-pin tampil paling atas
$kategori_result = $conn->query("SELECT * FROM kategori ORDER BY is_pinned DESC, id DESC");
// Check jika query gagal
if (!$kategori_result) {
    $error = "Gagal mengambil daftar kategori: " . $conn->error; // Tampilkan error query
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Kategori - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 min-h-screen flex relative">

<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0 shadow-lg">
    <div class="flex items-center mb-6"> <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo"> <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span> </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-yellow-800">Kelola Kategori</h1>
        <a href="buku.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow text-sm">← Kembali ke Daftar Buku</a>
    </div>

    <?php
        // Tampilkan pesan dari session
        if(isset($error)) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$error</p></div>";
        if(isset($success)) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$success</p></div>";
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <form method="POST" action="kategori.php" class="flex flex-col space-y-4 bg-white p-6 rounded-xl shadow h-full">
                <h2 class="text-yellow-800 font-bold text-lg mb-2">Tambah Kategori Baru</h2>
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                    <input type="text" name="nama" id="nama" placeholder="Contoh: Fiksi, Sejarah..." required class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
                </div>
                <button type="submit" name="tambah" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-lg font-medium transition duration-200 shadow"> + Tambah </button>
            </form>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-xl shadow h-full flex flex-col">
                <h2 class="text-yellow-800 font-bold mb-4 text-lg">Daftar Kategori</h2>
                <input type="text" id="kategoriSearchInput" placeholder="Cari kategori..." class="w-full px-4 py-2 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700 mb-4">
                <div class="flex-grow overflow-y-auto min-h-0 pr-2" style="max-height: 17rem;">
                    <ul class="space-y-3" id="kategoriList">
                        <?php if(!$kategori_result || $kategori_result->num_rows == 0): // Cek jika query gagal atau 0 hasil ?>
                            <li class="text-gray-500 italic" id="kategoriEmptyMsg">
                                <?php echo ($kategori_result) ? 'Belum ada kategori.' : 'Gagal memuat kategori.'; ?>
                            </li>
                        <?php else: ?>
                            <?php while($row = $kategori_result->fetch_assoc()): ?>
                                <li class="flex justify-between items-center bg-yellow-50 px-4 py-3 rounded-lg shadow-sm kategori-item">
                                    <span class="text-gray-800 font-medium kategori-nama"><?php echo htmlspecialchars($row['nama']); ?></span>
                                    <div class="flex space-x-2 items-center">
                                        <?php if($row['is_pinned'] == 1): ?>
                                            <a href="?pin_id=<?php echo $row['id']; ?>" class="p-1 rounded hover:bg-yellow-200" title="Lepas Sematan (Unpin)"> <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /> </svg> </a>
                                        <?php else: ?>
                                            <a href="?pin_id=<?php echo $row['id']; ?>" class="p-1 rounded hover:bg-green-200" title="Sematkan (Pin) Kategori Ini"> <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /> </svg> </a>
                                        <?php endif; ?>
                                        <a href="kategori_edit.php?id=<?php echo $row['id']; ?>" class="p-1 rounded hover:bg-yellow-200" title="Edit"> <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/> </svg> </a>
                                        <button type="button" onclick="openModal(<?php echo $row['id']; ?>)" class="p-1 rounded hover:bg-red-200" title="Hapus"> <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/> </svg> </button>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                            <li class="text-gray-500 italic hidden" id="kategoriNotFoundMsg">Kategori tidak ditemukan.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-80 mx-2">
        <h3 class="text-lg font-bold text-yellow-800 mb-4">Hapus Kategori</h3>
        <p class="mb-6 text-gray-700">Apakah Anda yakin ingin menghapus kategori ini?</p>
        <div class="flex justify-end space-x-2">
            <button onclick="closeModal()" class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 font-medium">Batal</button>
            <a id="confirmDelete" href="#" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white font-medium">Hapus</a>
        </div>
    </div>
</div>

<script>
function openModal(id){ document.getElementById('deleteModal').classList.remove('hidden'); document.getElementById('deleteModal').classList.add('flex'); document.getElementById('confirmDelete').href = 'kategori_hapus.php?id=' + id; }
function closeModal(){ const modal = document.getElementById('deleteModal'); modal.classList.add('hidden'); modal.classList.remove('flex'); }
document.getElementById('deleteModal').addEventListener('click', function(event) { if (event.target === this) { closeModal(); } });
document.getElementById('kategoriSearchInput').addEventListener('input', function() { const filter = this.value.toLowerCase(); const list = document.getElementById('kategoriList'); const items = list.getElementsByClassName('kategori-item'); const notFoundMsg = document.getElementById('kategoriNotFoundMsg'); let visibleCount = 0; for (let i = 0; i < items.length; i++) { const item = items[i]; const nama = item.querySelector('.kategori-nama').textContent.toLowerCase(); if (nama.includes(filter)) { item.style.display = 'flex'; visibleCount++; } else { item.style.display = 'none'; } } if (visibleCount === 0 && items.length > 0) { notFoundMsg.style.display = 'block'; } else { notFoundMsg.style.display = 'none'; } const emptyMsg = document.getElementById('kategoriEmptyMsg'); if (emptyMsg) { emptyMsg.style.display = (filter.length > 0) ? 'none' : (items.length === 0 ? 'block' : 'none'); } }); // Perbaiki logika empty msg saat search
</script>

</body>
</html>