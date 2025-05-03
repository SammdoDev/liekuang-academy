<?php
include '../koneksi.php';
session_start();

// Cek jika user belum login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Proses form jika ada POST request
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi input
    if (empty($_POST['nama_cabang'])) {
        $message = "Nama cabang tidak boleh kosong!";
        $message_type = "error";
    } else {
        // Sanitasi input
        $nama_cabang = htmlspecialchars(trim($_POST['nama_cabang']));
        
        // Cek apakah cabang dengan nama yang sama sudah ada
        $check_query = $conn->prepare("SELECT COUNT(*) as total FROM cabang WHERE nama_cabang = ?");
        $check_query->bind_param("s", $nama_cabang);
        $check_query->execute();
        $result = $check_query->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $message = "Cabang dengan nama '$nama_cabang' sudah ada!";
            $message_type = "warning";
        } else {
            // Insert data cabang baru
            $insert_query = $conn->prepare("INSERT INTO cabang (nama_cabang) VALUES (?)");
            $insert_query->bind_param("s", $nama_cabang);
            
            if ($insert_query->execute()) {
                $last_id = $conn->insert_id;
                $message = "Cabang baru berhasil ditambahkan!";
                $message_type = "success";
                
                // Redirect jika diminta
                if (isset($_POST['save_and_return']) && $_POST['save_and_return'] == '1') {
                    header("Location: ../index.php?created=success&cabang_name=" . urlencode($nama_cabang));
                    exit();
                }
            } else {
                $message = "Gagal menambahkan cabang: " . $insert_query->error;
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cabang Baru</title>
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
    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 bg-white dark:bg-gray-800 shadow-md">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard</h2>
            </div>
            
            <nav class="p-6 space-y-4">
                <a href="../index.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Home</span>
                </a>
                <a href="../staff/staff.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-users mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Staff</span>
                </a>
                <a href="../divisi/divisi.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-sitemap mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Divisi</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="tambah_cabang.php" 
                       class="flex items-center text-white bg-primary-600 px-4 py-3 rounded-lg shadow-md hover:bg-primary-700 transition">
                        <i class="fas fa-plus-circle mr-3"></i>
                        <span>Tambah Cabang</span>
                    </a>
                </div>
            </nav>
            
            <div class="p-6 mt-auto border-t border-gray-200 dark:border-gray-700">
                <button id="darkModeToggle" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-moon mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>Mode Gelap</span>
                </button>
                <a href="../logout.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-600 dark:hover:text-red-400 mt-2 transition">
                    <i class="fas fa-sign-out-alt mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Konten -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <div class="flex items-center">
                    <a href="../index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 mr-2">
                        <i class="fas fa-home"></i>
                    </a>
                    <span class="text-gray-500 dark:text-gray-500 mx-2">/</span>
                    <span class="text-gray-700 dark:text-gray-300">Tambah Cabang</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white mt-4">Tambah Cabang Baru</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    <i class="fas fa-building mr-2"></i>
                    Tambahkan cabang baru ke dalam sistem
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-plus-circle text-primary-600 dark:text-primary-400 mr-2"></i>
                        Form Tambah Cabang
                    </h2>
                </div>

                <div class="p-6">
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-lg border-l-4 <?php 
                            if ($message_type === 'success') {
                                echo 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-500 dark:border-green-500/70';
                            } elseif ($message_type === 'warning') {
                                echo 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 border-yellow-500 dark:border-yellow-500/70';
                            } else {
                                echo 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-500 dark:border-red-500/70';
                            }
                        ?>">
                            <div class="flex items-center">
                                <?php if ($message_type === 'success'): ?>
                                    <i class="fas fa-check-circle mr-2"></i>
                                <?php elseif ($message_type === 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                <?php endif; ?>
                                <p class="font-medium"><?= $message ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <div>
                            <label for="nama_cabang" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Cabang
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-building"></i>
                                </span>
                                <input type="text" id="nama_cabang" name="nama_cabang" value="<?= isset($_POST['nama_cabang']) ? htmlspecialchars($_POST['nama_cabang']) : '' ?>" required
                                    class="pl-10 py-3 block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Masukkan nama cabang">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Nama cabang harus unik dan akan menjadi identitas cabang di semua sistem
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                            <div class="flex items-center text-gray-600 dark:text-gray-400 mb-3">
                                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                <span class="font-medium">Informasi</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Setelah cabang dibuat, Anda dapat menambahkan divisi dan staff melalui menu yang sesuai. Cabang baru akan langsung aktif dalam sistem.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <button type="submit" name="save" 
                                    class="bg-primary-600 text-white px-4 py-3 rounded-lg hover:bg-primary-700 transition flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i>
                                Simpan Cabang
                            </button>
                            
                            <button type="submit" name="save_and_return" value="1" 
                                    class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                <i class="fas fa-check-double mr-2"></i>
                                Simpan & Kembali
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Panduan Tambah Cabang -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-question-circle text-primary-600 dark:text-primary-400 mr-2"></i>
                    Panduan Tambah Cabang
                </h3>
                
                <div class="space-y-4">
                    <div class="flex">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 mr-3">
                            <span>1</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 dark:text-white">Isi Nama Cabang</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Masukkan nama cabang yang jelas dan mudah diidentifikasi.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 mr-3">
                            <span>2</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 dark:text-white">Simpan Data</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Klik "Simpan Cabang" untuk menyimpan dan tetap di halaman ini, atau klik "Simpan & Kembali" untuk kembali ke daftar cabang.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 mr-3">
                            <span>3</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 dark:text-white">Kelola Cabang</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Setelah cabang dibuat, Anda dapat menambahkan divisi dan mengatur staff untuk cabang tersebut.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
                darkModeToggle.innerHTML = '<i class="fas fa-moon mr-3 text-gray-500"></i><span>Mode Gelap</span>';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun mr-3 text-gray-400"></i><span>Mode Terang</span>';
            }
        });
        
        // Update toggle text on load
        if (html.classList.contains('dark')) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun mr-3 text-gray-400"></i><span>Mode Terang</span>';
        }
    </script>
</body>
</html>