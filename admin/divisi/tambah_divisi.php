<?php
include '../../koneksi.php';
session_start();
$role = $_SESSION['role'];
$username = $_SESSION['username'];

if ($role !== 'admin') {
    header("Location: ../../unauthorized.php");
    exit;
}


if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

// Query untuk mengambil nama cabang
$queryCabang = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id_cabang = ?");
$queryCabang->bind_param("i", $cabang_id);
$queryCabang->execute();
$resultCabang = $queryCabang->get_result();
$cabang = $resultCabang->fetch_assoc();

if (!$cabang) {
    die("Cabang tidak ditemukan!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_divisi = $_POST['nama_divisi'];
    
    $stmt = $conn->prepare("INSERT INTO divisi (nama_divisi, id_cabang) VALUES (?, ?)");
    $stmt->bind_param("si", $nama_divisi, $cabang_id);
    
    if ($stmt->execute()) {
        header("Location: divisi.php?cabang_id=$cabang_id");
        exit();
    } else {
        $error_message = "Gagal menambahkan divisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Divisi - <?= htmlspecialchars($cabang['nama_cabang']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center">
            <a href="divisi.php?cabang_id=<?= $cabang_id ?>"
                class="flex items-center text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-plus-circle text-primary-600 dark:text-primary-400 mr-2"></i>
                    Tambah Divisi
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Cabang: <?= htmlspecialchars($cabang['nama_cabang']) ?>
                </p>
            </div>

            <div class="p-6">
                <?php if (isset($error_message)): ?>
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 dark:bg-red-900/30 dark:text-red-400 dark:border-red-500/70"
                    role="alert">
                    <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> <?= $error_message ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="nama_divisi"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Divisi
                        </label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                <i class="fas fa-users-cog"></i>
                            </span>
                            <input type="text" id="nama_divisi" name="nama_divisi" required
                                class="pl-10 py-4 block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Masukkan nama divisi">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <a href="divisi.php?cabang_id=<?= $cabang_id ?>"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition mr-3 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Batalkan
                        </a>
                        <button type="submit"
                            class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Divisi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            <button id="darkModeToggle"
                class="inline-flex items-center text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                <i class="fas fa-moon mr-2 dark:hidden"></i>
                <i class="fas fa-sun mr-2 hidden dark:inline"></i>
                <span class="dark:hidden">Mode Gelap</span>
                <span class="hidden dark:inline">Mode Terang</span>
            </button>
        </div>
    </div>

    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark');
        }
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            if (savedTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }
        
        // Toggle theme
        darkModeToggle.addEventListener('click', () => {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>