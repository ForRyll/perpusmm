<?php
include "../config.php";

if(isset($_POST['register'])){
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Cek username sudah ada atau belum
    $cek = $conn->query("SELECT * FROM admin WHERE username='$username'");
    if($cek->num_rows > 0){
        $error = "Username sudah terdaftar!";
    } else {
        $conn->query("INSERT INTO admin (username,password) VALUES ('$username','$password')");
        // Tautan di pesan sukses ini bisa Anda sesuaikan gayanya jika perlu
        $success = "Admin berhasil didaftarkan! <a href='login.php' class='underline text-blue-700'>Login disini</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Admin - E-Perpus</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 flex items-center justify-center min-h-screen relative overflow-hidden">

<div id="tsparticles" class="absolute inset-0 w-full h-full z-0"></div>
<div class="bg-yellow-100 p-10 rounded-xl shadow-lg w-full max-w-md relative z-10">
    <div class="flex flex-col items-center mb-6">
        <div class="p-2 bg-yellow-200 rounded-full mb-4">
            <img src="../images/aeter.png" alt="Logo" class="h-16 w-16 object-contain">
        </div>
        <h1 class="text-2xl font-bold text-yellow-800">Register Admin E-Perpus</h1>
    </div>

    <?php 
        if(isset($error)) echo "<p class='text-red-500 mb-4 text-center'>$error</p>";
        if(isset($success)) echo "<p class='text-green-600 mb-4 text-center'>$success</p>";
    ?>
    <form method="POST" class="flex flex-col space-y-4">
        <input type="text" name="username" placeholder="Username" required
               class="w-full px-4 py-3 border border-yellow-300 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
        <input type="password" name="password" placeholder="Password" required
               class="w-full px-4 py-3 border border-yellow-300 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-700">
        <button type="submit" name="register"
                class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-full font-medium transition duration-200">
            Daftar
        </button>
    </form>

    <p class="text-center mt-4 text-yellow-800">
        Sudah punya akun? 
        <a href="login.php" class="underline font-medium hover:text-yellow-600">Login disini</a>
    </p>
    <p class="text-gray-600 text-sm text-center mt-6">
        &copy; <?php echo date('Y'); ?> E-Perpustakaan Modern. MTs Muhammadiyah 1 Purbalingga.
    </p>
</div>


<script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
<script>
    tsParticles.load("tsparticles", {
        fpsLimit: 60,
        particles: {
            number: { value: 80, density: { enable: true, value_area: 800 } },
            color: { value: "#ca8a04" }, // Warna tema kuning
            shape: { type: "circle" },
            opacity: { value: 0.5, random: false },
            size: { value: 3, random: true },
            links: {
                enable: true,
                distance: 150,
                color: "#eab308",
                opacity: 0.4,
                width: 1
            },
            move: {
                enable: true,
                speed: 2,
                direction: "none",
                random: false,
                straight: false,
                out_mode: "out",
                bounce: false
            }
        },
        interactivity: {
            detect_on: "canvas",
            events: {
                onhover: { enable: true, mode: "repulse" },
                onclick: { enable: true, mode: "push" },
                resize: true
            },
            modes: {
                repulse: { distance: 100, duration: 0.4 },
                push: { particles_nb: 4 }
            }
        },
        detectRetina: true
    });
</script>
</body>
</html>