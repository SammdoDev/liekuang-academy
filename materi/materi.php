<?php
include '../koneksi.php';

// Pastikan parameter `divisi_id` tersedia di URL
if (!isset($_GET['divisi_id']) || empty($_GET['divisi_id'])) {
    die("❌ Divisi tidak ditemukan! Periksa parameter URL.");
}

$divisi_id = intval($_GET['divisi_id']);

// 🔍 Ambil data divisi
$queryDivisi = $conn->prepare("SELECT nama_divisi FROM divisi WHERE id = ?");
$queryDivisi->bind_param("i", $divisi_id);
$queryDivisi->execute();
$resultDivisi = $queryDivisi->get_result();
$divisi = $resultDivisi->fetch_assoc();

if (!$divisi) {
    die("❌ Divisi tidak ditemukan di database!");
}

// 🔍 Ambil daftar materi dan nilai rata-rata
$queryMateri = $conn->prepare("
    SELECT 
        m.id, 
        m.nama_materi, 
        COALESCE(AVG(n.nilai), 0) AS rata_rata 
    FROM materi m
    LEFT JOIN nilai_materi n ON m.id = n.id_materi
    WHERE m.id_divisi = ?
    GROUP BY m.id, m.nama_materi
");
$queryMateri->bind_param("i", $divisi_id);
$queryMateri->execute();
$resultMateri = $queryMateri->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Divisi - <?= htmlspecialchars($divisi['nama_divisi']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">
        Materi Divisi <?= htmlspecialchars($divisi['nama_divisi']) ?>
    </h1>

    <a href="tambah_materi.php?divisi_id=<?= $divisi_id ?>" 
       class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 mb-4 inline-block">
        + Tambah Materi
    </a>

    <table class="min-w-full bg-white border rounded shadow">
        <thead>
            <tr class="bg-gray-200">
                <th class="px-4 py-2 border">Nama Materi</th>
                <th class="px-4 py-2 border">Nilai Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultMateri->num_rows > 0): ?>
                <?php while ($materi = $resultMateri->fetch_assoc()): ?>
                    <tr class="border">
                        <td class="px-4 py-2 border text-start">
                            <?= htmlspecialchars($materi['nama_materi']) ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?= number_format($materi['rata_rata'], 2) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center text-gray-500 py-4">Tidak ada materi untuk divisi ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-4">
        <a href="../divisi/divisi.php?cabang_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>
</div>

</body>
</html>
