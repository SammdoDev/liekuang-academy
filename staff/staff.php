<?php
include '../koneksi.php';

// Validasi parameter divisi_id
if (!isset($_GET['divisi_id']) || empty($_GET['divisi_id'])) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Parameter divisi_id tidak ditemukan!
        <br><a href="divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

$divisi_id = intval($_GET['divisi_id']);

// Ambil informasi divisi
$divisiQuery = $conn->prepare("SELECT d.nama_divisi, d.id_cabang, c.nama_cabang 
                               FROM divisi d 
                               JOIN cabang c ON d.id_cabang = c.id_cabang
                               WHERE d.id_divisi = ?");
$divisiQuery->bind_param("i", $divisi_id);
$divisiQuery->execute();
$result = $divisiQuery->get_result();

if ($result->num_rows === 0) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Divisi tidak ditemukan!
        <br><a href="divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

$divisiData = $result->fetch_assoc();
$nama_divisi = $divisiData['nama_divisi'];
$nama_cabang = $divisiData['nama_cabang'];
$cabang_id = $divisiData['id_cabang'];

// Proses penambahan staff jika ada data POST yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_staff']) && !empty($_POST['nama_staff'])) {
    $nama_staff = trim($_POST['nama_staff']);
    
    // Cek apakah staff dengan nama yang sama sudah ada di divisi ini
    $cekStaff = $conn->prepare("SELECT id_staff FROM staff WHERE nama_staff = ? AND id_divisi = ?");
    $cekStaff->bind_param("si", $nama_staff, $divisi_id);
    $cekStaff->execute();
    $cekResult = $cekStaff->get_result();
    
    if ($cekResult->num_rows === 0) {
        // Staff belum ada, tambahkan
        $tambahStaff = $conn->prepare("INSERT INTO staff (nama_staff, id_divisi, id_cabang) VALUES (?, ?, ?)");
        $tambahStaff->bind_param("sii", $nama_staff, $divisi_id, $cabang_id);
        
        if ($tambahStaff->execute()) {
            // Redirect ke halaman yang sama tanpa POST data (untuk mencegah re-submit saat refresh)
            header("Location: " . $_SERVER['PHP_SELF'] . "?divisi_id=" . $divisi_id . "&added=success&staff_name=" . urlencode($nama_staff));
            exit;
        } else {
            $error_message = "Gagal menambahkan staff: " . $conn->error;
        }
    } else {
        $error_message = "Staff dengan nama tersebut sudah ada di divisi ini.";
    }
}

// Get staff count
$countQuery = $conn->prepare("SELECT COUNT(*) as total FROM staff WHERE id_divisi = ?");
$countQuery->bind_param("i", $divisi_id);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();
$staffCount = $countResult['total'];

// Ambil daftar staff dengan caching
$cacheKey = "staff_divisi_" . $divisi_id;
$staffList = [];

// Fungsi untuk mendapatkan data staff dari database
function getStaffFromDatabase($conn, $divisi_id) {
    $staffQuery = $conn->prepare("SELECT id_staff, nama_staff FROM staff WHERE id_divisi = ? ORDER BY nama_staff");
    $staffQuery->bind_param("i", $divisi_id);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();
    
    $staffList = [];
    while ($staff = $staffResult->fetch_assoc()) {
        $staffList[] = $staff;
    }
    
    return $staffList;
}

// Dapatkan data staff dari database
$staffList = getStaffFromDatabase($conn, $divisi_id);

// Ambil data staff untuk tabel
$staffQuery = $conn->prepare("SELECT id_staff, nama_staff FROM staff WHERE id_divisi = ? ORDER BY nama_staff");
$staffQuery->bind_param("i", $divisi_id);
$staffQuery->execute();
$staffResult = $staffQuery->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Staff - <?= htmlspecialchars($nama_divisi) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h1 class="text-2xl font-bold mb-2">Daftar Staff: <?= htmlspecialchars($nama_divisi) ?></h1>
            <p class="text-gray-600 mb-4">Cabang: <?= htmlspecialchars($nama_cabang) ?></p>
            
            <!-- Navigasi -->
            <div class="flex mb-4">
                <a href="../index.php" class="text-blue-600 hover:underline">
                     Lihat Semua Cabang
                </a>
            </div>
            
            <!-- Form Tambah Staff (Inline) -->
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-lg font-medium mb-3">Tambah Staff Baru</h3>
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 p-3 rounded-lg mb-3">
                        <p class="text-red-800"><?= htmlspecialchars($error_message) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?divisi_id=<?= $divisi_id ?>" class="flex items-center">
                    <input type="text" name="nama_staff" placeholder="Nama Staff" required
                           class="px-4 py-2 border rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow">
                    <button type="submit" name="tambah_staff" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-r-md hover:bg-blue-600">
                        Tambah
                    </button>
                </form>
            </div>
            
        </div>
        
        <!-- Pesan Notifikasi -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6">
                <p class="text-green-800">
                     Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil dihapus
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6">
                <p class="text-green-800">
                     Data staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'tersebut')) ?> berhasil diperbarui
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
            <div class="bg-green-100 p-4 rounded-lg mb-6">
                <p class="text-green-800">
                     Staff <?= htmlspecialchars(urldecode($_GET['staff_name'] ?? 'baru')) ?> berhasil ditambahkan
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Daftar Staff -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if ($staffResult->num_rows > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Staff
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $counter = 1; // Initialize counter for sequential numbering
                        while ($staff = $staffResult->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $counter++ ?> <!-- Display and increment counter -->
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($staff['nama_staff']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="../skill/skill.php?id_staff=<?= $staff['id_staff'] ?>&divisi_id=<?= $divisi_id ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                         input skill
                                    </a>
                                    <a href="edit_staff.php?id_staff=<?= $staff['id_staff'] ?>&divisi_id=<?= $divisi_id ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                         Edit
                                    </a>
                                    <a href="hapus_staff.php?id_staff=<?= $staff['id_staff'] ?>&divisi_id=<?= $divisi_id ?>&confirm=true" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus staff ini?');"
                                       class="text-red-600 hover:text-red-900">
                                         Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-6 text-center">
                    <p class="text-gray-500">Belum ada staff untuk divisi ini.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistik -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-medium mb-3">Statistik Staff</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-blue-800">Total staff di divisi ini: <strong><?= $staffCount ?></strong></p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <?php
                    $totalQuery = $conn->prepare("SELECT COUNT(*) as total FROM staff WHERE id_cabang = ?");
                    $totalQuery->bind_param("i", $cabang_id);
                    $totalQuery->execute();
                    $totalResult = $totalQuery->get_result()->fetch_assoc();
                    ?>
                    <p class="text-purple-800">Total staff di cabang <?= htmlspecialchars($nama_cabang) ?>: 
                        <strong><?= $totalResult['total'] ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script untuk mempertahankan state -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mencegah resubmit form saat refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Otomatis hilangkan notifikasi setelah 5 detik
        setTimeout(function() {
            const notifications = document.querySelectorAll('.bg-green-100, .bg-red-100');
            notifications.forEach(function(notification) {
                notification.style.display = 'none';
            });
        }, 5000);
    });
    </script>
</body>
</html>