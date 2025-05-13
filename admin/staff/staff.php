<?php
include '../../koneksi.php';

// Password akan diambil dari divisi

// Validasi parameter 
if (!isset($_GET['skill_id']) || empty($_GET['skill_id'])) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Parameter skill_id tidak ditemukan!
        <br><a href="../divisi/divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

if (!isset($_GET['divisi_id']) || empty($_GET['divisi_id'])) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Parameter divisi_id tidak ditemukan!
        <br><a href="../divisi/divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

$skill_id = intval($_GET['skill_id']);
$divisi_id = intval($_GET['divisi_id']);
$cabang_id = isset($_GET['cabang_id']) ? intval($_GET['cabang_id']) : 0;

// Variabel pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil informasi skill dan divisi
$infoQuery = $conn->prepare("
    SELECT s.nama_skill, d.nama_divisi, c.nama_cabang, d.id_cabang 
    FROM skill s
    JOIN divisi d ON s.id_divisi = d.id_divisi
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE s.id_skill = ? AND s.id_divisi = ?
");
$infoQuery->bind_param("ii", $skill_id, $divisi_id);
$infoQuery->execute();
$result = $infoQuery->get_result();

if ($result->num_rows === 0) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Skill atau Divisi tidak ditemukan!
        <br><a href="../divisi/divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

$infoData = $result->fetch_assoc();
$nama_skill = $infoData['nama_skill'];
$nama_divisi = $infoData['nama_divisi'];
$nama_cabang = $infoData['nama_cabang'];
// Pastikan cabang_id diambil dari database jika tidak ada di GET parameter
if ($cabang_id == 0) {
    $cabang_id = $infoData['id_cabang'];
}

// Definisikan getColorClass function
function getColorClass($value)
{
    if ($value >= 3.5)
        return "bg-green-100 text-green-800";
    if ($value >= 2.5)
        return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
}

// Proses penambahan staff jika ada data POST yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_staff']) && !empty($_POST['nama_staff'])) {
    $nama_staff = trim($_POST['nama_staff']);

    // Cek apakah staff dengan nama yang sama sudah ada
    $cekStaff = $conn->prepare("SELECT id_staff FROM staff WHERE nama_staff = ? AND id_divisi = ?");
    $cekStaff->bind_param("si", $nama_staff, $divisi_id);
    $cekStaff->execute();
    $cekResult = $cekStaff->get_result();

    if ($cekResult->num_rows === 0) {
        // Staff belum ada, tambahkan dengan skill_id yang sesuai
        $tambahStaff = $conn->prepare("INSERT INTO staff (nama_staff, id_skill, id_divisi, id_cabang) VALUES (?, ?, ?, ?)");
        $tambahStaff->bind_param("siii", $nama_staff, $skill_id, $divisi_id, $cabang_id);

        if ($tambahStaff->execute()) {
            // Redirect ke halaman yang sama tanpa POST data (untuk mencegah re-submit saat refresh)
            header("Location: " . $_SERVER['PHP_SELF'] . "?skill_id=" . $skill_id . "&divisi_id=" . $divisi_id . "&cabang_id=" . $cabang_id . "&added=success&staff_name=" . urlencode($nama_staff));
            exit;
        } else {
            $error_message = "Gagal menambahkan staff: " . $conn->error;
        }
    } else {
        $error_message = "Staff dengan nama tersebut sudah ada di divisi ini.";
    }
}


// Get staff count untuk skill ini
$countQuery = $conn->prepare("SELECT COUNT(*) as total FROM staff WHERE id_skill = ? AND id_divisi = ?");
$countQuery->bind_param("ii", $skill_id, $divisi_id);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();
$staffCount = $countResult['total'];

// Persiapkan query untuk pencarian staff
$searchCondition = "";
$searchParams = [];
$paramTypes = "";

if (!empty($search)) {
    $searchCondition = " AND s.nama_staff LIKE ? ";
    $searchParams[] = "%$search%";
    $paramTypes .= "s";
}

// Query untuk mengambil data staff beserta nilai rata-rata 
$query = "
    SELECT 
        s.id_staff, 
        s.nama_staff,
        COALESCE(
            (SELECT AVG(sm.rata_rata) FROM skill_matrix sm WHERE sm.id_staff = s.id_staff AND sm.id_skill = ?), 
            0
        ) as avg_total
    FROM staff s
    WHERE s.id_skill = ? AND s.id_divisi = ? $searchCondition
    ORDER BY s.nama_staff ASC
";

$staffQuery = $conn->prepare($query);

// Binding parameters
if (!empty($search)) {
    $staffQuery->bind_param("iii" . $paramTypes, $skill_id, $skill_id, $divisi_id, ...$searchParams);
} else {
    $staffQuery->bind_param("iii", $skill_id, $skill_id, $divisi_id);
}

$staffQuery->execute();
$staffResult = $staffQuery->get_result();

// Get average rating for this skill
$avgQuery = $conn->prepare("
    SELECT COALESCE(AVG(sm.rata_rata), 0) as avg_skill
    FROM skill_matrix sm 
    WHERE sm.id_skill = ?
");
$avgQuery->bind_param("i", $skill_id);
$avgQuery->execute();
$avgResult = $avgQuery->get_result()->fetch_assoc();
$avg_skill = number_format($avgResult['avg_skill'], 1);

// Store staff data in array for caching
$staffData = [];
if ($staffResult && $staffResult->num_rows > 0) {
    while ($row = $staffResult->fetch_assoc()) {
        $staffData[] = [
            'id_staff' => $row['id_staff'],
            'nama_staff' => $row['nama_staff'],
            'avg_total' => $row['avg_total']
        ];
    }
}

// Fungsi untuk mendapatkan detail skill matrix berdasarkan staff dan skill
function getSkillMatrixDetails($conn, $staff_id, $skill_id)
{
    $query = $conn->prepare("
        SELECT id_skill_matrix, id_staff, id_skill, total_look, konsultasi_komunikasi, 
               teknik, kerapian_kebersihan, produk_knowledge, rata_rata, catatan 
        FROM skill_matrix 
        WHERE id_staff = ? AND id_skill = ? 
    ");
    $query->bind_param("ii", $staff_id, $skill_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Skill Matrix</title>
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
    <style>
        ::-webkit-scrollbar {
            display: none;
        }

        aside {
            overflow-y: auto;
        }

        .rating-value {
            min-width: 3.5rem;
            display: inline-block;
            text-align: center;
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        @media (min-width: 1024px) {
            aside.lg\:h-screen {
                display: flex;
                flex-direction: column;
            }

            aside .overflow-y-auto {
                height: 100%;
                scrollbar-width: thin;
                scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
            }

            aside .overflow-y-auto::-webkit-scrollbar {
                width: 4px;
            }

            aside .overflow-y-auto::-webkit-scrollbar-track {
                background: transparent;
            }

            aside .overflow-y-auto::-webkit-scrollbar-thumb {
                background-color: rgba(156, 163, 175, 0.5);
                border-radius: 20px;
            }
        }

        .dropdown {
            position: relative;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            left: 0;
            /* Change from right to left */
            min-width: 160px;
            z-index: 1000;
            /* High z-index to ensure it's on top */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .dropdown-content.show {
            display: block;
        }

        /* This will make sure the dropdown remains visible when clicked */
        .dropdown-active .dropdown-content {
            display: block;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 bg-white dark:bg-gray-800 shadow-md lg:sticky lg:top-0 lg:h-screen">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Skill Staff</h2>
                <button id="mobile-menu-button"
                    class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav id="sidebar-menu" class="hidden lg:block p-6 space-y-4">
                <a href="../skill/skill.php?divisi_id=<?= $divisi_id ?>"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-arrow-left mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Kembali ke Daftar Skill</span>
                </a>

                <div
                    class="bg-primary-50 dark:bg-gray-700 p-4 rounded-lg border border-primary-100 dark:border-gray-600">
                    <h3 class="font-medium text-primary-800 dark:text-white mb-2">Info Skill</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-start">
                            <i class="fas fa-star text-primary-400 dark:text-primary-300 mt-1 mr-2"></i>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Skill:</p>
                                <p class="font-medium text-gray-800 dark:text-white">
                                    <?= htmlspecialchars($nama_skill) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-layer-group text-primary-400 dark:text-primary-300 mt-1 mr-2"></i>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Divisi:</p>
                                <p class="font-medium text-gray-800 dark:text-white">
                                    <?= htmlspecialchars($nama_divisi) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-building text-primary-400 dark:text-primary-300 mt-1 mr-2"></i>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Cabang:</p>
                                <p class="font-medium text-gray-800 dark:text-white">
                                    <?= htmlspecialchars($nama_cabang) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-users text-primary-400 dark:text-primary-300 mt-1 mr-2"></i>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Jumlah Staff:</p>
                                <p class="font-medium text-gray-800 dark:text-white"><?= $staffCount ?> Orang</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-chart-line text-primary-400 dark:text-primary-300 mt-1 mr-2"></i>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Rata-rata Skill:</p>
                                <p class="font-medium text-gray-800 dark:text-white"><?= $avg_skill ?></p>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../cabang.php"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Home</span>
                    </a>
                    <a href="../divisi/divisi.php?cabang_id=<?= $cabang_id ?>"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i
                            class="fas fa-sitemap mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Daftar Divisi</span>
                    </a>
                </div>
            </nav>

            <div class="p-6 mt-auto border-t border-gray-200 dark:border-gray-700 hidden lg:block">
                <button id="darkModeToggle"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-moon mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>Mode Gelap</span>
                </button>
            </div>
        </aside>

        <!-- Konten -->
        <main class="flex-1 p-4 lg:p-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md mb-6 overflow-hidden">
                <div class="bg-primary-600 p-6 text-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">Staff - <?= htmlspecialchars($nama_skill) ?></h1>
                            <p class="text-primary-100 mt-1">
                                Mengelola data staff untuk skill <?= htmlspecialchars($nama_skill) ?> di divisi
                                <?= htmlspecialchars($nama_divisi) ?>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0 flex gap-2">
                            <span
                                class="bg-white text-primary-600 text-sm font-medium px-3 py-1 rounded-full flex items-center">
                                <i class="fas fa-users mr-1"></i> <?= $staffCount ?> Staff
                            </span>
                            <span
                                class="bg-white text-primary-600 text-sm font-medium px-3 py-1 rounded-full flex items-center">
                                <i class="fas fa-chart-line mr-1"></i> AVG: <?= $avg_skill ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Tambah Staff -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-plus-circle mr-2 text-primary-500"></i>Tambah Staff Baru
                    </h3>
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-lg mb-3">
                            <p class="text-red-800 dark:text-red-400"><?= htmlspecialchars($error_message) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST"
                        action="<?= $_SERVER['PHP_SELF'] ?>?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                        class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="relative flex-grow w-full">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="nama_staff" placeholder="Nama Staff" required
                                class="pl-10 pr-4 py-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <button type="submit" name="tambah_staff"
                            class="bg-primary-600 text-white px-6 rounded-lg hover:bg-primary-700 transition w-full sm:w-auto flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            <span>Tambah Staff</span>
                        </button>
                    </form>
                </div>

                <!-- Notification Messages -->
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
                    <div class="bg-green-100 dark:bg-green-900/30 p-4 animate-fadeIn">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2"></i>
                            <p class="text-green-800 dark:text-green-300">
                                Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil dihapus
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
                    <div class="bg-green-100 dark:bg-green-900/30 p-4 animate-fadeIn">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2"></i>
                            <p class="text-green-800 dark:text-green-300">
                                Data staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil
                                diperbarui
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
                    <div class="bg-green-100 dark:bg-green-900/30 p-4 animate-fadeIn">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2"></i>
                            <p class="text-green-800 dark:text-green-300">
                                Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'baru')) ?> berhasil ditambahkan
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <form method="GET" action=""
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <input type="hidden" name="skill_id" value="<?= $skill_id ?>">
                        <input type="hidden" name="divisi_id" value="<?= $divisi_id ?>">
                        <input type="hidden" name="cabang_id" value="<?= $cabang_id ?>">

                        <div class="relative flex-grow">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Cari nama staff..."
                                class="pl-10 pr-4 py-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition flex items-center gap-2">
                                <i class="fas fa-search"></i>
                                <span>Cari</span>
                            </button>

                            <?php if (!empty($search)): ?>
                                <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                                    class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-white px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition flex items-center gap-2">
                                    <i class="fas fa-times"></i>
                                    <span>Reset</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Staff Data Table -->
                <div class="p-6">
                    <?php if (empty($staffData)): ?>
                        <div
                            class="flex flex-col items-center justify-center p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="mb-4 text-gray-400 dark:text-gray-500">
                                <i class="fas fa-user-slash text-5xl"></i>
                            </div>
                            <?php if (!empty($search)): ?>
                                <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-2">Tidak ada staff ditemukan
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada staff yang cocok dengan pencarian
                                    "<?= htmlspecialchars($search) ?>"</p>
                            <?php else: ?>
                                <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-2">Belum ada staff terdaftar
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400">Tambahkan staff baru untuk skill ini menggunakan
                                    form di atas</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-700 text-left">
                                        <th class="px-4 py-3 text-gray-700 dark:text-gray-300">No</th>
                                        <th class="px-4 py-3 text-gray-700 dark:text-gray-300">Nama Staff</th>
                                        <th class="px-4 py-3 text-gray-700 dark:text-gray-300">Rata-rata</th>
                                        <th class="px-4 py-3 text-gray-700 dark:text-gray-300">Status</th>
                                        <th class="px-4 py-3 text-gray-700 dark:text-gray-300">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($staffData as $staff): ?>
                                        <?php
                                        $avg = number_format($staff['avg_total'], 1);
                                        $colorClass = getColorClass($staff['avg_total']);
                                        $statusText = "Belum Diisi";

                                        if ($staff['avg_total'] > 0) {
                                            $statusText = $staff['avg_total'] >= 3.5 ? "Baik" : ($staff['avg_total'] >= 2.5 ? "Cukup" : "Kurang");
                                        }

                                        // Get skill matrix details
                                        $skillMatrixDetails = getSkillMatrixDetails($conn, $staff['id_staff'], $skill_id);
                                        ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-3 text-gray-800 dark:text-gray-300"><?= $no++ ?></td>
                                            <td class="px-4 py-3 text-gray-800 dark:text-gray-300 font-medium">
                                                <div class="flex items-center">
                                                    <span><?= htmlspecialchars($staff['nama_staff']) ?></span>

                                                    <?php if ($skillMatrixDetails): ?>
                                                        <button class="ml-2 text-blue-500 hover:text-blue-700 toggle-details"
                                                            data-staff-id="<?= $staff['id_staff'] ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="<?= $colorClass ?> text-sm font-medium px-2.5 py-0.5 rounded">
                                                    <?= $avg ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if ($staff['avg_total'] <= 0): ?>
                                                    <span
                                                        class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 text-sm font-medium px-2.5 py-0.5 rounded">
                                                        <?= $statusText ?>
                                                    </span>
                                                <?php elseif ($staff['avg_total'] >= 3.5): ?>
                                                    <span
                                                        class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                                        <?= $statusText ?>
                                                    </span>
                                                <?php elseif ($staff['avg_total'] >= 2.5): ?>
                                                    <span
                                                        class="bg-yellow-100 text-yellow-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                                        <?= $statusText ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                                        <?= $statusText ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 relative">
                                                <div class="flex items-center space-x-2">
                                                    <!-- Tombol Edit Data di sebelah kiri -->
                                                    <a href="edit_staff.php?id_staff=<?= $staff['id_staff'] ?>&skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                                                        class="inline-flex items-center bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-400 dark:hover:bg-blue-800 text-sm font-medium px-3 py-1 rounded-lg z-[50] transition whitespace-nowrap">
                                                        <i class="fas fa-edit mr-2"></i> Edit Data
                                                    </a>

                                                </div>
                                            </td>

                                        </tr>

                                        <!-- Skill Matrix Details Panel -->
                                        <?php if ($skillMatrixDetails): ?>
                                            <tr class="skill-details hidden" id="details-<?= $staff['id_staff'] ?>">
                                                <td colspan="5" class="bg-gray-50 dark:bg-gray-800 p-4">
                                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                                        <h4 class="font-medium text-gray-800 dark:text-white mb-3">Detail Penilaian
                                                            untuk <?= htmlspecialchars($staff['nama_staff']) ?></h4>

                                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">total look
                                                                </h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-blue-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['total_look'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= $skillMatrixDetails['total_look'] ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">konsultasi
                                                                    komunikasi</h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-green-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['konsultasi_komunikasi'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= $skillMatrixDetails['konsultasi_komunikasi'] ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">teknik
                                                                </h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-purple-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['teknik'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= $skillMatrixDetails['teknik'] ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">kerapian
                                                                    kebersihan</h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-yellow-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['kerapian_kebersihan'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= $skillMatrixDetails['kerapian_kebersihan'] ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">produk
                                                                    knowledge</h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-pink-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['produk_knowledge'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= $skillMatrixDetails['produk_knowledge'] ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                                <h5 class="text-gray-500 dark:text-gray-400 text-sm mb-1">Rata-rata
                                                                </h5>
                                                                <div class="flex items-center">
                                                                    <div class="flex-1">
                                                                        <div
                                                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                            <div class="bg-primary-500 h-2 rounded-full"
                                                                                style="width: <?= ($skillMatrixDetails['rata_rata'] / 4) * 100 ?>%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span
                                                                        class="ml-2 text-gray-800 dark:text-white font-medium"><?= number_format($skillMatrixDetails['rata_rata'], 1) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Updated Notes Section - Made more visible with improved styling -->
                                                        <div class="mt-4 bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm">
                                                            <h5 class="text-gray-700 dark:text-gray-300 font-medium mb-2">
                                                                <i class="fas fa-comment-alt mr-2 text-primary-500"></i>Catatan
                                                            </h5>
                                                            <?php if (!empty($skillMatrixDetails['catatan'])): ?>
                                                                <div
                                                                    class="bg-gray-50 dark:bg-gray-600 p-3 rounded border-l-4 border-primary-500">
                                                                    <p class="text-gray-800 dark:text-white whitespace-pre-line">
                                                                        <?= htmlspecialchars($skillMatrixDetails['catatan']) ?>
                                                                    </p>
                                                                </div>
                                                            <?php else: ?>
                                                                <p class="text-gray-500 dark:text-gray-400 italic">
                                                                    <i class="fas fa-info-circle mr-1"></i>Tidak ada catatan untuk staff
                                                                    ini
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>


    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            const sidebarMenu = document.getElementById('sidebar-menu');
            sidebarMenu.classList.toggle('hidden');
        });

        // Dark mode toggle
        document.getElementById('darkModeToggle').addEventListener('click', function () {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Get all toggle buttons for skill details
            const toggleButtons = document.querySelectorAll('.toggle-details');

            // Add click event to each button
            toggleButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const staffId = this.getAttribute('data-staff-id');
                    const detailsRow = document.getElementById('details-' + staffId);

                    // Toggle the visibility of the details row
                    if (detailsRow.classList.contains('hidden')) {
                        detailsRow.classList.remove('hidden');
                        detailsRow.classList.add('show');
                    } else {
                        detailsRow.classList.add('hidden');
                        detailsRow.classList.remove('show');
                    }
                });
            });

            // New code for dropdown toggle
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation(); // Prevent event from bubbling up

                    // Get the parent dropdown element
                    const dropdown = this.closest('.dropdown');
                    const dropdownContent = dropdown.querySelector('.dropdown-content');

                    // Close all other dropdowns first
                    document.querySelectorAll('.dropdown').forEach(d => {
                        if (d !== dropdown) {
                            d.classList.remove('dropdown-active');
                            d.querySelector('.dropdown-content').classList.remove('show');
                        }
                    });

                    // Toggle the current dropdown
                    dropdown.classList.toggle('dropdown-active');
                    dropdownContent.classList.toggle('show');
                });
            });

            // Close dropdowns when clicking elsewhere on the page
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('dropdown-active');
                        dropdown.querySelector('.dropdown-content')?.classList.remove('show');
                    });
                }
            });

            // Prevent dropdown from closing when clicking inside it
            document.querySelectorAll('.dropdown-content').forEach(content => {
                content.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });
        });

    </script>
</body>

</html>