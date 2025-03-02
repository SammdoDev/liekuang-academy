<?php
include '../koneksi.php';

// Pastikan parameter `divisi_id` tersedia di URL
if (!isset($_GET['divisi_id']) || empty($_GET['divisi_id'])) {
    die("❌ Divisi tidak ditemukan! Periksa parameter URL.");
}

$divisi_id = intval($_GET['divisi_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];

    // Ambil id_cabang berdasarkan divisi_id
    $queryCabang = $conn->prepare("SELECT cabang_id FROM divisi WHERE id = ?");
    $queryCabang->bind_param("i", $divisi_id);
    $queryCabang->execute();
    $resultCabang = $queryCabang->get_result();
    $cabang = $resultCabang->fetch_assoc();

    if (!$cabang) {
        die("❌ Cabang tidak ditemukan untuk divisi ini.");
    }

    $id_cabang = $cabang['cabang_id']; // Dapatkan id_cabang dari divisi

    // 🔹 Perbaiki query INSERT agar menyertakan id_cabang
    $stmt = $conn->prepare("INSERT INTO staff (nama, id_divisi, id_cabang) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $nama, $divisi_id, $id_cabang);

    if ($stmt->execute()) {
        header("Location: staff.php?divisi_id=$divisi_id");
        exit();
    } else {
        echo "<p class='text-red-600'>❌ Gagal menambahkan staff.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Tambah Staff</h1>

    <form method="POST">
        <label class="block">
            <span class="text-gray-700">Nama Staff:</span>
            <input type="text" name="nama" required class="block w-full p-2 border rounded">
        </label>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-4">
            Simpan Staff
        </button>
    </form>

    <div class="mt-4">
        <a href="staff.php?divisi_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Staff</a>
    </div>
</div>
</body>
</html>
