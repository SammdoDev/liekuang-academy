<?php
include '../koneksi.php';

// Validasi parameter
if (!isset($_GET['id_staff']) || !isset($_GET['divisi_id'])) {
    die('<div class="bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Parameter tidak valid. Diperlukan id_staff dan divisi_id.
        <br><a href="javascript:history.back()" class="text-blue-600 hover:underline">⬅ Kembali</a>
    </div>');
}

$id_staff = intval($_GET['id_staff']);
$divisi_id = intval($_GET['divisi_id']);

// Verifikasi staff ada dan terkait dengan divisi yang benar
$check_stmt = $conn->prepare("SELECT id_staff, nama_staff FROM staff WHERE id_staff = ? AND id_divisi = ?");
$check_stmt->bind_param("ii", $id_staff, $divisi_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    die('<div class="bg-red-100 p-4 my-4 rounded-lg text-red-800">
         Staff tidak ditemukan atau tidak terkait dengan divisi ini.
        <br><a href="staff.php?divisi_id=' . $divisi_id . '" class="text-blue-600 hover:underline"> Kembali ke Daftar Staff</a>
    </div>');
}

$staff_data = $result->fetch_assoc();
$nama_staff = $staff_data['nama_staff'];

// Jika ada parameter konfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Proses penghapusan
    $delete_stmt = $conn->prepare("DELETE FROM staff WHERE id_staff = ?");
    $delete_stmt->bind_param("i", $id_staff);
    
    if ($delete_stmt->execute()) {
        // Redirect ke daftar staff dengan pesan sukses
        header("Location: staff.php?divisi_id=$divisi_id&deleted=success&staff_name=" . urlencode($nama_staff));
        exit();
    } else {
        $error_message = "Gagal menghapus staff: " . $delete_stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Konfirmasi Hapus Staff</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="p-4 mb-4 bg-red-100 rounded-lg text-red-800">
                <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <div class="p-4 mb-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <p class="text-yellow-700">Apakah Anda yakin ingin menghapus staff berikut?</p>
            <p class="font-bold text-lg mt-2"><?= htmlspecialchars($nama_staff) ?></p>
            <p class="text-sm text-gray-500 mt-1">ID Staff: <?= $id_staff ?></p>
            <p class="text-sm text-red-600 mt-3"> Tindakan ini tidak dapat dibatalkan!</p>
        </div>
        
        <div class="flex justify-between">
            <a href="staff.php?divisi_id=<?= $divisi_id ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                Batal
            </a>
            <a href="hapus_staff.php?id_staff=<?= $id_staff ?>&divisi_id=<?= $divisi_id ?>&confirm=yes" 
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                Hapus Staff
            </a>
        </div>
    </div>
</body>
</html>