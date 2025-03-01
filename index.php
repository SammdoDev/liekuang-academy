<?php
include 'koneksi.php';

// Ambil daftar cabang
$queryCabang = "SELECT * FROM cabang ORDER BY id ASC";
$resultCabang = $conn->query($queryCabang);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cabang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<div class="w-64 bg-gray-800 text-white min-h-screen p-6">
    <h2 class="text-2xl font-bold mb-6 text-center">Dashboard</h2>
    <nav class="space-y-4">
        <a href="index.php" class="block py-2 px-4 rounded-lg bg-gray-700 hover:bg-gray-600"> Home</a>
        <a href="staff/staff.php" class="block py-2 px-4 rounded-lg hover:bg-gray-600"> Staff</a>
        <a href="divisi/divisi.php" class="block py-2 px-4 rounded-lg hover:bg-gray-600"> Divisi</a>
    </nav>
</div>

<!-- Main Content -->
<div class="flex-1 p-8">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Daftar Cabang</h1>

    <?php
    // Debugging: Menampilkan jumlah cabang yang ditemukan
    echo "<p class='text-red-500'>Jumlah cabang ditemukan: " . $resultCabang->num_rows . "</p>";
    ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php while ($cabang = $resultCabang->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold text-gray-700"><?= htmlspecialchars($cabang['nama_cabang']) ?></h2>
                <p class="text-gray-500 text-sm mt-2">ID: <?= $cabang['id'] ?></p>
                <a href="divisi/divisi.php?cabang_id=<?= $cabang['id'] ?>" 
                   class="block text-center mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                     Lihat Divisi
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
