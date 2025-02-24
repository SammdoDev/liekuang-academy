<?php
include '../koneksi.php';

if (!isset($_GET['cabang_id'])) {
    die("Cabang tidak ditemukan!");
}

$cabang_id = intval($_GET['cabang_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_divisi = $_POST['nama_divisi'];

    $stmt = $conn->prepare("INSERT INTO divisi (nama_divisi, id_cabang) VALUES (?, ?)");
    $stmt->bind_param("si", $nama_divisi, $cabang_id);

    if ($stmt->execute()) {
        header("Location: divisi.php?cabang_id=$cabang_id");
        exit();
    } else {
        echo "<p class='text-red-600'>Gagal menambahkan divisi.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Divisi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Tambah Divisi</h1>

    <form method="POST">
        <label class="block">
            <span class="text-gray-700">Nama Divisi:</span>
            <input type="text" name="nama_divisi" required class="block w-full p-2 border rounded">
        </label>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-4">
            Simpan Divisi
        </button>
    </form>

    <div class="mt-4">
        <a href="divisi.php?cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>
</div>
</body>
</html>
