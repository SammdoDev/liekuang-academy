<?php
include '../koneksi.php';

if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

// Ambil daftar divisi berdasarkan cabang
$queryDivisi = $conn->prepare("SELECT * FROM divisi WHERE id_cabang = ?");
$queryDivisi->bind_param("i", $cabang_id);
$queryDivisi->execute();
$resultDivisi = $queryDivisi->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divisi Cabang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Divisi dalam Cabang</h1>

    <a href="tambah_divisi.php?cabang_id=<?= $cabang_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4 inline-block">
        + Tambah Divisi
    </a>

    <ul class="space-y-2">
        <?php while ($divisi = $resultDivisi->fetch_assoc()): ?>
            <li class="p-3 border rounded bg-gray-200 flex justify-between items-center">
                <span class="font-medium"><?= htmlspecialchars($divisi['nama_divisi']) ?></span>
                <div class="space-x-2">
                    <a href="../staff/staff.php?divisi_id=<?= $divisi['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Lihat Staff</a>
                    <a href="edit_divisi.php?id=<?= $divisi['id'] ?>&cabang_id=<?= $cabang_id ?>" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Edit</a>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>

    <div class="mt-4">
        <a href="../index.php" class="text-blue-600 hover:underline">⬅ Kembali ke Cabang</a>
    </div>
</div>

</body>
</html>
