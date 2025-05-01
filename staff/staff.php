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
    if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && 
        isset($_SESSION['cabang_id']) && $_SESSION['cabang_id'] == $cabang_id) {
        $authenticated = true;
    }
}

// Fungsi untuk mendapatkan password berdasarkan cabang
function getPasswordForBranch($conn, $cabang_id) {
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
function getColorClass($value) {
    if ($value >= 3.5) return "bg-green-100 text-green-800";
    if ($value >= 2.5) return "bg-yellow-100 text-yellow-800";
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
        $tambahStaff = $conn->prepare("INSERT INTO staff (nama_staff, id_skill, id_divisi, id_cabang, created_at) VALUES (?, ?, ?, ?, NOW())");
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
    <title>Staff - <?= htmlspecialchars($nama_skill) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Prevent flickering on load */
        .rating-value {
            min-width: 3.5rem;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white p-4 md:p-6 rounded-lg shadow-md mb-6">
            <div class="flex flex-col md:flex-row justify-between md:items-center">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Staff Skill: <?= htmlspecialchars($nama_skill) ?></h1>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <p class="text-gray-600">Divisi: <span class="font-medium"><?= htmlspecialchars($nama_divisi) ?></span></p>
                        <span class="text-gray-400 hidden md:inline">|</span>
                        <p class="text-gray-600">Cabang: <span class="font-medium"><?= htmlspecialchars($nama_cabang) ?></span></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                        <i class="fas fa-users mr-1"></i> <?= $staffCount ?> Staff
                    </span>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <a href="../skill/skill.php?divisi_id=<?= $divisi_id ?>" class="flex items-center text-blue-600 hover:text-blue-800 hover:underline">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Skill
                </a>
                <?php if ($authenticated): ?>
                <div class="flex items-center bg-green-50 px-3 py-2 rounded-lg">
                    <span class="text-green-700 mr-2"><i class="fas fa-check-circle"></i> Autentikasi Aktif</span>
                    <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>&logout=true" 
                        class="text-red-600 hover:text-red-800 text-sm font-medium ml-2">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Form Tambah Staff (Inline) -->
            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-lg font-medium mb-3">Tambah Staff Baru</h3>
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 p-3 rounded-lg mb-3">
                        <p class="text-red-800"><?= htmlspecialchars($error_message) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>" class="flex flex-col sm:flex-row items-center gap-2">
                    <input type="text" name="nama_staff" placeholder="Nama Staff" required
                           class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow w-full sm:w-auto">
                    <button type="submit" name="tambah_staff" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 w-full sm:w-auto">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Staff
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Notification Messages -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-green-800">
                    Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil dihapus
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-green-800">
                    Data staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil diperbarui
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-green-800">
                    Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'baru')) ?> berhasil ditambahkan
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Staff List with Skill Matrix (Simplified) -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex flex-col md:flex-row justify-between md:items-center">
                    <div>
                        <h2 class="text-xl font-semibold">Daftar Staff</h2>
                        <p class="text-sm text-gray-500">Nilai rata-rata skill matrix</p>
                    </div>
                    
                    <!-- Search Form -->
                    <form method="GET" action="" class="mt-3 md:mt-0">
                        <input type="hidden" name="skill_id" value="<?= $skill_id ?>">
                        <input type="hidden" name="divisi_id" value="<?= $divisi_id ?>">
                        <input type="hidden" name="cabang_id" value="<?= $cabang_id ?>">
                        <div class="flex">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama staff..." 
                                class="px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-500 text-white px-3 py-2 rounded-r-md hover:bg-blue-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (count($staffData) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Staff
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">
                                    Rata-Rata
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="staffTable">
                            <?php 
                            $counter = 1;
                            foreach ($staffData as $staff): 
                                // Format nilai untuk tampilan
                                $avg_total = number_format($staff['avg_total'], 1);
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $counter++ ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($staff['nama_staff']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="<?= getColorClass($staff['avg_total']) ?> px-3 py-1 rounded-full text-sm font-bold rating-value">
                                            <?= $avg_total ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex flex-wrap items-center justify-end gap-3">
                                            <?php if (!$authenticated): ?>
                                            <!-- Tombol input nilai dengan popup password jika belum login -->
                                            <button onclick="showPasswordModal(<?= $staff['id_staff'] ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                                <i class="fas fa-clipboard-list mr-1"></i> Input Nilai
                                            </button>
                                            <?php else: ?>
                                            <!-- Link input nilai jika sudah login -->
                                            <a href="../skill_matrix/skill_matrix.php?id_staff=<?= $staff['id_staff'] ?>&id_skill=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>" 
                                               class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                                <i class="fas fa-clipboard-list mr-1"></i> Input Nilai
                                            </a>
                                            <?php endif; ?>
                                            <a href="edit_staff.php?id_staff=<?= $staff['id_staff'] ?>&skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>" 
                                               class="text-blue-600 hover:text-blue-900 flex items-center">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <a href="hapus_staff.php?id_staff=<?= $staff['id_staff'] ?>&skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&confirm=true" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus staff ini?');"
                                               class="text-red-600 hover:text-red-900 flex items-center">
                                                <i class="fas fa-trash mr-1"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($search)): ?>
                <div class="p-4 border-t">
                    <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">
                        <i class="fas fa-times-circle mr-1"></i> Hapus filter pencarian
                    </a>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-6 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-users text-gray-400 text-2xl"></i>
                    </div>
                    <?php if (!empty($search)): ?>
                        <p class="text-gray-500">Tidak ada staff yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>"</p>
                        <p class="mt-2">
                            <a href="?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali ke semua staff
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="text-gray-500">Belum ada staff untuk skill ini.</p>
                        <p class="text-gray-400 text-sm mt-2">Gunakan form di atas untuk menambahkan staff.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    
    <!-- Modal Password untuk Input Nilai -->
    <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Login untuk Input Nilai</h3>
                <button onclick="hidePasswordModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mb-4 text-gray-600">Masukkan password untuk input nilai staff cabang <?= htmlspecialchars($nama_cabang) ?>.</p>
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?skill_id=<?= $skill_id ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>" id="passwordForm">
                <input type="hidden" name="redirect_staff_id" id="redirect_staff_id">
                <div class="mb-4">
                    <input type="password" name="password" placeholder="Masukkan password" required
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="hidePasswordModal()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 mr-2">
                        Batal
                    </button>
                    <button type="submit" name="password_submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Script untuk mempertahankan state dan fungsionalitas modal -->
    <script>
    // Data rata-rata untuk setiap staff (cache di client-side)
 // Data rata-rata untuk setiap staff (cache di client-side)
 const staffRatings = <?= json_encode($staffData) ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Mencegah resubmit form saat refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Pastikan nilai rata-rata selalu ditampilkan dengan benar
        refreshRatings();
        
        // Otomatis hilangkan notifikasi setelah 5 detik
        setTimeout(function() {
            const notifications = document.querySelectorAll('.bg-green-100, .bg-red-100:not([role="status"])');
            notifications.forEach(function(notification) {
                notification.style.display = 'none';
            });
        }, 5000);
    });
    
    // Fungsi untuk menyegarkan tampilan nilai rata-rata
    function refreshRatings() {
        // Tidak perlu implementasi karena kita sudah menggunakan server-side rendering
        // dan menyimpan data dalam array di PHP
    }
    
    // Fungsi untuk menampilkan modal password
    function showPasswordModal(staffId) {
        document.getElementById('passwordModal').classList.remove('hidden');
        document.getElementById('redirect_staff_id').value = staffId;
    }
    
    // Fungsi untuk menyembunyikan modal password
    function hidePasswordModal() {
        document.getElementById('passwordModal').classList.add('hidden');
    }
    
    // Tutup modal jika user klik di luar modal
    window.onclick = function(event) {
        const modal = document.getElementById('passwordModal');
        if (event.target === modal) {
            hidePasswordModal();
        }
    };
    
    // Validasi form password sebelum submit
    document.getElementById('passwordForm').addEventListener('submit', function(event) {
        const password = document.getElementById('admin_password').value;
        if (!password) {
            event.preventDefault();
            document.getElementById('passwordError').classList.remove('hidden');
            return false;
        }
        document.getElementById('passwordError').classList.add('hidden');
        return true;
    });
    
    // Fungsi untuk export data ke Excel
    function exportToExcel() {
        window.location.href = 'export.php?type=excel';
    }
    
    // Fungsi untuk export data ke PDF
    function exportToPDF() {
        window.location.href = 'export.php?type=pdf';
    }
    
    // Fungsi untuk menangani perubahan filter tanggal
    document.getElementById('date_filter').addEventListener('change', function() {
        document.getElementById('filter_form').submit();
    });
    
    // Fungsi untuk menampilkan konfirmasi hapus data
    function confirmDelete(staffId) {
        if (confirm('Apakah Anda yakin ingin menghapus data staff ini?')) {
            window.location.href = 'delete_staff.php?id=' + staffId;
        }
    }
    </script>
</body>
</html>
