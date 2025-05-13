<?php
include '../koneksi.php';
include '../table.php';

$role = $_SESSION['role'] ?? '';
if ($role !== 'guru' && $role !== 'kasir') {
    header("Location: ../unauthorized.php");
    exit;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Check if branch ID is provided
if (!isset($_GET['cabang_id']) || empty($_GET['cabang_id'])) {
    header("Location: index.php?error=nocabang");
    exit();
}

$cabang_id = $_GET['cabang_id'];

// Get branch details
$query_cabang = "SELECT nama_cabang FROM cabang WHERE id_cabang = ?";
$stmt_cabang = mysqli_prepare($conn, $query_cabang);
mysqli_stmt_bind_param($stmt_cabang, "i", $cabang_id);
mysqli_stmt_execute($stmt_cabang);
mysqli_stmt_bind_result($stmt_cabang, $nama_cabang);
mysqli_stmt_fetch($stmt_cabang);
mysqli_stmt_close($stmt_cabang);

// Function to delete staff
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id_staff'])) {
    $staff_id = $_GET['id_staff'];
    $delete_query = "DELETE FROM staff WHERE id_staff = ?";
    $stmt_delete = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt_delete, "i", $staff_id);

    if (mysqli_stmt_execute($stmt_delete)) {
        header("Location: karyawan_cabang.php?cabang_id=$cabang_id&success=deleted");
        exit();
    } else {
        header("Location: karyawan_cabang.php?cabang_id=$cabang_id&error=deletefailed");
        exit();
    }
    if ($stmt_delete instanceof mysqli_stmt) {
        mysqli_stmt_close($stmt_delete);
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Cabang <?php echo htmlspecialchars($nama_cabang); ?> - Liekuang Academy</title>
    <!-- Tailwind CSS via CDN -->
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
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-layer-group text-2xl text-primary-600 dark:text-primary-400 mr-2"></i>
                        <span class="font-bold text-gray-800 dark:text-white text-lg">Liekuang Academy</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="divisi.php?cabang_id=<?php echo $cabang_id; ?>"
                            class="border-transparent text-gray-500 dark:text-gray-300 hover:border-primary-500 hover:text-primary-700 dark:hover:text-primary-400 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-sitemap mr-1"></i> Divisi
                        </a>
                        <a href="#"
                            class="border-primary-500 text-primary-600 dark:text-primary-400 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-users mr-1"></i> Staff
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center">
                            <button id="darkModeToggle"
                                class="p-1 rounded-full text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white focus:outline-none">
                                <i class="fas fa-moon text-lg"></i>
                            </button>
                            <div class="ml-4 flex items-center">
                                <i class="fas fa-user-circle text-gray-500 dark:text-gray-300 text-2xl mr-2"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Admin</span>
                            </div>
                            <a href="../akses/logout.php"
                                class="ml-4 px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-150 text-sm flex items-center">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-5" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="../cabang/cabang.php"
                        class="inline-flex items-center text-sm text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        <i class="fas fa-home mr-2"></i>
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <a href="divisi.php?cabang_id=<?php echo $cabang_id; ?>"
                            class="text-sm text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                            Divisi <?php echo htmlspecialchars($nama_cabang); ?>
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Staff Cabang <?php echo htmlspecialchars($nama_cabang); ?>
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Notification Messages -->
        <?php
        if (isset($_GET['success'])) {
            echo '<div id="success-alert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex justify-between items-center">';

            if ($_GET['success'] == "added") {
                echo '<span><i class="fas fa-check-circle mr-2"></i>Staff berhasil ditambahkan.</span>';
            } else if ($_GET['success'] == "updated") {
                echo '<span><i class="fas fa-check-circle mr-2"></i>Data staff berhasil diperbarui.</span>';
            } else if ($_GET['success'] == "deleted") {
                echo '<span><i class="fas fa-check-circle mr-2"></i>Staff berhasil dihapus.</span>';
            }

            echo '<button onclick="document.getElementById(\'success-alert\').style.display=\'none\'" class="text-green-700">
                    <i class="fas fa-times"></i>
                  </button>
                </div>';
        }

        if (isset($_GET['error'])) {
            echo '<div id="error-alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex justify-between items-center">';

            if ($_GET['error'] == "emptyfields") {
                echo '<span><i class="fas fa-exclamation-circle mr-2"></i>Harap isi semua bidang yang diperlukan.</span>';
            } else if ($_GET['error'] == "sqlerror") {
                echo '<span><i class="fas fa-exclamation-circle mr-2"></i>Terjadi kesalahan database.</span>';
            } else if ($_GET['error'] == "deletefailed") {
                echo '<span><i class="fas fa-exclamation-circle mr-2"></i>Gagal menghapus staff.</span>';
            }

            echo '<button onclick="document.getElementById(\'error-alert\').style.display=\'none\'" class="text-red-700">
                    <i class="fas fa-times"></i>
                  </button>
                </div>';
        }
        ?>

        <!-- Page Header -->
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-users text-primary-600 dark:text-primary-400 mr-2"></i>
                    Staff Cabang: <?php echo htmlspecialchars($nama_cabang); ?>
                </h1>
                <a href="tambah_staff.php?cabang_id=<?php echo $cabang_id; ?>"
                    class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Staff
                </a>
            </div>
        </div>

        <!-- Employee List Table -->
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                No.</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Nama Staff</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Divisi</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Skill</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        // Query to get all staff in this branch with division and skill names
                        $query_staff = "SELECT s.id_staff, s.nama_staff, d.nama_divisi, sk.nama_skill 
                                        FROM staff s 
                                        LEFT JOIN divisi d ON s.id_divisi = d.id_divisi 
                                        LEFT JOIN skill sk ON s.id_skill = sk.id_skill 
                                        WHERE s.id_cabang = ? 
                                        ORDER BY s.nama_staff ASC";
                        $stmt_staff = mysqli_prepare($conn, $query_staff);
                        mysqli_stmt_bind_param($stmt_staff, "i", $cabang_id);
                        mysqli_stmt_execute($stmt_staff);
                        $result_staff = mysqli_stmt_get_result($stmt_staff);

                        if (mysqli_num_rows($result_staff) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result_staff)) {
                                echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">' . $no . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($row['nama_staff']) . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">' .
                                    (isset($row['nama_divisi']) ? htmlspecialchars($row['nama_divisi']) : '<span class="text-gray-400 italic">Tidak ada</span>') . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">' .
                                    (isset($row['nama_skill']) ? htmlspecialchars($row['nama_skill']) : '<span class="text-gray-400 italic">Tidak ada</span>') . '</td>';

                                // Action buttons
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
                                echo '<div class="flex space-x-2">';
                                echo '<a href="../staff/staff.php?id_staff=' . $row['id_staff'] . '&cabang_id=' . $cabang_id . '" class="inline-flex items-center px-3 py-1 bg-primary-600 text-white text-sm rounded hover:bg-primary-700 transition duration-150" title="Detail">';
                                echo '<i class="fas fa-eye mr-1"></i> Lihat';
                                echo '</a>';
                                $no++;
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada staff dalam cabang ini.</td></tr>';
                        }

                        mysqli_stmt_close($stmt_staff);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Konfirmasi Hapus</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 dark:text-gray-300 mb-4">Apakah Anda yakin ingin menghapus staff ini? Tindakan
                    ini tidak dapat dibatalkan.</p>
                <div class="flex justify-end space-x-3">
                    <button id="cancelDelete"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md transition duration-150">Batal</button>
                    <a id="confirmDeleteBtn" href="#"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition duration-150">Hapus</a>
                </div>
            </div>
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
                darkModeToggle.innerHTML = '<i class="fas fa-moon text-lg"></i>';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun text-lg"></i>';
            }
        });

        // Update toggle icon on load
        if (html.classList.contains('dark')) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun text-lg"></i>';
        }

        // Delete confirmation modal
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        function confirmDelete(staffId) {
            deleteModal.classList.remove('hidden');
            confirmDeleteBtn.href = `karyawan_cabang.php?cabang_id=<?php echo $cabang_id; ?>&action=delete&id_staff=${staffId}`;
        }

        cancelDelete.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });

    </script>
</body>

</html>