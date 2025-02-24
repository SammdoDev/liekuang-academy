<?php
include '../koneksi.php';

if (!isset($_GET['divisi_id'])) {
    die("Divisi tidak ditemukan!");
}

$divisi_id = intval($_GET['divisi_id']);

// Ambil daftar skill berdasarkan divisi
$query = $conn->prepare("SELECT * FROM skills WHERE id_divisi = ?");
$query->bind_param("i", $divisi_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Skill</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Daftar Skill</h1>

    <a href="tambah_skill.php?divisi_id=<?= $divisi_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4 inline-block">
        + Tambah Skill
    </a>

    <ul class="space-y-2">
        <?php while ($skill = $result->fetch_assoc()): ?>
            <li class="p-3 border rounded bg-gray-200 flex justify-between items-center">
                <span><?= htmlspecialchars($skill['nama_skill']) ?></span>
                <a href="hapus_skill.php?id=<?= $skill['id'] ?>&divisi_id=<?= $divisi_id ?>" 
                   class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                   onclick="return confirm('Yakin ingin menghapus skill ini?');">Hapus</a>
            </li>
        <?php endwhile; ?>
    </ul>

    <div class="mt-4">
        <a href="../divisi/divisi.php?cabang_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>
</div>
</body>
</html>
