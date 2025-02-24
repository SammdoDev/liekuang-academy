<?php
include 'koneksi.php';

// Ambil daftar cabang
$queryCabang = "SELECT * FROM cabang";
$resultCabang = $conn->query($queryCabang);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Daftar Cabang</h1>
    
    <ul class="space-y-2">
        <?php while ($cabang = $resultCabang->fetch_assoc()): ?>
            <li class="p-2 border rounded bg-gray-200 flex justify-between items-center">
                <span><?= htmlspecialchars($cabang['nama_cabang']) ?></span>
                <a href="divisi\divisi.php?cabang_id=<?= $cabang['id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                    Lihat Divisi
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

</body>
</html>
