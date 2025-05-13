<?php
include '../../koneksi.php';

session_start();
$role = $_SESSION['role'];
$username = $_SESSION['username'];

if ($role !== 'admin') {
    header("Location: ../../unauthorized.php");
    exit;
}

// Validasi parameter
if (!isset($_GET['id_staff']) || !isset($_GET['divisi_id'])) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Parameter tidak valid. Diperlukan id_staff dan divisi_id.</p>
        <a href="javascript:history.back()" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>');
}

$id_staff = intval($_GET['id_staff']);
$divisi_id = intval($_GET['divisi_id']);
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : '';
$cabang_id = isset($_GET['cabang_id']) ? intval($_GET['cabang_id']) : '';

// Ambil data staff
$staffQuery = $conn->prepare("SELECT s.*, d.nama_divisi, c.nama_cabang 
                             FROM staff s
                             JOIN divisi d ON s.id_divisi = d.id_divisi
                             JOIN cabang c ON s.id_cabang = c.id_cabang
                             WHERE s.id_staff = ?");
$staffQuery->bind_param("i", $id_staff);
$staffQuery->execute();
$result = $staffQuery->get_result();

if ($result->num_rows === 0) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Staff tidak ditemukan.</p>
        <a href="staff.php?divisi_id=' . $divisi_id . '" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Staff
        </a>
    </div>');
}

$staffData = $result->fetch_assoc();
$nama_staff = $staffData['nama_staff'];
$divisi_nama = $staffData['nama_divisi'];
$cabang_nama = $staffData['nama_cabang'];

// Jika ada parameter konfirmasi
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    // Proses penghapusan
    $delete_stmt = $conn->prepare("DELETE FROM staff WHERE id_staff = ?");
    $delete_stmt->bind_param("i", $id_staff);
    
    if ($delete_stmt->execute()) {
        // Buat URL redirect dengan semua parameter
        $redirect_url = "staff.php?";
            
        if (!empty($skill_id)) {
            $redirect_url .= "skill_id=$skill_id&";
        }
        
        $redirect_url .= "divisi_id=$divisi_id";
        
        if (!empty($cabang_id)) {
            $redirect_url .= "&cabang_id=$cabang_id";
        }
        
        $redirect_url .= "&deleted=success&staff_name=" . urlencode($nama_staff);
        
        // Redirect ke daftar staff dengan pesan sukses
        header("Location: $redirect_url");
        exit();
    } else {
        $error_message = "Gagal menghapus staff: " . $delete_stmt->error;
    }
}

// Buat URL kembali dengan semua parameter
$back_url = "staff.php?";

if (!empty($skill_id)) {
    $back_url .= "skill_id=$skill_id&";
}

$back_url .= "divisi_id=$divisi_id";

if (!empty($cabang_id)) {
    $back_url .= "&cabang_id=$cabang_id";
}

// URL ke halaman edit
$edit_url = "edit_staff.php?id_staff=$id_staff&divisi_id=$divisi_id";
if (!empty($skill_id)) {
    $edit_url .= "&skill_id=$skill_id";
}
if (!empty($cabang_id)) {
    $edit_url .= "&cabang_id=$cabang_id";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Staff - <?= htmlspecialchars($nama_staff) ?></title>
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
    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center">
            <a href="<?= $back_url ?>" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Kembali ke Daftar Staff</span>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center space-x-3">
                <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-full">
                    <i class="fas fa-user-times text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Konfirmasi Hapus Staff
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        ID Staff: <?= $id_staff ?>
                    </p>
                </div>
            </div>

            <div class="p-6">
                <?php if (isset($error_message)): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border-l-4 border-red-500 dark:border-red-500/70 text-red-700 dark:text-red-400">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <p class="font-medium"><?= $error_message ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-500 dark:border-blue-500/70">
                    <div class="flex items-center text-blue-800 dark:text-blue-300">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span class="font-medium">Informasi Staff</span>
                    </div>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-blue-700 dark:text-blue-400">
                        <div>
                            <span class="font-semibold">Divisi:</span> 
                            <?= htmlspecialchars($divisi_nama) ?>
                        </div>
                        <div>
                            <span class="font-semibold">Cabang:</span> 
                            <?= htmlspecialchars($cabang_nama) ?>
                        </div>
                    </div>
                </div>

                <div class="p-5 mb-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-l-4 border-yellow-500 dark:border-yellow-500/70">
                    <div class="flex items-center text-yellow-800 dark:text-yellow-300 font-medium mb-3">
                        <i class="fas fa-exclamation-triangle mr-2 text-lg"></i>
                        <span>Peringatan!</span>
                    </div>
                    
                    <p class="text-yellow-700 dark:text-yellow-400 mb-3">
                        Anda akan menghapus staff berikut dari sistem:
                    </p>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800/50 mb-4 flex items-center">
                        <div class="bg-yellow-100 dark:bg-yellow-800/50 rounded-full p-3 mr-3">
                            <i class="fas fa-user text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-white text-lg">
                                <?= htmlspecialchars($nama_staff) ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center">
                                    <i class="fas fa-id-card mr-1"></i> ID: <?= $id_staff ?>
                                </span>
                                <span class="mx-2">•</span>
                                <span class="inline-flex items-center">
                                    <i class="fas fa-building mr-1"></i> <?= htmlspecialchars($divisi_nama) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <p class="text-red-600 dark:text-red-400 font-medium flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Tindakan ini tidak dapat dibatalkan!
                    </p>
                </div>

                <form method="POST" onsubmit="return confirmDelete()">
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <a href="<?= $back_url ?>" 
                           class="bg-gray-500 dark:bg-gray-600 text-white px-4 py-3 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>
                            Batal
                        </a>
                        
                        <button type="submit" name="confirm_delete" value="yes" 
                                class="bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Hapus Staff
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="<?= $edit_url ?>" 
                       class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition flex items-center justify-center">
                        <i class="fas fa-pen mr-2"></i>
                        Edit Staff Ini
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            <button id="darkModeToggle" class="inline-flex items-center text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                <i class="fas fa-moon mr-2 dark:hidden"></i>
                <i class="fas fa-sun mr-2 hidden dark:inline"></i>
                <span class="dark:hidden">Mode Gelap</span>
                <span class="hidden dark:inline">Mode Terang</span>
            </button>
        </div>
    </div>
    
    <script>
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus staff "<?= htmlspecialchars($nama_staff) ?>"? Tindakan ini tidak dapat dibatalkan.');
        }

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