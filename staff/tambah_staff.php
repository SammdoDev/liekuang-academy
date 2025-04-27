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

// Ambil id_cabang berdasarkan id_divisi
$cabang_id = get_cabang_id_from_divisi($conn, $divisi_id);

if (!$cabang_id) {
    die('<div class="max-w-lg mx-auto bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Divisi tidak ditemukan dalam database!
        <br><a href="divisi.php" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>');
}

// Ambil nama divisi untuk ditampilkan
$divisiQuery = $conn->prepare("SELECT nama_divisi FROM divisi WHERE id_divisi = ?");
$divisiQuery->bind_param("i", $divisi_id);
$divisiQuery->execute();
$divisiData = $divisiQuery->get_result()->fetch_assoc();
$nama_divisi = $divisiData ? $divisiData['nama_divisi'] : 'Tidak Diketahui';

// Ambil nama cabang untuk ditampilkan
$cabangQuery = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id_cabang = ?");
$cabangQuery->bind_param("i", $cabang_id);
$cabangQuery->execute();
$cabangData = $cabangQuery->get_result()->fetch_assoc();
$nama_cabang = $cabangData ? $cabangData['nama_cabang'] : 'Tidak Diketahui';

// Proses form jika disubmit
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['nama_staff'])) {
        $nama = sanitize_input($conn, $_POST['nama_staff']);
        
        if ($debug_mode) {
            debug_log("Nama Staff = $nama, ID Divisi = $divisi_id, ID Cabang = $cabang_id");
        }
        
        // Tambahkan staff menggunakan fungsi di koneksi.php
        $result = add_staff($conn, $nama, $divisi_id, $cabang_id);
        
        if ($result['success']) {
            $message = "✅ " . $result['message'] . " dengan ID: " . $result['staff_id'];
            $message_type = "success";
            
            // Redirect setelah 3 detik
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'staff.php?divisi_id=$divisi_id';
                }, 3000);
            </script>";
        } else {
            $message = "❌ " . $result['message'];
            $message_type = "error";
            
            if ($debug_mode && isset($result['error_code'])) {
                debug_log("Error code: " . $result['error_code']);
            }
        }
    } else {
        $message = "⚠ Nama staff tidak boleh kosong!";
        $message_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Staff - <?= htmlspecialchars($nama_divisi) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Tambah Staff</h1>
        
        <div class="mb-4 p-3 bg-blue-50 rounded-lg">
            <p class="text-blue-800"><strong>Divisi:</strong> <?= htmlspecialchars($nama_divisi) ?></p>
            <p class="text-blue-800"><strong>Cabang:</strong> <?= htmlspecialchars($nama_cabang) ?></p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="p-3 mb-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                <?= $message ?>
                <?php if ($message_type === 'success'): ?>
                    <p class="mt-2 text-sm">Tunggu sebentar</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>?divisi_id=<?= $divisi_id ?>">
            <label class="block mb-4">
                <span class="text-gray-700">Nama Staff:</span>
                <input type="text" name="nama_staff" required class="block w-full p-2 border rounded mt-1">
            </label>
            
            <!-- Hidden fields to ensure consistency -->
            <input type="hidden" name="divisi_id" value="<?= $divisi_id ?>">
            <input type="hidden" name="cabang_id" value="<?= $cabang_id ?>">
            
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-2 w-full">
                Simpan Staff
            </button>
        </form>
        
        <div class="mt-4">
            <a href="staff.php?divisi_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline block text-center">
                 Kembali ke Daftar Staff
            </a>
        </div>
    </div>
</body>
</html>