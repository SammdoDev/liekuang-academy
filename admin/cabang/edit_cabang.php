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
if (!isset($_GET['id'])) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Parameter tidak valid. Diperlukan id cabang.</p>
        <a href="javascript:history.back()" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>');
}

$cabang_id = intval($_GET['id']);

// Ambil data cabang
$query = $conn->prepare("SELECT * FROM cabang WHERE id_cabang = ?");
$query->bind_param("i", $cabang_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Cabang tidak ditemukan.</p>
        <a href="cabang.php" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Cabang
        </a>
    </div>');
}

$cabang = $result->fetch_assoc();
$current_nama = $cabang['nama_cabang'];
$current_alamat = $cabang['alamat_cabang'] ?? '';
$current_telp = $cabang['telp_cabang'] ?? '';

// Hitung jumlah divisi di cabang ini
$divisi_query = $conn->prepare("SELECT COUNT(*) as total FROM divisi WHERE id_cabang = ?");
$divisi_query->bind_param("i", $cabang_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();
$divisi_count = $divisi_result->fetch_assoc()['total'] ?? 0;

// Proses Update
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['nama_cabang'])) {
        $message = "Nama cabang tidak boleh kosong!";
        $message_type = "warning";
    } else {
        $nama_cabang = sanitize_input($conn, $_POST['nama_cabang']);
        
        // Update data cabang
        $updateQuery = $conn->prepare("UPDATE cabang SET nama_cabang = ? WHERE id_cabang = ?");
        $updateQuery->bind_param("si", $nama_cabang, $cabang_id);
        
        
        if ($updateQuery->execute()) {
            $message = "Data cabang berhasil diperbarui!";
            $message_type = "success";
            
            // Perbarui nilai current untuk ditampilkan di form
            $current_nama = $nama_cabang;
            
            // Redirect jika diminta
            if (isset($_POST['save_and_return']) && $_POST['save_and_return'] == '1') {
                header("Location: cabang.php?updated=success&cabang_name=" . urlencode($nama_cabang));
                exit();
            }
        } else {
            $message = "Gagal memperbarui data: " . $updateQuery->error;
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cabang - <?= htmlspecialchars($current_nama) ?></title>
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
            <a href="cabang.php" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Kembali ke Daftar Cabang</span>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-building text-primary-600 dark:text-primary-400 mr-2"></i>
                    Edit Cabang
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    ID Cabang: <?= $cabang_id ?>
                </p>
            </div>

            <div class="p-6">
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-500 dark:border-blue-500/70">
                    <div class="flex items-center text-blue-800 dark:text-blue-300">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span class="font-medium">Informasi Cabang</span>
                    </div>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                        <div>
                            <span class="font-semibold">Jumlah Divisi:</span> 
                            <?= $divisi_count ?> divisi
                        </div>
                    </div>
                </div>
                
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

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="nama_cabang" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Cabang
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                <i class="fas fa-building"></i>
                            </span>
                            <input type="text" id="nama_cabang" name="nama_cabang" value="<?= htmlspecialchars($current_nama) ?>" required
                                class="pl-10 py-4 block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Masukkan nama cabang">
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nama cabang akan ditampilkan di semua sistem aplikasi
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <button type="submit" name="save" 
                                class="bg-primary-600 text-white px-4 py-3 rounded-lg hover:bg-primary-700 transition flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Perubahan
                        </button>
                        
                        <button type="submit" name="save_and_return" value="1" 
                                class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                            <i class="fas fa-check-double mr-2"></i>
                            Simpan & Kembali
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <a href="cabang.php" 
                       class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                    
                    <a href="hapus_cabang.php?id=<?= $cabang_id ?>" 
                       class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition flex items-center" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus cabang ini? Semua divisi, staff, dan skill yang terkait juga akan dihapus!')">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Hapus Cabang Ini
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
        // Script untuk konfirmasi jika ada perubahan yang belum disimpan
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const initialValues = {
                nama: document.getElementById('nama_cabang').value,
                alamat: document.getElementById('alamat_cabang').value,
                telp: document.getElementById('telp_cabang').value
            };
            
            let formChanged = false;
            
            form.addEventListener('change', function() {
                const currentValues = {
                    nama: document.getElementById('nama_cabang').value,
                    alamat: document.getElementById('alamat_cabang').value,
                    telp: document.getElementById('telp_cabang').value
                };
                
                formChanged = currentValues.nama !== initialValues.nama || 
                              currentValues.alamat !== initialValues.alamat ||
                              currentValues.telp !== initialValues.telp;
            });
            
            // Untuk link kembali dan hapus
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                if (!link.hasAttribute('onclick')) {
                    link.addEventListener('click', function(e) {
                        if (formChanged && !confirm('Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
                            e.preventDefault();
                        }
                    });
                }
            });
        });

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