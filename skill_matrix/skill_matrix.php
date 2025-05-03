<?php
include '../koneksi.php';

if (!isset($_GET['skill_id']) || !isset($_GET['divisi_id']) || !isset($_GET['cabang_id'])) {
    // Check if we're coming from staff page with staff ID
    if (isset($_GET['id_staff']) && isset($_GET['id_skill']) && isset($_GET['divisi_id'])) {
        $staff_id = intval($_GET['id_staff']);
        $skill_id = intval($_GET['id_skill']);
        $divisi_id = intval($_GET['divisi_id']);

        // Get cabang ID from divisi
        $cabang_query = $conn->prepare("SELECT id_cabang FROM divisi WHERE id_divisi = ?");
        $cabang_query->bind_param("i", $divisi_id);
        $cabang_query->execute();
        $cabang_result = $cabang_query->get_result();

        if ($cabang_result->num_rows > 0) {
            $cabang_data = $cabang_result->fetch_assoc();
            $cabang_id = $cabang_data['id_cabang'];
        } else {
            die("Divisi tidak ditemukan!");
        }
    } else {
        die("Data tidak lengkap!");
    }
} else {
    $skill_id = intval($_GET['skill_id']);
    $divisi_id = intval($_GET['divisi_id']);
    $cabang_id = intval($_GET['cabang_id']);
    $staff_id = isset($_GET['id_staff']) ? intval($_GET['id_staff']) : 0;
}

// Get skill information
$skill_query = $conn->prepare("
    SELECT nama_skill
    FROM skill
    WHERE id_skill = ?
");
$skill_query->bind_param("i", $skill_id);
$skill_query->execute();
$skill_result = $skill_query->get_result();

if ($skill_result->num_rows == 0) {
    die("Skill tidak ditemukan!");
}

$skill_data = $skill_result->fetch_assoc();
$skill_name = $skill_data['nama_skill'];

// Get division and branch information
$divisi_query = $conn->prepare("
    SELECT d.nama_divisi, c.nama_cabang
    FROM divisi d
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE d.id_divisi = ? AND c.id_cabang = ?
");
$divisi_query->bind_param("ii", $divisi_id, $cabang_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();

if ($divisi_result->num_rows == 0) {
    die("Divisi atau cabang tidak ditemukan!");
}

$divisi_data = $divisi_result->fetch_assoc();
$divisi_name = $divisi_data['nama_divisi'];
$cabang_name = $divisi_data['nama_cabang'];

// Get staff from this division with specific staff ID filter if provided
$staff_query_sql = "
    SELECT id_staff, nama_staff
    FROM staff
    WHERE id_divisi = ?
";

// Add staff_id filter if provided
if ($staff_id > 0) {
    $staff_query = $conn->prepare("
        SELECT id_staff, nama_staff
        FROM staff
        WHERE id_divisi = ? AND id_staff = ?
    ");
    $staff_query->bind_param("ii", $divisi_id, $staff_id);
} else {
    die("ID staff wajib disertakan!");
}

$staff_query->execute();
$staff_result = $staff_query->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    foreach ($_POST['staff'] as $current_staff_id => $values) {
        $total_look = floatval($values['total_look']);
        $konsultasi_komunikasi = floatval($values['konsultasi_komunikasi']);
        $teknik = floatval($values['teknik']);
        $kerapian_kebersihan = floatval($values['kerapian_kebersihan']);
        $produk_knowledge = floatval($values['produk_knowledge']);

        // Check if record exists
        $check_query = $conn->prepare("
            SELECT id_skill_matrix
            FROM skill_matrix
            WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
        ");
        $check_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $current_staff_id);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing record - removed rata_rata from the query since it's a generated column
            $update_query = $conn->prepare("
                UPDATE skill_matrix
                SET total_look = ?,
                    konsultasi_komunikasi = ?,
                    teknik = ?,
                    kerapian_kebersihan = ?,
                    produk_knowledge = ?
                WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
            ");
            // 5 nilai double, 4 nilai integer → total 9
            $update_query->bind_param(
                "dddddiiii",
                $total_look,
                $konsultasi_komunikasi,
                $teknik,
                $kerapian_kebersihan,
                $produk_knowledge,
                $skill_id,
                $divisi_id,
                $cabang_id,
                $current_staff_id
            );

            $update_query->execute();
        } else {
            // Insert new record - removed rata_rata from the query since it's a generated column
            $insert_query = $conn->prepare("
                INSERT INTO skill_matrix (
                    id_skill, id_divisi, id_cabang, id_staff, 
                    total_look, konsultasi_komunikasi, teknik, 
                    kerapian_kebersihan, produk_knowledge
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_query->bind_param(
                "iiiiidddd",
                $skill_id,
                $divisi_id,
                $cabang_id,
                $current_staff_id,
                $total_look,
                $konsultasi_komunikasi,
                $teknik,
                $kerapian_kebersihan,
                $produk_knowledge
            );
            $insert_query->execute();
        }
    }

    // Redirect to staff page after saving data
    header("Location: http://localhost/liekuang-academy/staff/staff.php?skill_id=$skill_id&divisi_id=$divisi_id&cabang_id=$cabang_id");
    exit;
}

// Check if we have a success message
$success_message = isset($_GET['success']) && $_GET['success'] == 1 ? "Data berhasil disimpan!" : "";
?>

<!DOCTYPE html>
<html lang="id" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Matrix - <?php echo htmlspecialchars($skill_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
                <a href="http://localhost/liekuang-academy/staff/staff.php?skill_id=6&divisi_id=1&cabang_id=1"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-arrow-left mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Kembali</span>
                </a>


                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../cabang/cabang.php"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Home</span>
                    </a>
                    <a href="#"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-users mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Karyawan</span>
                    </a>
                    <a href="#"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i
                            class="fas fa-chart-bar mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Laporan</span>
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

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        Skill Matrix: <span
                            class="text-primary-600 dark:text-primary-400"><?php echo htmlspecialchars($skill_name); ?></span>
                    </h1>
                    <div
                        class="flex flex-col sm:flex-row sm:items-center text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <div class="flex items-center">
                            <i class="fas fa-building mr-2 text-primary-500"></i>
                            <span class="font-medium">Divisi:</span>
                            <span class="ml-1"><?php echo htmlspecialchars($divisi_name); ?></span>
                        </div>
                        <span class="hidden sm:block mx-2">|</span>
                        <div class="flex items-center mt-1 sm:mt-0">
                            <i class="fas fa-map-marker-alt mr-2 text-primary-500"></i>
                            <span class="font-medium">Cabang:</span>
                            <span class="ml-1"><?php echo htmlspecialchars($cabang_name); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message (Fixed at top right) -->
            <?php if (!empty($success_message)): ?>
                <div id="successAlert"
                    class="fixed top-4 right-4 bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded shadow-md z-50 animate-fade-in-out">
                    <div class="flex items-center">
                        <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-2"></i></div>
                        <div>
                            <p class="font-bold">Berhasil!</p>
                            <p><?php echo $success_message; ?></p>
                        </div>
                        <button type="button" class="ml-auto"
                            onclick="document.getElementById('successAlert').style.display='none'">
                            <i class="fas fa-times text-green-700 dark:text-green-300"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white">
                        <i class="fas fa-clipboard-list mr-2 text-primary-500"></i>
                        Form Penilaian Skill
                    </h3>
                </div>

                <form method="post" action="">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th rowspan="2"
                                        class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 sticky left-0 z-10">
                                        <div class="flex items-center">
                                            <i class="fas fa-user mr-2 text-primary-500"></i>
                                            Nama Staff
                                        </div>
                                    </th>
                                    <th colspan="6"
                                        class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">
                                        Kriteria Penilaian
                                    </th>
                                </tr>
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-user-tie mb-1 text-primary-500"></i>
                                            <span>Total Look</span>
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-comments mb-1 text-primary-500"></i>
                                            <span>Konsultasi &amp; Komunikasi</span>
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-tools mb-1 text-primary-500"></i>
                                            <span>Teknik</span>
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-broom mb-1 text-primary-500"></i>
                                            <span>Kerapian &amp; Kebersihan</span>
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-book mb-1 text-primary-500"></i>
                                            <span>Produk Knowledge</span>
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 w-28">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-calculator mb-1 text-primary-500"></i>
                                            <span>Rata rata</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php
                                if ($staff_result->num_rows > 0) {
                                    $row_number = 0;
                                    while ($staff = $staff_result->fetch_assoc()) {
                                        $row_number++;
                                        $bg_class = $row_number % 2 === 0 ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900';

                                        // Get existing skill matrix data for this staff
                                        $matrix_query = $conn->prepare("
                                            SELECT * FROM skill_matrix
                                            WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
                                        ");
                                        $matrix_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $staff['id_staff']);
                                        $matrix_query->execute();
                                        $matrix_result = $matrix_query->get_result();

                                        $total_look = "";
                                        $konsultasi_komunikasi = "";
                                        $teknik = "";
                                        $kerapian_kebersihan = "";
                                        $produk_knowledge = "";
                                        $rata_rata = "";

                                        if ($matrix_result->num_rows > 0) {
                                            $matrix_data = $matrix_result->fetch_assoc();
                                            $total_look = $matrix_data['total_look'];
                                            $konsultasi_komunikasi = $matrix_data['konsultasi_komunikasi'];
                                            $teknik = $matrix_data['teknik'];
                                            $kerapian_kebersihan = $matrix_data['kerapian_kebersihan'];
                                            $produk_knowledge = $matrix_data['produk_knowledge'];
                                            $rata_rata = $matrix_data['rata_rata'];
                                        }
                                        ?>
                                        <tr
                                            class="<?php echo $bg_class; ?> hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap sticky left-0 <?php echo $bg_class; ?> z-10">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    <?php echo htmlspecialchars($staff['nama_staff']); ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.1" min="0" max="4"
                                                    class="rating-input total-look w-16 h-10 rounded-md border border-gray-400 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 text-center bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                                    name="staff[<?php echo $staff['id_staff']; ?>][total_look]"
                                                    value="<?php echo $total_look; ?>" required>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.1" min="0" max="4"
                                                    class="rating-input konsultasi w-16 h-10 rounded-md border border-gray-400 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 text-center bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                                    name="staff[<?php echo $staff['id_staff']; ?>][konsultasi_komunikasi]"
                                                    value="<?php echo $konsultasi_komunikasi; ?>" required>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.1" min="0" max="4"
                                                    class="rating-input teknik w-16 h-10 rounded-md border border-gray-400 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 text-center bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                                    name="staff[<?php echo $staff['id_staff']; ?>][teknik]"
                                                    value="<?php echo $teknik; ?>" required>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.1" min="0" max="4"
                                                    class="rating-input kerapian w-16 h-10 rounded-md border border-gray-400 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 text-center bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                                    name="staff[<?php echo $staff['id_staff']; ?>][kerapian_kebersihan]"
                                                    value="<?php echo $kerapian_kebersihan; ?>" required>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <input type="number" step="0.1" min="0" max="4"
                                                    class="rating-input produk w-16 h-10 rounded-md border border-gray-400 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 text-center bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                                    name="staff[<?php echo $staff['id_staff']; ?>][produk_knowledge]"
                                                    value="<?php echo $produk_knowledge; ?>" required>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <div
                                                    class="bg-gray-100 dark:bg-gray-600 w-16 h-10 rounded-md border border-gray-300 dark:border-gray-500 flex items-center justify-center text-center mx-auto">
                                                    <span class="rata-rata font-medium text-gray-800 dark:text-gray-200">
                                                        <?php echo $rata_rata ? number_format($rata_rata, 1) : '-'; ?>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr class="bg-white dark:bg-gray-800"><td colspan="7" class="px-6 py-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400"><div class="flex flex-col items-center"><i class="fas fa-users-slash text-4xl mb-3 text-gray-400 dark:text-gray-500"></i>Tidak ada staff ditemukan pada divisi ini.</div></td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Submit Button -->
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-2 text-primary-500"></i>
                            Skala penilaian: 0 (Terendah) - 4 (Tertinggi)
                        </div>
                        <button type="submit" name="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Calculate average when input values change
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const inputs = row.querySelectorAll('.rating-input');
                const rataRataElem = row.querySelector('.rata-rata');

                if (inputs.length > 0 && rataRataElem) {
                    inputs.forEach(input => {
                        input.addEventListener('input', function () {
                            calculateAverage(inputs, rataRataElem);
                        });
                    });

                    // Calculate initial values
                    calculateAverage(inputs, rataRataElem);
                }
            });

            // Auto-dismiss success alert after 5 seconds
            const alertElement = document.getElementById('successAlert');
            if (alertElement) {
                setTimeout(function () {
                    alertElement.style.display = 'none';
                }, 5000);
            }

            // Add visual feedback when input changes
            const allInputs = document.querySelectorAll('.rating-input');
            allInputs.forEach(input => {
                input.addEventListener('change', function () {
                    const originalBg = this.classList.contains('dark:bg-gray-700') ? 'dark:bg-gray-700' : 'bg-white';
                    this.classList.remove(originalBg);
                    this.classList.add('bg-green-50', 'dark:bg-green-900');
                    setTimeout(() => {
                        this.classList.remove('bg-green-50', 'dark:bg-green-900');
                        this.classList.add(originalBg);
                    }, 1000);
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
        });

        function calculateAverage(inputs, outputElement) {
            let sum = 0;
            let validCount = 0;

            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    sum += value;
                    validCount++;
                }
            });

            if (validCount > 0) {
                const average = sum / validCount;
                outputElement.textContent = average.toFixed(1);
            } else {
                outputElement.textContent = '-';
            }
        }
    </script>
</body>

</html>