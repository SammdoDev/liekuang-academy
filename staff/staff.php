<?php
include '../koneksi.php';

// Password untuk mengakses dan input nilai - bisa dibuat per cabang
$access_password = "skillmatrix123"; // Default password

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

// Variabel untuk cek status otentikasi
$authenticated = false;
$auth_message = "";

// Handle form login password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_submit'])) {
    // Verifikasi password sesuai dengan cabang_id
    $branch_password = getPasswordForBranch($conn, $cabang_id);

    if ($_POST['password'] === $branch_password) {
        // Set session untuk autentikasi
        session_start();
        $_SESSION['authenticated'] = true;
        $_SESSION['cabang_id'] = $cabang_id; // Simpan cabang_id dalam session
        $authenticated = true;
        $auth_message = "Autentikasi berhasil!";
    } else {
        $auth_message = "Password salah, silakan coba lagi.";
    }
} else {
    // Cek apakah sudah login sebelumnya dan cabang_id sama
    session_start();
    if (
        isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true &&
        isset($_SESSION['cabang_id']) && $_SESSION['cabang_id'] == $cabang_id
    ) {
        $authenticated = true;
    }
}

// Fungsi untuk mendapatkan password berdasarkan cabang
function getPasswordForBranch($conn, $cabang_id)
{
    // Di sini bisa diterapkan logic untuk mengambil password khusus per cabang dari database
    // Untuk sementara kita gunakan password default
    return "skillmatrix123";

    // Implementasi dengan database bisa seperti ini:
    /*
    $query = $conn->prepare("SELECT password FROM cabang WHERE id_cabang = ?");
    $query->bind_param("i", $cabang_id);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return $data['password'];
    }
    return "skillmatrix123"; // Password default
    */
}

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

// Logout functionality
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_start();
    unset($_SESSION['authenticated']);
    unset($_SESSION['cabang_id']);
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF'] . "?skill_id=" . $skill_id . "&divisi_id=" . $divisi_id . "&cabang_id=" . $cabang_id);
    exit;
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
// Gunakan created_at untuk mengurutkan staff (paling lama dulu)
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
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 bg-white dark:bg-gray-800 shadow-md lg:sticky lg:top-0 lg:h-screen">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Skill Matrix</h2>
                <button id="mobile-menu-button"
                    class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav id="sidebar-menu" class="hidden lg:block p-6 space-y-4">
                <a href="../skill/skill.php"
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

                <?php if ($authenticated): ?>
                    <div
                        class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg border border-green-100 dark:border-green-800/30">
                        <div class="flex items-center text-green-800 dark:text-green-400">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="font-medium">Autentikasi Aktif</span>
                        </div>
                        <div class="mt-2 flex justify-end">
                            <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>&logout=true"
                                class="text-red-600 hover:text-red-800 text-sm flex items-center">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../index.php"
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
                                    class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition flex items-center gap-2">
                                    <i class="fas fa-times"></i>
                                    <span>Reset</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Staff List: Card View -->
            <?php if (count($staffData) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $counter = 1;
                    foreach ($staffData as $staff):
                        // Format nilai untuk tampilan
                        $avg_total = number_format($staff['avg_total'], 1);
                        $ratingClass = "";
                        if ($staff['avg_total'] >= 3.5) {
                            $ratingClass = "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300";
                        } else if ($staff['avg_total'] >= 2.5) {
                            $ratingClass = "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300";
                        } else {
                            $ratingClass = "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300";
                        }
                        ?>
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border border-gray-200 dark:border-gray-700 transition duration-300 hover-card">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="rounded-full bg-primary-100 dark:bg-primary-900/30 p-3">
                                        <i class="fas fa-user text-primary-600 dark:text-primary-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($staff['nama_staff']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Staff #<?= $counter++ ?></p>
                                    </div>
                                </div>
                                <div class="<?= $ratingClass ?> px-3 py-1 rounded-full text-sm font-bold">
                                    <?= $avg_total ?>
                                </div>
                            </div>

                            <div class="space-y-3 mt-4">
                                <?php if (!$authenticated): ?>
                                    <!-- Tombol input nilai dengan popup password jika belum login -->
                                    <button onclick="showPasswordModal(<?= $staff['id_staff'] ?>)"
                                        class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-2">
                                        <i class="fas fa-clipboard-list"></i>
                                        <span>Input Nilai</span>
                                    </button>
                                <?php else: ?>
                                    <!-- Link input nilai jika sudah login -->
                                    <a href="../skill_matrix/skill_matrix.php?id_staff=<?= $staff['id_staff'] ?>&id_skill=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>"
                                        class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-2">
                                        <i class="fas fa-clipboard-list"></i>
                                        <span>Input Nilai</span>
                                    </a>
                                <?php endif; ?>

                                <div class="flex gap-2">
                                    <a href="edit_staff.php?id_staff=<?= $staff['id_staff'] ?>&skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>"
                                        class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center justify-center gap-1">
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </a>
                                    <a href="hapus_staff.php?id_staff=<?= $staff['id_staff'] ?>&skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&confirm=true"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus staff ini?');"
                                        class="flex-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 px-3 py-2 rounded-lg hover:bg-red-200 dark:hover:bg-red-800/50 transition flex items-center justify-center gap-1">
                                        <i class="fas fa-trash"></i>
                                        <span>Hapus</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-8 text-center border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full mb-4">
                            <i class="fas fa-users text-4xl text-gray-400"></i>
                        </div>
                        <?php if (!empty($search)): ?>
                            <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Tidak ada hasil</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Tidak ada staff yang cocok dengan pencarian
                                "<?= htmlspecialchars($search) ?>"</p>
                            <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                                class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition flex items-center gap-2">
                                <i class="fas fa-arrow-left"></i>
                                <span>Kembali ke semua staff</span>
                            </a>
                        <?php else: ?>
                            <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-2">Belum ada staff</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Tambahkan staff baru untuk skill ini</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fitur tambahan: Grafik dan statistik -->
            <?php if (count($staffData) > 0): ?>
                <div
                    class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-chart-pie mr-2 text-primary-500"></i>Distribusi Rating Skill
                    </h2>
                    <div class="flex flex-wrap justify-around gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg flex-1 text-center">
                            <div class="text-4xl font-bold text-green-600 dark:text-green-400">
                                <?php
                                $highSkill = 0;
                                foreach ($staffData as $staff) {
                                    if ($staff['avg_total'] >= 3.5)
                                        $highSkill++;
                                }
                                echo $highSkill;
                                ?>
                            </div>
                            <p class="text-green-800 dark:text-green-300 mt-1">Nilai Tinggi (≥3.5)</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg flex-1 text-center">
                            <div class="text-4xl font-bold text-yellow-600 dark:text-yellow-400">
                                <?php
                                $mediumSkill = 0;
                                foreach ($staffData as $staff) {
                                    if ($staff['avg_total'] >= 2.5 && $staff['avg_total'] < 3.5)
                                        $mediumSkill++;
                                }
                                echo $mediumSkill;
                                ?>
                            </div>
                            <p class="text-yellow-800 dark:text-yellow-300 mt-1">Nilai Sedang (2.5-3.4)</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg flex-1 text-center">
                            <div class="text-4xl font-bold text-red-600 dark:text-red-400">
                                <?php
                                $lowSkill = 0;
                                foreach ($staffData as $staff) {
                                    if ($staff['avg_total'] < 2.5)
                                        $lowSkill++;
                                }
                                echo $lowSkill;
                                ?>
                            </div>
                            <p class="text-red-800 dark:text-red-300 mt-1">Nilai Rendah (<2.5)< /p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Password untuk Input Nilai -->
    <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md animate-fadeIn">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-800 dark:text-white">Login untuk Input Nilai</h3>
                <button onclick="hidePasswordModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mb-4 text-gray-600 dark:text-gray-400">Masukkan password untuk input nilai staff cabang
                <?= htmlspecialchars($nama_cabang) ?>.
            </p>
            <form method="POST"
                action="<?= $_SERVER['PHP_SELF'] ?>?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>"
                id="passwordForm">
                <input type="hidden" name="redirect_staff_id" id="redirect_staff_id">
                <div class="mb-4">
                    <div class="relative">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="admin_password" placeholder="Masukkan password"
                            required
                            class="pl-10 pr-4 py-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <p id="passwordError" class="text-red-600 text-sm mt-1 hidden">Password tidak boleh kosong</p>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="hidePasswordModal()"
                        class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition mr-2">
                        Batal
                    </button>
                    <button type="submit" name="password_submit"
                        class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebarMenu = document.getElementById('sidebar-menu');

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function () {
                    sidebarMenu.classList.toggle('hidden');
                });
            }

            // Dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');

            // Check for saved theme preference or use system preference
            if (localStorage.getItem('darkMode') === 'true' ||
                (!localStorage.getItem('darkMode') &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function () {
                    document.documentElement.classList.toggle('dark');
                    localStorage.setItem('darkMode',
                        document.documentElement.classList.contains('dark') ? 'true' : 'false');
                });
            }

            // Update icon in dark mode toggle button
            function updateDarkModeIcon() {
                const icon = darkModeToggle.querySelector('i');
                if (document.documentElement.classList.contains('dark')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    darkModeToggle.querySelector('span').textContent = 'Mode Terang';
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    darkModeToggle.querySelector('span').textContent = 'Mode Gelap';
                }
            }

            if (darkModeToggle) {
                updateDarkModeIcon();
                darkModeToggle.addEventListener('click', updateDarkModeIcon);
            }

            // Auto-hide notifications after 5 seconds
            const notifications = document.querySelectorAll('.animate-fadeIn');
            if (notifications.length > 0) {
                setTimeout(() => {
                    notifications.forEach(notification => {
                        notification.style.opacity = '0';
                        notification.style.transform = 'translateY(-10px)';
                        notification.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                        setTimeout(() => notification.remove(), 500);
                    });
                }, 5000);
            }
        });

        // Password modal functions
        function showPasswordModal(staffId) {
            document.getElementById('passwordModal').classList.remove('hidden');
            document.getElementById('redirect_staff_id').value = staffId;
            document.getElementById('admin_password').focus();
        }

        function hidePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
            document.getElementById('admin_password').value = '';
            document.getElementById('passwordError').classList.add('hidden');
        }

        // Form validation
        document.getElementById('passwordForm')?.addEventListener('submit', function (event) {
            const password = document.getElementById('admin_password').value;
            if (!password.trim()) {
                event.preventDefault();
                document.getElementById('passwordError').classList.remove('hidden');
            }
        });

        // Close modal when clicking outside
        document.getElementById('passwordModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                hidePasswordModal();
            }
        });

        // Handle escape key to close modal
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !document.getElementById('passwordModal')?.classList.contains('hidden')) {
                hidePasswordModal();
            }
        });
    </script>
</body>

</html>