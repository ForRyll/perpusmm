<?php
session_start();
include "../config.php";

// Error reporting (hapus saat production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Ambil daftar buku yang tersedia
$bukuTersedia = $conn->query("SELECT id, judul FROM buku WHERE status='Tersedia' AND aktif=1 ORDER BY judul ASC"); // Urutkan berdasarkan judul

// Ambil buku ke array JS
$bukuArray = [];
if ($bukuTersedia) { // Cek jika query berhasil
    while($row = $bukuTersedia->fetch_assoc()){
        // Pastikan judul tidak null sebelum dimasukkan ke array
        if ($row['judul'] !== null) {
            $bukuArray[] = ['id'=>$row['id'], 'judul'=>$row['judul']];
        }
    }
} else {
    error_log("Gagal mengambil daftar buku tersedia: " . $conn->error);
}


// Proses submit
if(isset($_POST['submit'])){
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $kelas = trim($_POST['kelas']);
    $buku_id = isset($_POST['buku_id']) ? (int)$_POST['buku_id'] : 0;
    $tanggal_pinjam_str = $_POST['tanggal_pinjam'];
    $tanggal_kembali_str = null; // Inisialisasi

    // Validasi dasar
    if (empty($nis) || empty($nama) || empty($kelas) || empty($buku_id) || empty($tanggal_pinjam_str)) {
         $error = "Semua field wajib diisi!";
    } else {
        // Hitung tanggal kembali hanya jika tanggal pinjam valid
        try {
            $tanggal_pinjam_dt = new DateTime($tanggal_pinjam_str);
            // Cek apakah tanggal valid (misal bukan 0000-00-00 yang mungkin lolos validasi input type date)
             if ($tanggal_pinjam_dt && $tanggal_pinjam_dt->format('Y') > 1970) {
                 $tanggal_kembali_dt = (clone $tanggal_pinjam_dt)->modify('+7 days');
                 $tanggal_kembali_str = $tanggal_kembali_dt->format('Y-m-d');
             } else {
                 throw new Exception("Format tanggal pinjam tidak valid.");
             }

        } catch (Exception $e) {
            $error = "Format tanggal pinjam tidak valid!";
            error_log("Error tanggal pinjam: " . $e->getMessage());
            $tanggal_kembali_str = null; // Set null jika tanggal pinjam error
        }

        // Lanjutkan hanya jika tanggal kembali berhasil dihitung
        if ($tanggal_kembali_str) {
            $stmt = $conn->prepare("INSERT INTO peminjaman (buku_id, user_nis, user_name, user_class, tanggal_pinjam, status, status_pengembalian) VALUES (?, ?, ?, ?, ?, 'Dipinjam', 'Belum Dikembalikan')");
             // Tanggal kembali tidak perlu di-insert saat pinjam, akan diisi saat dikembalikan
            if ($stmt) {
                $stmt->bind_param("issss", $buku_id, $nis, $nama, $kelas, $tanggal_pinjam_str);

                if($stmt->execute()){
                    // Update status buku
                    $stmt2 = $conn->prepare("UPDATE buku SET status='Dipinjam' WHERE id=?");
                    if ($stmt2) {
                        $stmt2->bind_param("i", $buku_id);
                        $stmt2->execute();
                        $stmt2->close();
                        $success = "Peminjaman berhasil ditambahkan!";
                    } else {
                         // Rollback? Atau setidaknya beri tahu error
                         $error = "Peminjaman tercatat, tapi gagal update status buku: " . $conn->error;
                         // Mungkin perlu menghapus record peminjaman yang baru dibuat jika kritis
                         // $conn->query("DELETE FROM peminjaman WHERE id = " . $stmt->insert_id);
                    }
                } else {
                    $error = "Gagal menyimpan peminjaman! " . $stmt->error;
                }
                $stmt->close();
            } else {
                 $error = "Gagal menyiapkan statement: " . $conn->error;
            }
        }
        // Jika $error sudah diset karena tanggal tidak valid, tidak perlu else tambahan
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Peminjam - Dashboard Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Styling untuk dropdown buku custom */
    #dropdownBuku li:hover { background-color: #fef3c7; } /* bg-yellow-100 */
</style>
</head>
<body class="bg-yellow-50 min-h-screen flex">

<aside class="bg-yellow-100 w-64 min-h-screen p-6 flex flex-col fixed left-0 top-0 bottom-0 shadow-lg">
    <div class="flex items-center mb-6"> <img src="../images/aeter.png" class="h-12 w-12 mr-2" alt="Logo"> <span class="text-xl font-bold text-yellow-800">E-Perpus Admin</span> </div>
    <nav class="flex flex-col space-y-2 mt-4">
        <a href="dashboard.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Dashboard</a>
        <a href="buku.php" class="text-yellow-800 font-medium px-3 py-2 rounded hover:bg-yellow-200">Daftar Buku</a>
        <a href="peminjaman.php" class="text-yellow-800 font-medium px-3 py-2 rounded bg-yellow-200">Data Peminjaman</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded mt-auto text-center">Logout</a>
    </nav>
</aside>

<main class="flex-grow p-6 ml-64">
    <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-bold text-yellow-800 mb-6">Tambah Peminjam</h1>

        <?php
        // Notifikasi konsisten
        if(isset($error)) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$error</p></div>";
        if(isset($success)) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-sm' role='alert'><p>$success</p></div>";
        ?>

        <form method="POST" class="flex flex-col space-y-4 bg-white p-6 rounded-xl shadow">

            <div>
                <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">NIS Siswa</label>
                <input type="text" name="nis" id="nis" placeholder="NIS Siswa" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
            </div>

            <div>
                <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                <input type="text" name="nama" id="nama" placeholder="Nama Siswa" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
            </div>

            <div>
                <label for="kelas" class="block text-sm font-medium text-gray-700 mb-1">Kelas Siswa</label>
                <input type="text" name="kelas" id="kelas" placeholder="Contoh: 7A, 8B..." required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
            </div>

            <div class="relative">
                 <label for="searchBuku" class="block text-sm font-medium text-gray-700 mb-1">Pilih Buku</label>
                 <input type="text" id="searchBuku" placeholder="Cari Judul Buku..." autocomplete="off" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
                 <ul id="dropdownBuku" class="absolute z-10 w-full bg-white border border-yellow-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-md"></ul>
                 <input type="hidden" name="buku_id" id="buku_id" required>
                 <p id="bukuError" class="text-red-500 text-sm mt-1 hidden">Silakan pilih buku dari daftar.</p>
            </div>

            <div>
                 <label for="tanggal_pinjam" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam</label>
                 <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" required
                    class="w-full px-4 py-3 border border-yellow-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700 bg-white"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="flex space-x-4 pt-2">
                <a href="peminjaman.php"
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 rounded-lg font-medium text-center transition duration-200">
                    Kembali
                </a>
                <button type="submit" name="submit" id="submitBtn"
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-lg font-medium transition duration-200 shadow">
                    Simpan Peminjaman
                </button>
            </div>
        </form>
    </div>
</main>

<script>
const bukuData = <?php echo json_encode($bukuArray); ?>;
const searchInput = document.getElementById('searchBuku');
const dropdown = document.getElementById('dropdownBuku');
const hiddenBukuId = document.getElementById('buku_id');
const bukuError = document.getElementById('bukuError');
const submitBtn = document.getElementById('submitBtn');
let selectedJudul = ''; // Simpan judul buku yang dipilih

function filterBuku(showAll = false){
    const query = searchInput.value.toLowerCase();
    dropdown.innerHTML = ''; // Kosongkan hasil sebelumnya
    bukuError.classList.add('hidden'); // Sembunyikan error
    searchInput.classList.remove('border-red-500'); // Hapus border merah

    // Tentukan data yang akan difilter
    const dataToFilter = (showAll || query === '') ? bukuData : bukuData.filter(b => b.judul.toLowerCase().includes(query));

    if (dataToFilter.length === 0 && !showAll) {
         dropdown.innerHTML = '<li class="px-4 py-2 text-gray-500 italic">Buku tidak ditemukan</li>';
    } else {
        dataToFilter.forEach(b => {
            const li = document.createElement('li');
            li.textContent = b.judul;
            li.className = "px-4 py-2 cursor-pointer hover:bg-yellow-100 text-gray-800"; // Ganti hover
            li.onclick = () => {
                searchInput.value = b.judul;      // Tampilkan judul di input
                hiddenBukuId.value = b.id;        // Set ID di hidden input
                selectedJudul = b.judul;          // Simpan judul terpilih
                dropdown.classList.add('hidden'); // Tutup dropdown
                bukuError.classList.add('hidden'); // Sembunyikan error
                searchInput.classList.remove('border-red-500'); // Hapus border merah
            };
            dropdown.appendChild(li);
        });
    }
    // Tampilkan dropdown jika ada hasil ATAU jika menampilkan semua saat fokus
    dropdown.classList.toggle('hidden', dataToFilter.length === 0 && !showAll);
}

// Event listener untuk input
searchInput.addEventListener('input', () => {
     filterBuku();
     // Reset hidden ID jika user mengetik lagi setelah memilih
     if (searchInput.value !== selectedJudul) {
          hiddenBukuId.value = '';
     }
});

// Tampilkan semua saat fokus
searchInput.addEventListener('focus', () => {
    filterBuku(true); // Tampilkan semua buku
});

// Tutup dropdown jika klik di luar
document.addEventListener('click', e => {
    if(!searchInput.contains(e.target) && !dropdown.contains(e.target)){
        dropdown.classList.add('hidden');
        // Validasi saat klik di luar jika belum memilih
        validateBukuSelection();
    }
});

// Validasi saat input kehilangan fokus (blur)
searchInput.addEventListener('blur', () => {
     // Beri sedikit jeda agar event click di list dropdown sempat berjalan
     setTimeout(validateBukuSelection, 150);
});

// Fungsi validasi
function validateBukuSelection() {
     // Cek apakah input KOSONG atau input TIDAK SAMA dengan judul yang dipilih DARI LIST
     if (searchInput.value.trim() === '' || hiddenBukuId.value === '') {
          // Jika user mengetik sesuatu tapi tidak memilih dari list
          if (searchInput.value.trim() !== '') {
               searchInput.classList.add('border-red-500'); // Tambah border merah
               bukuError.classList.remove('hidden');     // Tampilkan pesan error
               hiddenBukuId.value = ''; // Pastikan ID kosong
          } else {
              // Jika input memang kosong
               searchInput.classList.remove('border-red-500');
               bukuError.classList.add('hidden');
               hiddenBukuId.value = '';
          }
     } else {
         // Jika sudah memilih dari list (hiddenBukuId ada isinya)
          searchInput.classList.remove('border-red-500');
          bukuError.classList.add('hidden');
     }
}

// Validasi sebelum submit form
document.querySelector('form').addEventListener('submit', function(event) {
    validateBukuSelection(); // Panggil validasi sekali lagi
    if (hiddenBukuId.value === '') {
        event.preventDefault(); // Hentikan submit jika ID buku kosong
        searchInput.focus(); // Fokuskan kembali ke input buku
        searchInput.classList.add('border-red-500');
        bukuError.classList.remove('hidden');
        alert("Silakan pilih buku yang valid dari daftar."); // Beri alert tambahan
    }
});

</script>
</body>
</html>