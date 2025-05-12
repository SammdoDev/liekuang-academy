<?php
include '../koneksi.php';
session_start();

// Cek apakah id_cabang tersedia
if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

// Pastikan user punya akses ke cabang ini (dari session)
if (!isset($_SESSION['cabang_akses'][$cabang_id]) || $_SESSION['cabang_akses'][$cabang_id] !== true) {
    $_SESSION['error_message'] = "Anda belum memiliki akses ke cabang ini!";
    header("Location: ../cabang/cabang.php");
    exit();
}

// Ambil data cabang
$queryCabang = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id_cabang = ?");
$queryCabang->bind_param("i", $cabang_id);
$queryCabang->execute();
$resultCabang = $queryCabang->get_result();
$cabang = $resultCabang->fetch_assoc();

if (!$cabang) {
    die("Cabang tidak ditemukan!");
}

// Ambil data divisi untuk cabang ini
$queryDivisi = $conn->prepare("SELECT * FROM divisi WHERE id_cabang = ?");
$queryDivisi->bind_param("i", $cabang_id);
$queryDivisi->execute();
$resultDivisi = $queryDivisi->get_result();
$jumlahDivisi = $resultDivisi->num_rows;

// Akses divisi yang telah diberikan (session)
$divisi_access = $_SESSION['divisi_access'] ?? [];

// Proses jika user mengisi password divisi
$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['divisi_id']) && isset($_POST['password'])) {
    $divisi_id = intval($_POST['divisi_id']);
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT id_divisi FROM divisi WHERE id_divisi = ? AND password = ?");
    $query->bind_param("is", $divisi_id, $password);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        if (!in_array($divisi_id, $divisi_access)) {
            $divisi_access[] = $divisi_id;
            $_SESSION['divisi_access'] = $divisi_access;
        }
        $success_msg = "Akses divisi diberikan!";
    } else {
        $error_msg = "Password salah!";
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divisi - <?= htmlspecialchars($cabang['nama_cabang']) ?></title>
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
    <!-- Notifikasi -->
    <?php if ($error_msg): ?>
        <div id="error-alert"
            class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error_msg ?>
            <button onclick="document.getElementById('error-alert').style.display='none'" class="ml-4 text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <script>
            setTimeout(() => {
                document.getElementById('error-alert').style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div id="success-alert"
            class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $success_msg ?>
            <button onclick="document.getElementById('success-alert').style.display='none'" class="ml-4 text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <script>
            setTimeout(() => {
                document.getElementById('success-alert').style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Modal Password -->
    <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Masukkan Password</h3>
                <button onclick="closePasswordModal()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-4">Divisi ini dilindungi password. Masukkan password untuk
                mengakses.</p>

            <form id="passwordForm" method="POST" action="">
                <input type="hidden" id="divisiIdInput" name="divisi_id" value="">
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 dark:text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        required>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closePasswordModal()"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mr-2">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 bg-white dark:bg-gray-800 shadow-md">

            <nav class="p-6 space-y-4">
                <a href="../cabang/cabang.php"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-arrow-left mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Kembali ke Cabang</span>
                </a>
                <a href="tambah_divisi.php?cabang_id=<?= $cabang_id ?>"
                    class="flex items-center text-white bg-primary-600 px-4 py-3 rounded-lg shadow-md hover:bg-primary-700 transition">
                    <i class="fas fa-plus-circle mr-3"></i>
                    <span>Tambah Divisi</span>
                </a>


                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../cabang/cabang.php"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Home</span>
                    </a>
                    <a href="karyawan.php?cabang_id=<?php echo $cabang_id; ?>"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-users mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Karyawan</span>
                    </a>

                </div>
            </nav>

            <div class="p-6 mt-auto border-t border-gray-200 dark:border-gray-700">
                <button id="darkModeToggle"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-moon mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>Mode Gelap</span>
                </button>
            </div>
        </aside>

        <!-- Konten -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Divisi -
                        <?= htmlspecialchars($cabang['nama_cabang']) ?>
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        <i class="fas fa-layer-group mr-2"></i>
                        Total: <?= $jumlahDivisi ?> divisi
                    </p>
                </div>

                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="searchDivisi" placeholder="Cari divisi..."
                            class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <?php if ($resultDivisi->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    // Reset pointer to the beginning
                    $resultDivisi->data_seek(0);
                    while ($divisi = $resultDivisi->fetch_assoc()):
                        // Check if admin or has access to this divisi
                        $has_access = in_array($divisi['id_divisi'], $divisi_access);

                        $has_password = !empty($divisi['password']);
                        ?>
                        <div
                            class="divisi-card p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300 flex flex-col">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center">
                                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                                            <?= htmlspecialchars($divisi['nama_divisi']) ?>
                                        </h2>
                                        <?php if ($has_password): ?>
                                            <i class="fas fa-lock ml-2 text-gray-500 dark:text-gray-400"
                                                title="Dilindungi password"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($divisi['deskripsi'])): ?>
                                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">
                                            <?= nl2br(htmlspecialchars($divisi['deskripsi'] ?? 'Tidak ada deskripsi')) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm italic">
                                            Tidak ada deskripsi
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown relative">
                                    <button
                                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div
                                        class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                        <a href="edit_divisi.php?id=<?= $divisi['id_divisi'] ?>"
                                            class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-edit mr-2"></i> Edit
                                        </a>
                                        <a href="hapus_divisi.php?id=<?= $divisi['id_divisi'] ?>"
                                            onclick="return confirm('Anda yakin ingin menghapus divisi ini?')"
                                            class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-trash-alt mr-2"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-4">
                                <div class="flex space-x-2">
                                    <?php if ($has_access): ?>
                                        <a href="../skill/skill.php?divisi_id=<?= $divisi['id_divisi'] ?>"
                                            class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg text-center hover:bg-primary-700 transition flex items-center justify-center">
                                            <i class="fas fa-laptop-code mr-2"></i> Kelola Skill
                                        </a>
                                    <?php else: ?>
                                        <button onclick="showPasswordModal(<?= $divisi['id_divisi'] ?>)"
                                            class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg text-center hover:bg-gray-700 transition flex items-center justify-center">
                                            <i class="fas fa-lock mr-2"></i> Masukkan Password
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full mb-4">
                            <i class="fas fa-folder-open text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Belum ada divisi</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Tambahkan divisi baru untuk cabang ini</p>
                        <a href="tambah_divisi.php?cabang_id=<?= $cabang_id ?>"
                            class="inline-flex items-center bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Tambah Divisi Pertama
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Password modal functions
        function showPasswordModal(divisiId) {
            document.getElementById('divisiIdInput').value = divisiId;
            document.getElementById('passwordModal').classList.remove('hidden');
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
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
        const searchInput = document.getElementById('searchDivisi');
        const divisiCards = document.querySelectorAll('.divisi-card');

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();

            divisiCards.forEach(card => {
                const divisiName = card.querySelector('h2').textContent.toLowerCase();
                const divisiDesc = card.querySelector('p')?.textContent.toLowerCase() || '';

                if (divisiName.includes(searchTerm) || divisiDesc.includes(searchTerm)) {
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