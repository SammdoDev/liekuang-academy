<?php
include '../koneksi.php';

if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

// Query untuk mengambil nama cabang
$queryCabang = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id_cabang = ?");
$queryCabang->bind_param("i", $cabang_id);
$queryCabang->execute();
$resultCabang = $queryCabang->get_result();
$cabang = $resultCabang->fetch_assoc();

if (!$cabang) {
    die("Cabang tidak ditemukan!");
}

// Query untuk mengambil divisi
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
    <title>Divisi - <?= htmlspecialchars($cabang['nama_cabang']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-gray-800 shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Dashboard</h2>
        
        <nav class="space-y-4">
            <a href="../index.php" class="block text-gray-700 dark:text-gray-300 px-4 py-2 hover:bg-blue-500 hover:text-white rounded-lg transition">
                 Kembali ke Cabang
            </a>
            <a href="tambah_divisi.php?cabang_id=<?= $cabang_id ?>" 
               class="block text-white bg-blue-500 px-4 py-2 rounded-lg shadow-md hover:bg-blue-600 transition">
                 Tambah Divisi
            </a>
        </nav>
    </aside>

    <!-- Konten -->
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Divisi - <?= htmlspecialchars($cabang['nama_cabang']) ?></h1>

        <?php if ($resultDivisi->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($divisi = $resultDivisi->fetch_assoc()): ?>
                    <div class="p-6 bg-white dark:bg-gray-700 rounded-lg shadow-md border dark:border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">
                            <?= htmlspecialchars($divisi['nama_divisi']) ?>
                        </h2>
                        <div class="flex space-x-2 px-1 mt-4">
                            <a href="../staff/staff.php?divisi_id=<?= $divisi['id_divisi'] ?>" 
                               class="bg-green-500 w-full text-white px-3 py-2 rounded-lg shadow hover:bg-green-600 transition text-center">
                                 Staff
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 dark:text-gray-300 mt-6">Belum ada divisi dalam cabang ini.</p>
        <?php endif; ?>
    </main>

</body>
</html>