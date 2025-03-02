<?php
include '../koneksi.php';

// Pastikan parameter `divisi_id` tersedia di URL
if (!isset($_GET['divisi_id']) || empty($_GET['divisi_id'])) {
    die("❌ Divisi tidak ditemukan! Periksa parameter URL.");
}

$divisi_id = intval($_GET['divisi_id']);

// 🔍 Ambil data divisi dan cabang terkait
$queryDivisi = $conn->prepare("
    SELECT divisi.id AS divisi_id, divisi.nama_divisi, divisi.cabang_id, cabang.nama_cabang 
    FROM divisi 
    JOIN cabang ON divisi.cabang_id = cabang.id 
    WHERE divisi.id = ?
");
$queryDivisi->bind_param("i", $divisi_id);
$queryDivisi->execute();
$resultDivisi = $queryDivisi->get_result();
$divisi = $resultDivisi->fetch_assoc();

// Jika divisi tidak ditemukan, tampilkan pesan error
if (!$divisi) {
    die("❌ Divisi tidak ditemukan di database!");
}

// 🔍 Ambil daftar staff berdasarkan divisi
$queryStaff = $conn->prepare("SELECT id, nama FROM staff WHERE id_divisi = ?");
$queryStaff->bind_param("i", $divisi_id);
$queryStaff->execute();
$resultStaff = $queryStaff->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Staff - <?= htmlspecialchars($divisi['nama_divisi']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">
        Staff <?= htmlspecialchars($divisi['nama_divisi']) ?> -
        <?= htmlspecialchars($divisi['nama_cabang']) ?>
    </h1>

    <a href="tambah_staff.php?divisi_id=<?= $divisi_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4 inline-block">
        + Tambah Staff
    </a>

    <table class="min-w-full bg-white border rounded shadow">
        <thead>
            <tr class="bg-gray-200">
                <th class="px-4 py-2 border">Nama Staff</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultStaff->num_rows > 0): ?>
                <?php while ($staff = $resultStaff->fetch_assoc()): ?>
                    <tr class="border">
                        <td class="px-4 py-2 border text-start">
                            <?= htmlspecialchars($staff['nama']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td class="text-center text-gray-500 py-4">Tidak ada staff di divisi ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-4">
        <a href="../divisi/divisi.php?cabang_id=<?= $divisi['cabang_id'] ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>
</div>

</body>
</html>
