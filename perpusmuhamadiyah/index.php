<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E-Perpustakaan Modern</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-yellow-50 flex items-center justify-center min-h-screen relative overflow-hidden">

<div id="tsparticles" class="absolute inset-0 w-full h-full z-0"></div>
<div class="bg-yellow-100 p-10 rounded-xl shadow-lg text-center w-full max-w-md relative z-10">
    <div class="flex justify-center mb-6">
        <div class="p-2 bg-yellow-200 rounded-full">
            <img src="images/aeter.png" alt="Logo" class="h-24 w-24 object-contain">
        </div>
    </div>

    <h1 class="text-3xl font-bold mb-6 text-yellow-800">Selamat Datang di E-Perpustakaan</h1>

    <a href="admin/login.php" 
       class="block bg-yellow-400 hover:bg-yellow-600 text-white py-3 px-6 rounded-full mb-4 font-medium transition duration-200">
        Login Admin
    </a>
    <a href="siswa/beranda.php" 
       class="block bg-yellow-400 hover:bg-yellow-500 text-white py-3 px-6 rounded-full font-medium transition duration-200">
        Masuk Siswa
    </a>

    <p class="text-gray-600 text-sm mt-6">
        © <?php echo date('Y'); ?> E-Perpustakaan Modern. MTs Muhammadiyah 1 Purbalingga.
    </p>
</div>


<script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>

<script>
    tsParticles.load("tsparticles", {
        fpsLimit: 60,
        particles: {
            number: {
                value: 80, // Jumlah partikel
                density: {
                    enable: true,
                    value_area: 800
                }
            },
            // Warna partikel disesuaikan dengan tema kuning Anda
            color: {
                value: "#ca8a04" // Mirip text-yellow-800
            },
            shape: {
                type: "circle"
            },
            opacity: {
                value: 0.5,
                random: false,
                anim: {
                    enable: false
                }
            },
            size: {
                value: 3,
                random: true,
                anim: {
                    enable: false
                }
            },
            // Garis penghubung antar partikel
            links: {
                enable: true,
                distance: 150,
                color: "#eab308", // Mirip bg-yellow-500
                opacity: 0.4,
                width: 1
            },
            // Gerakan partikel
            move: {
                enable: true,
                speed: 2,
                direction: "none",
                random: false,
                straight: false,
                out_mode: "out",
                bounce: false,
                attract: {
                    enable: false
                }
            }
        },
        // Interaktivitas (bereaksi terhadap mouse)
        interactivity: {
            detect_on: "canvas",
            events: {
                // Mendorong partikel saat di-hover
                onhover: {
                    enable: true,
                    mode: "repulse"
                },
                // Menambahkan partikel baru saat di-klik
                onclick: {
                    enable: true,
                    mode: "push"
                },
                resize: true
            },
            modes: {
                repulse: {
                    distance: 100,
                    duration: 0.4
                },
                push: {
                    particles_nb: 4
                }
            }
        },
        detectRetina: true
    });
</script>
</body>
</html>