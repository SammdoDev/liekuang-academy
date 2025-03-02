<?php
include 'koneksi.php';

// Ambil daftar cabang dari MySQL
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

    <!-- Menampilkan daftar cabang dari MySQL -->
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

    <!-- Menampilkan daftar cabang dari Firebase -->
    <h2 class="text-2xl font-bold mt-8">Data Cabang dari Firebase</h2>
    <div id="firebase-data" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mt-4"></div>
</div>

<!-- Firebase Script -->
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
    import { getDatabase, ref, onValue, push } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js";

    // Konfigurasi Firebase (ganti dengan milikmu)
    const firebaseConfig = {
        apiKey: "YOUR_API_KEY",
        authDomain: "YOUR_AUTH_DOMAIN",
        databaseURL: "YOUR_DATABASE_URL",
        projectId: "YOUR_PROJECT_ID",
        storageBucket: "YOUR_STORAGE_BUCKET",
        messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
        appId: "YOUR_APP_ID"
    };

    // Inisialisasi Firebase
    const app = initializeApp(firebaseConfig);
    const database = getDatabase(app);
    const dbRef = ref(database, "cabang");

    // Ambil data cabang dari Firebase
    onValue(dbRef, (snapshot) => {
        let firebaseDataContainer = document.getElementById("firebase-data");
        firebaseDataContainer.innerHTML = ""; // Kosongkan sebelum menambahkan baru
        snapshot.forEach((childSnapshot) => {
            let cabang = childSnapshot.val();
            let div = document.createElement("div");
            div.classList = "bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition duration-200";
            div.innerHTML = `
                <h2 class="text-xl font-semibold text-gray-700">${cabang.nama_cabang}</h2>
                <p class="text-gray-500 text-sm mt-2">ID: ${childSnapshot.key}</p>
            `;
            firebaseDataContainer.appendChild(div);
        });
    });

</script>

</body>
</html>
