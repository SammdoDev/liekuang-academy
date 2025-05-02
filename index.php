<?php
include 'koneksi.php';
include 'table.php';
session_start();

// Ambil daftar cabang dan urutkan berdasarkan ID
$queryCabang = "SELECT * FROM cabang ORDER BY id_cabang ASC";
$resultCabang = $conn->query($queryCabang);

// Hitung jumlah cabang
$jumlahCabang = $resultCabang->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cabang</title>
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
                <a href="index.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 transition group">
                    <i class="fas fa-home mr-3 text-primary-500"></i>
                    <span>Home</span>
                </a>
                <a href="staff/staff.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-users mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Staff</span>
                </a>
                <a href="divisi/divisi.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
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
                <a href="logout.php" class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-600 dark:hover:text-red-400 mt-2 transition">
                    <i class="fas fa-sign-out-alt mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Konten -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Daftar Cabang</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        <i class="fas fa-building mr-2"></i>
                        Total: <?= $jumlahCabang ?> cabang
                    </p>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="searchCabang" placeholder="Cari cabang..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <?php if ($jumlahCabang > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    // Reset pointer to the beginning
                    $resultCabang->data_seek(0);
                    while ($cabang = $resultCabang->fetch_assoc()): 
                    ?>
                        <div class="cabang-card p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300 flex flex-col">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                                        <?= htmlspecialchars($cabang['nama_cabang']) ?>
                                    </h2>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                        ID: <?= $cabang['id_cabang'] ?>
                                    </p>
                                </div>
                                <div class="dropdown relative">
                                    <button class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                        <a href="edit_cabang.php?id=<?= $cabang['id_cabang'] ?>" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-edit mr-2"></i> Edit
                                        </a>
                                        <a href="hapus_cabang.php?id=<?= $cabang['id_cabang'] ?>" onclick="return confirm('Anda yakin ingin menghapus cabang ini?')" 
                                           class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-trash-alt mr-2"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-auto pt-4">
                                <button onclick="lihatModal(<?= $cabang['id_cabang'] ?>, '<?= htmlspecialchars($cabang['nama_cabang']) ?>')" 
                                       class="w-full bg-primary-600 text-white px-4 py-3 rounded-lg hover:bg-primary-700 transition flex items-center justify-center">
                                      <i class="fas fa-eye mr-2"></i> Lihat Divisi
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full mb-4">
                            <i class="fas fa-building text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Belum ada cabang</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Tambahkan cabang baru untuk memulai</p>
                        <a href="tambah_cabang.php" 
                           class="inline-flex items-center bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Tambah Cabang Pertama
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal untuk password Lihat -->
    <div id="lihatPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white" id="lihatModalTitle">Lihat Divisi Cabang</h3>
                <button onclick="closeModal('lihatPasswordModal')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="lihatPasswordForm" method="post" action="lihat_divisi.php">
                <input type="hidden" id="lihatCabangId" name="cabang_id">
                <div class="mb-4">
                    <label for="lihatPassword" class="block text-gray-700 dark:text-gray-300 mb-2">Masukkan Password:</label>
                    <input type="password" id="lihatPassword" name="password" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('lihatPasswordModal')" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg mr-2 hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function lihatModal(id, nama) {
            document.getElementById('lihatCabangId').value = id;
            document.getElementById('lihatModalTitle').innerText = 'Lihat Divisi Cabang: ' + nama;
            document.getElementById('lihatPasswordModal').classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            if (modalId === 'lihatPasswordModal') {
                document.getElementById('lihatPassword').value = '';
            }
        }
        
        // Tutup modal jika user mengklik di luar modal
        window.onclick = function(event) {
            let viewModal = document.getElementById('lihatPasswordModal');
            
            if (event.target === viewModal) {
                closeModal('lihatPasswordModal');
            }
        }
        
        // Dropdown toggle
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const btn = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('searchCabang');
        const cabangCards = document.querySelectorAll('.cabang-card');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            
            cabangCards.forEach(card => {
                const cabangName = card.querySelector('h2').textContent.toLowerCase();
                const cabangId = card.querySelector('p').textContent.toLowerCase();
                
                if (cabangName.includes(searchTerm) || cabangId.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
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