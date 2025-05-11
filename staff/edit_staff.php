<?php
include '../koneksi.php';

// Function to sanitize input (assuming this is defined in koneksi.php but adding for completeness)
if (!function_exists('sanitize_input')) {
    function sanitize_input($conn, $input)
    {
        return mysqli_real_escape_string($conn, trim($input));
    }
}

// Get parameters
$id_staff = isset($_GET['id_staff']) ? intval($_GET['id_staff']) : 0;
$divisi_id = isset($_GET['divisi_id']) ? intval($_GET['divisi_id']) : 0;
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$cabang_id = isset($_GET['cabang_id']) ? intval($_GET['cabang_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'edit_staff';

// Validate required parameters
if ($id_staff == 0) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Parameter tidak valid. Diperlukan id_staff.</p>
        <a href="javascript:history.back()" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>');
}

if ($divisi_id == 0) {
    die('<div class="bg-red-100 dark:bg-red-900/30 p-4 my-4 rounded-lg text-red-800 dark:text-red-400 border-l-4 border-red-500 dark:border-red-500/70">
        <p class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> Parameter tidak valid. Diperlukan divisi_id.</p>
        <a href="javascript:history.back()" class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>');
}

// If cabang_id is not provided but we have divisi_id, try to get cabang_id from divisi
if ($cabang_id == 0 && $divisi_id > 0) {
    $cabang_query = $conn->prepare("SELECT id_cabang FROM divisi WHERE id_divisi = ?");
    $cabang_query->bind_param("i", $divisi_id);
    $cabang_query->execute();
    $cabang_result = $cabang_query->get_result();

    if ($cabang_result->num_rows > 0) {
        $cabang_data = $cabang_result->fetch_assoc();
        $cabang_id = $cabang_data['id_cabang'];
    }
}

// Get staff information
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
$current_nama = $staffData['nama_staff'];
$current_divisi_id = $staffData['id_divisi'];
$current_cabang_id = $staffData['id_cabang'];
$current_divisi_nama = $staffData['nama_divisi'];
$current_cabang_nama = $staffData['nama_cabang'];

// Get skill information if skill_id is provided
$skill_name = '';
if ($skill_id > 0) {
    $skill_query = $conn->prepare("SELECT nama_skill FROM skill WHERE id_skill = ?");
    $skill_query->bind_param("i", $skill_id);
    $skill_query->execute();
    $skill_result = $skill_query->get_result();

    if ($skill_result->num_rows > 0) {
        $skill_data = $skill_result->fetch_assoc();
        $skill_name = $skill_data['nama_skill'];
    }
}

// Messages
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Edit staff details form submission
        if ($_POST['action'] == 'edit_staff') {
            if (empty($_POST['nama_staff'])) {
                $message = "Nama staff tidak boleh kosong!";
                $message_type = "warning";
            } else {
                $nama = sanitize_input($conn, $_POST['nama_staff']);

                $updateQuery = $conn->prepare("UPDATE staff SET nama_staff = ? WHERE id_staff = ?");
                $updateQuery->bind_param("ssi", $nama, $id_staff);

                if ($updateQuery->execute()) {
                    $message = "Data staff berhasil diperbarui!";
                    $message_type = "success";
                    $current_nama = $nama;

                    if (isset($_POST['save_and_return']) && $_POST['save_and_return'] == '1') {
                        $redirect_url = "staff.php?";

                        if (!empty($skill_id)) {
                            $redirect_url .= "skill_id=$skill_id&";
                        }

                        $redirect_url .= "divisi_id=$divisi_id";

                        if (!empty($cabang_id)) {
                            $redirect_url .= "&cabang_id=$cabang_id";
                        }

                        $redirect_url .= "&updated=success&staff_name=" . urlencode($nama);

                        header("Location: $redirect_url");
                        exit();
                    }
                } else {
                    $message = "Gagal memperbarui data: " . $updateQuery->error;
                    $message_type = "error";
                }
            }
        }
        // Skill matrix form submission
        elseif ($_POST['action'] == 'skill_matrix' && isset($_POST['staff']) && $skill_id > 0) {
            $values = $_POST['staff'][$id_staff];

            $total_look = floatval($values['total_look']);
            $konsultasi_komunikasi = floatval($values['konsultasi_komunikasi']);
            $teknik = floatval($values['teknik']);
            $kerapian_kebersihan = floatval($values['kerapian_kebersihan']);
            $produk_knowledge = floatval($values['produk_knowledge']);
            $catatan_skill = sanitize_input($conn, $values['catatan_skill']);

            // Check if record exists
            $check_query = $conn->prepare("
                SELECT id_skill_matrix
                FROM skill_matrix
                WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
            ");
            $check_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $id_staff);
            $check_query->execute();
            $check_result = $check_query->get_result();

            if ($check_result->num_rows > 0) {
                // Update existing record
                $update_query = $conn->prepare("
                    UPDATE skill_matrix
                    SET total_look = ?,
                        konsultasi_komunikasi = ?,
                        teknik = ?,
                        kerapian_kebersihan = ?,
                        produk_knowledge = ?,
                        catatan = ?
                    WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
                ");
                $update_query->bind_param(
                    "dddddsiiii",
                    $total_look,
                    $konsultasi_komunikasi,
                    $teknik,
                    $kerapian_kebersihan,
                    $produk_knowledge,
                    $catatan_skill,
                    $skill_id,
                    $divisi_id,
                    $cabang_id,
                    $id_staff
                );

                if ($update_query->execute()) {
                    $message = "Nilai skill berhasil diperbarui!";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui nilai skill: " . $update_query->error;
                    $message_type = "error";
                }
            } else {
                // Insert new record
                $insert_query = $conn->prepare("
                    INSERT INTO skill_matrix (
                        id_skill, id_divisi, id_cabang, id_staff, 
                        total_look, konsultasi_komunikasi, teknik, 
                        kerapian_kebersihan, produk_knowledge
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_query->bind_param(
                    "iiiiddddds",
                    $skill_id,
                    $divisi_id,
                    $cabang_id,
                    $id_staff,
                    $total_look,
                    $konsultasi_komunikasi,
                    $teknik,
                    $kerapian_kebersihan,
                    $produk_knowledge,
                    $catatan_skill
                );

                if ($insert_query->execute()) {
                    $message = "Nilai skill berhasil disimpan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menyimpan nilai skill: " . $insert_query->error;
                    $message_type = "error";
                }
            }

            // Redirect option
            if (isset($_POST['save_and_return']) && $_POST['save_and_return'] == '1') {
                header("Location: staff.php?skill_id=$skill_id&divisi_id=$divisi_id&cabang_id=$cabang_id&success=1");
                exit();
            }
        }
    }
}

// Get existing skill matrix data for this staff
$matrix_data = null;
if ($skill_id > 0) {
    $matrix_query = $conn->prepare("
        SELECT * FROM skill_matrix
        WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
    ");
    $matrix_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $id_staff);
    $matrix_query->execute();
    $matrix_result = $matrix_query->get_result();

    if ($matrix_result->num_rows > 0) {
        $matrix_data = $matrix_result->fetch_assoc();
    }
}

// Back URL with all parameters
$back_url = "staff.php?";
if (!empty($skill_id)) {
    $back_url .= "skill_id=$skill_id&";
}
$back_url .= "divisi_id=$divisi_id";
if (!empty($cabang_id)) {
    $back_url .= "&cabang_id=$cabang_id";
}
?>

<!DOCTYPE html>
<html lang="id" class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo ($action == 'skill_matrix') ? "Skill Matrix - $skill_name - $current_nama" : "Edit Staff - $current_nama"; ?>
    </title>
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
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Staff Manager</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ID Staff: <?php echo $id_staff; ?>
                </p>
            </div>

            <nav class="p-6 space-y-4">
                <a href="<?php echo $back_url; ?>"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                    <i class="fas fa-arrow-left mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                    <span>Kembali ke Daftar Staff</span>
                </a>

                <?php if ($action == 'edit_staff'): ?>
                    <a href="?id_staff=<?php echo $id_staff; ?>&divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?>&action=edit_staff<?php echo !empty($skill_id) ? '&skill_id=' . $skill_id : ''; ?>"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 font-medium transition group">
                        <i class="fas fa-user-edit mr-3 text-primary-500"></i>
                        <span>Edit Profil Staff</span>
                    </a>
                <?php else: ?>
                    <a href="?id_staff=<?php echo $id_staff; ?>&divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?>&action=edit_staff<?php echo !empty($skill_id) ? '&skill_id=' . $skill_id : ''; ?>"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-user-edit mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Edit Profil Staff</span>
                    </a>
                <?php endif; ?>

                <?php if ($skill_id > 0): ?>
                    <?php if ($action == 'skill_matrix'): ?>
                        <a href="?id_staff=<?php echo $id_staff; ?>&divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?>&skill_id=<?php echo $skill_id; ?>&action=skill_matrix"
                            class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 font-medium transition group">
                            <i class="fas fa-chart-bar mr-3 text-primary-500"></i>
                            <span>Skill Matrix</span>
                        </a>
                    <?php else: ?>
                        <a href="?id_staff=<?php echo $id_staff; ?>&divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?>&skill_id=<?php echo $skill_id; ?>&action=skill_matrix"
                            class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                            <i class="fas fa-chart-bar mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                            <span>Skill Matrix</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm uppercase text-gray-500 dark:text-gray-400 font-semibold mb-3">Navigasi Cepat
                    </h3>
                    <a href="../cabang/cabang.php"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-home mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Home</span>
                    </a>
                    <a href="staff.php?divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?><?php echo !empty($skill_id) ? '&skill_id=' . $skill_id : ''; ?>"
                        class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <i class="fas fa-users mr-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-500"></i>
                        <span>Daftar Staff</span>
                    </a>
                </div>
            </nav>

            <div class="p-6 mt-auto border-t border-gray-200 dark:border-gray-700">
                <button id="darkModeToggle"
                    class="flex items-center text-gray-700 dark:text-gray-300 px-4 py-2 w-full rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-moon mr-3 text-gray-500 dark:hidden"></i>
                    <i class="fas fa-sun mr-3 text-gray-400 hidden dark:inline"></i>
                    <span class="dark:hidden">Mode Gelap</span>
                    <span class="hidden dark:inline">Mode Terang</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-auto">
            <!-- Success/Error Messages -->
            <?php if (!empty($message)): ?>
                <div id="alertMessage" class="mb-6 p-4 rounded-lg border-l-4 <?php
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
                        <p class="font-medium"><?php echo $message; ?></p>
                        <button type="button"
                            class="ml-auto text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300"
                            onclick="document.getElementById('alertMessage').style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Staff Information Panel -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
                <div
                    class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            <?php if ($action == 'skill_matrix'): ?>
                                <i class="fas fa-chart-bar text-primary-600 dark:text-primary-400 mr-2"></i>
                                Skill Matrix: <?php echo htmlspecialchars($skill_name); ?>
                            <?php else: ?>
                                <i class="fas fa-user-edit text-primary-600 dark:text-primary-400 mr-2"></i>
                                Edit Staff
                            <?php endif; ?>
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            <?php echo htmlspecialchars($current_nama); ?>
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-2 text-primary-500"></i>
                                    <span class="font-medium">Divisi:</span>
                                    <span class="ml-1"><?php echo htmlspecialchars($current_divisi_nama); ?></span>
                                </div>
                                <span class="hidden sm:block mx-2">|</span>
                                <div class="flex items-center mt-1 sm:mt-0">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary-500"></i>
                                    <span class="font-medium">Cabang:</span>
                                    <span class="ml-1"><?php echo htmlspecialchars($current_cabang_nama); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($action == 'edit_staff'): ?>
                <!-- Edit Staff Form -->
                <div
                    class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                            <i class="fas fa-user-edit text-primary-600 dark:text-primary-400 mr-2"></i>
                            Edit Data Staff
                        </h2>
                    </div>

                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="edit_staff">

                            <div>
                                <label for="nama_staff"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Staff
                                </label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" id="nama_staff" name="nama_staff"
                                        value="<?php echo htmlspecialchars($current_nama); ?>" required
                                        class="pl-10 py-4 block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="Masukkan nama staff">
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Nama staff akan ditampilkan di semua sistem aplikasi
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

                        <div
                            class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <a href="<?php echo $back_url; ?>"
                                class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Kembali
                            </a>

                            <a href="hapus_staff.php?id_staff=<?php echo $id_staff; ?>&divisi_id=<?php echo $divisi_id; ?>&cabang_id=<?php echo $cabang_id; ?>"
                                class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 transition flex items-center"
                                onclick="return confirm('Anda yakin ingin menghapus staff ini? Tindakan ini tidak dapat dibatalkan.');">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Hapus Staff
                            </a>

                        </div>
                    </div>
                </div>
            <?php elseif ($action == 'skill_matrix' && $skill_id > 0): ?>
                <!-- Skill Matrix Form -->
                <div
                    class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                            <i class="fas fa-chart-bar text-primary-600 dark:text-primary-400 mr-2"></i>
                            Skill Matrix: <?php echo htmlspecialchars($skill_name); ?>
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Evaluasi kemampuan <?php echo htmlspecialchars($current_nama); ?> pada skill ini
                        </p>
                    </div>

                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="skill_matrix">

                            <!-- Instructions -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Petunjuk Pengisian
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                            <p>Berikan nilai untuk setiap komponen skill dengan skala 1-10:</p>
                                            <ul class="list-disc ml-5 mt-1 space-y-1">
                                                <li>1-3: Pemula (Masih membutuhkan banyak bimbingan)</li>
                                                <li>4-6: Menengah (Mampu bekerja dengan pengawasan minimal)</li>
                                                <li>7-8: Mahir (Mampu bekerja mandiri dengan hasil baik)</li>
                                                <li>9-10: Ahli (Dapat mengajarkan kepada orang lain)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <?php
                                // Define skill parameters
                                $skill_params = [
                                    'total_look' => [
                                        'label' => 'Total Look',
                                        'desc' => 'Penampilan secara keseluruhan, kesesuaian dengan standar',
                                        'icon' => 'fas fa-user-check'
                                    ],
                                    'konsultasi_komunikasi' => [
                                        'label' => 'Konsultasi & Komunikasi',
                                        'desc' => 'Kemampuan berkomunikasi dan memberikan konsultasi',
                                        'icon' => 'fas fa-comments'
                                    ],
                                    'teknik' => [
                                        'label' => 'Teknik',
                                        'desc' => 'Penguasaan teknik dan metode yang tepat',
                                        'icon' => 'fas fa-tools'
                                    ],
                                    'kerapian_kebersihan' => [
                                        'label' => 'Kerapian & Kebersihan',
                                        'desc' => 'Menjaga kerapian dan kebersihan area kerja',
                                        'icon' => 'fas fa-broom'
                                    ],
                                    'produk_knowledge' => [
                                        'label' => 'Produk Knowledge',
                                        'desc' => 'Pemahaman tentang produk yang digunakan',
                                        'icon' => 'fas fa-lightbulb'
                                    ]
                                ];

                                foreach ($skill_params as $param_name => $param_info):
                                    $value = isset($matrix_data[$param_name]) ? $matrix_data[$param_name] : 0;
                                    ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="<?php echo $param_info['icon']; ?> text-primary-500 mr-2"></i>
                                            <?php echo $param_info['label']; ?>
                                        </label>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                            <div class="w-full sm:w-3/4">
                                                <input type="range" min="0" max="4" step="0.5" id="<?php echo $param_name; ?>"
                                                    name="staff[<?php echo $id_staff; ?>][<?php echo $param_name; ?>]"
                                                    value="<?php echo $value; ?>"
                                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary-600"
                                                    oninput="updateRangeValue(this, '<?php echo $param_name; ?>_value')">


                                            </div>
                                            <div class="w-full sm:w-1/4">
                                                <div class="flex items-center">
                                                    <span id="<?php echo $param_name; ?>_value"
                                                        class="bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-lg font-bold rounded-lg px-3 py-1 min-w-[3rem] text-center">
                                                        <?php echo $value; ?>
                                                    </span>
                                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">/ 4</span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <?php echo $param_info['desc']; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Catatan Field -->
                                <div class="mt-4">
                                    <label for="catatan_skill"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <i class="fas fa-sticky-note text-primary-500 mr-2"></i>
                                        Catatan Skill
                                    </label>
                                    <textarea id="catatan_skill" name="staff[<?php echo $id_staff; ?>][catatan_skill]"
                                        rows="4"
                                        class="py-3 px-4 block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="Tambahkan catatan terkait skill ini (opsional)"><?php echo isset($matrix_data['catatan']) ? htmlspecialchars($matrix_data['catatan']) : ''; ?></textarea>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Catatan khusus terkait kemampuan staff pada skill ini
                                    </p>
                                </div>
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

                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="<?php echo $back_url; ?>"
                                class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Kembali ke Daftar Staff
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Function to update range value display
        function updateRangeValue(rangeInput, valueDisplayId) {
            document.getElementById(valueDisplayId).textContent = rangeInput.value;
        }

        // Initialize range values
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize all range values
            <?php foreach (array_keys($skill_params ?? []) as $param): ?>
                updateRangeValue(document.getElementById('<?php echo $param; ?>'), '<?php echo $param; ?>_value');
            <?php endforeach; ?>

            // Dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');

            darkModeToggle.addEventListener('click', function () {
                document.documentElement.classList.toggle('dark');

                // Store preference in cookie
                const isDarkMode = document.documentElement.classList.contains('dark');
                document.cookie = `theme=${isDarkMode ? 'dark' : 'light'}; path=/; max-age=31536000`; // 1 year
            });

            // Auto hide alerts after 5 seconds
            const alertMessage = document.getElementById('alertMessage');
            if (alertMessage) {
                setTimeout(function () {
                    alertMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>

</html>