<?php
include '../koneksi.php';

if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

// Ambil nama cabang
$queryCabang = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id = ?");
$queryCabang->bind_param("i", $cabang_id);
$queryCabang->execute();
$resultCabang = $queryCabang->get_result();
$cabang = $resultCabang->fetch_assoc();

if (!$cabang) {
    die("Cabang tidak ditemukan!");
}

// Ambil daftar divisi berdasarkan cabang
$queryDivisi = $conn->prepare("SELECT * FROM divisi WHERE cabang_id = ?");
$queryDivisi->bind_param("i", $cabang_id);
$queryDivisi->execute();
$resultDivisi = $queryDivisi->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divisi - <?= htmlspecialchars($cabang['nama_cabang']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

<div class="max-w-3xl w-full bg-white p-6 rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
        Divisi di Cabang <span class="text-blue-600"><?= htmlspecialchars($cabang['nama_cabang']) ?></span>
    </h1>

    <a href="tambah_divisi.php?cabang_id=<?= $cabang_id ?>" 
       class="flex items-center justify-center bg-blue-500 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-600 transition duration-200 mb-4">
         Tambah Divisi
    </a>

    <?php if ($resultDivisi->num_rows > 0): ?>
        <ul class="space-y-3">
            <?php while ($divisi = $resultDivisi->fetch_assoc()): ?>
                <li class="p-4 border rounded-lg bg-gray-50 shadow-sm flex justify-between items-center hover:bg-gray-100 transition duration-200">
                    <span class="font-semibold text-gray-800"><?= htmlspecialchars($divisi['nama_divisi']) ?></span>
                    <div class="space-x-2 flex">
                        <a href="../staff/staff.php?divisi_id=<?= $divisi['id'] ?>" 
                           class="bg-green-500 text-white px-4 py-2 rounded-lg shadow hover:bg-green-600 transition duration-200">
                             Lihat Staff
                        </a>
                        <a href="edit_divisi.php?id=<?= $divisi['id'] ?>&cabang_id=<?= $cabang_id ?>" 
                           class="bg-yellow-500 text-white px-4 py-2 rounded-lg shadow hover:bg-yellow-600 transition duration-200">
                             Edit
                        </a>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500 text-center mt-4">Belum ada divisi dalam cabang ini.</p>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="../index.php" class="text-blue-600 hover:underline text-lg">⬅ Kembali ke Cabang</a>
    </div>
</div>

</body>
</html>
