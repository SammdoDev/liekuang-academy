<?php
include 'koneksi.php';
include 'table.php';
session_start();

// Ambil daftar cabang dan urutkan berdasarkan ID
$queryCabang = "SELECT * FROM cabang ORDER BY id_cabang ASC";
$resultCabang = $conn->query($queryCabang);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cabang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .sidebar-active {
            background-color: #4B5563;
        }
    </style>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<aside class="w-64 bg-gray-800 text-white min-h-screen p-6 shadow-lg">
    <h2 class="text-2xl font-bold mb-8 text-center border-b border-gray-600 pb-4">Dashboard</h2>
    <nav class="space-y-3">
        <a href="index.php" class="flex items-center py-3 px-4 rounded-lg sidebar-active hover:bg-gray-600 transition-all">
            <i class="fas fa-home mr-3"></i> Home
        </a>
        <a href="staff/staff.php" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-600 transition-all">
            <i class="fas fa-users mr-3"></i> Staff
        </a>
        <a href="divisi/divisi.php" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-600 transition-all">
            <i class="fas fa-sitemap mr-3"></i> Divisi
        </a>
        <div class="border-t border-gray-600 my-4"></div>
        <a href="logout.php" class="flex items-center py-3 px-4 rounded-lg hover:bg-red-600 transition-all mt-auto">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Daftar Cabang</h1>
        <a href="tambah_cabang.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center">
            <i class="fas fa-plus mr-2"></i> Tambah Cabang
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php while ($cabang = $resultCabang->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-lg shadow-lg border hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold text-gray-700"><?= htmlspecialchars($cabang['nama_cabang']) ?></h2>
                <p class="text-gray-500 text-sm mt-2">ID: <?= $cabang['id_cabang'] ?></p>
                <div class="flex mt-4 space-x-2">
                <a href="divisi/divisi.php?cabang_id=<?= $cabang['id_cabang'] ?>" 
                   class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
                      Lihat
                </a>
                    <button onclick="aksesModal(<?= $cabang['id_cabang'] ?>, '<?= htmlspecialchars($cabang['nama_cabang']) ?>')" 
                            class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center justify-center">
                        <i class="fas fa-lock mr-2"></i> Akses
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<!-- Modal untuk password -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold" id="modalTitle">Akses Cabang</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="passwordForm" method="post" action="akses_cabang.php">
            <input type="hidden" id="cabangId" name="cabang_id">
            <div class="mb-4">
                <label for="password" class="block text-gray-700 mb-2">Masukkan Password:</label>
                <input type="password" id="password" name="password" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2 hover:bg-gray-400 transition">
                    Batal
                </button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    Masuk
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function aksesModal(id, nama) {
        document.getElementById('cabangId').value = id;
        document.getElementById('modalTitle').innerText = 'Akses Cabang: ' + nama;
        document.getElementById('passwordModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('passwordModal').classList.add('hidden');
        document.getElementById('password').value = '';
    }
    
    // Tutup modal jika user mengklik di luar modal
    window.onclick = function(event) {
        let modal = document.getElementById('passwordModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>