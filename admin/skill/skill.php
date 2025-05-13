<?php
include '../../koneksi.php';
session_start();
$role = $_SESSION['role'];
$username = $_SESSION['username'];

if ($role !== 'admin') {
    header("Location: ../../unauthorized.php");
    exit;
}

if (!isset($_GET['divisi_id'])) {
    die("Divisi tidak ditemukan!");
}

$divisi_id = intval($_GET['divisi_id']);

// Get the division name and branch info
$divisi_query = $conn->prepare("
    SELECT d.nama_divisi, c.nama_cabang, c.id_cabang
    FROM divisi d
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE d.id_divisi = ?
");
$divisi_query->bind_param("i", $divisi_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();

if ($divisi_result->num_rows > 0) {
    $divisi_data = $divisi_result->fetch_assoc();
    $divisi_name = $divisi_data['nama_divisi'];
    $cabang_name = $divisi_data['nama_cabang'];
    $cabang_id = $divisi_data['id_cabang'];
} else {
    $divisi_name = 'Unknown Division';
    $divisi_desc = '';
    $cabang_name = 'Unknown Branch';
    $cabang_id = 0;
}

// Modified query to remove average rating calculation
$query = $conn->prepare("
    SELECT s.*, 
           COUNT(DISTINCT st.id_staff) as jumlah_staff
    FROM skill s
    LEFT JOIN staff st ON s.id_skill = st.id_skill
    WHERE s.id_divisi = ?
    GROUP BY s.id_skill
");
$query->bind_param("i", $divisi_id);
$query->execute();
$result = $query->get_result();

// Get the total staff count for this division
$staff_count_query = $conn->prepare("
    SELECT COUNT(*) as total
    FROM staff
    WHERE id_divisi = ?
");
$staff_count_query->bind_param("i", $divisi_id);
$staff_count_query->execute();
$staff_count_result = $staff_count_query->get_result();
$staff_count_data = $staff_count_result->fetch_assoc();
$total_staff = $staff_count_data['total'] ?? 0;

// Count total skills
$total_skills = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Skill - <?= htmlspecialchars($divisi_name) ?></title>
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
                <a href="../divisi/divisi.php?cabang_id=<?= $cabang_id ?>"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-arrow-left mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Kembali ke Divisi</span>
                </a>
                <a href="tambah_skill.php?divisi_id=<?= $divisi_id ?>"
                    class="flex items-center text-white bg-primary-600 px-4 py-3 rounded-lg shadow-md hover:bg-primary-700 transition">
                    <i class="fas fa-plus-circle mr-3"></i>
                    <span>Tambah Skill</span>
                </a>

                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../cabang/cabang.php"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Home</span>
                    </a>
                    <form action="../../logout.php" method="POST">
                        <button type="submit"
                            class="mt-4 w-full flex items-center justify-start text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-sign-out-alt mr-3"></i>
                            <span>Logout</span>
                        </button>
                    </form>

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
                    <div class="flex items-center space-x-3">
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                            <?= htmlspecialchars($divisi_name) ?>
                        </h1>
                        <span
                            class="bg-primary-100 text-primary-800 text-sm font-medium px-3 py-1 rounded-full dark:bg-primary-900 dark:text-primary-300">
                            <?= htmlspecialchars($cabang_name) ?>
                        </span>
                    </div>

                    <?php if (!empty($divisi_desc)): ?>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            <?= nl2br(htmlspecialchars($divisi_desc)) ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex items-center space-x-4 mt-3">
                        <p class="text-gray-600 dark:text-gray-400 flex items-center">
                            <i class="fas fa-users mr-2 text-primary-500"></i>
                            <?= $total_staff ?> staff
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 flex items-center">
                            <i class="fas fa-laptop-code mr-2 text-primary-500"></i>
                            <?= $total_skills ?> skills
                        </p>
                    </div>
                </div>

                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="searchSkill" placeholder="Cari skill..."
                            class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    // Reset pointer to the beginning
                    $result->data_seek(0);
                    while ($skill = $result->fetch_assoc()):
                        ?>
                        <div
                            class="skill-card p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300 flex flex-col">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                                        <?= html_entity_decode(htmlspecialchars($skill['nama_skill'], ENT_NOQUOTES)) ?>
                                    </h2>
                                    <div class="flex items-center mt-2">
                                        <span
                                            class="bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300 text-sm font-medium px-2.5 py-0.5 rounded-full flex items-center">
                                            <i class="fas fa-users text-xs mr-1.5"></i>
                                            <?= $skill['jumlah_staff'] ?> staff
                                        </span>
                                    </div>
                                </div>
                                <div class="dropdown relative">
                                    <button
                                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div
                                        class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                        <a href="edit_skill.php?id=<?= $skill['id_skill'] ?>&divisi_id=<?= $divisi_id ?>"
                                            class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-edit mr-2"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-4">
                                <a href="../staff/staff.php?skill_id=<?= $skill['id_skill'] ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                                    class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg text-center hover:bg-primary-700 transition flex items-center justify-center">
                                    <i class="fas fa-users mr-2"></i> Lihat Staff
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full mb-4">
                            <i class="fas fa-laptop-code text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Belum ada skill yang terdaftar
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Tambahkan skill baru untuk divisi ini</p>
                        <a href="tambah_skill.php?divisi_id=<?= $divisi_id ?>"
                            class="inline-flex items-center bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Tambah Skill Pertama
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
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
        const searchInput = document.getElementById('searchSkill');
        const skillCards = document.querySelectorAll('.skill-card');

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();

            skillCards.forEach(card => {
                const skillName = card.querySelector('h2').textContent.toLowerCase();

                if (skillName.includes(searchTerm)) {
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