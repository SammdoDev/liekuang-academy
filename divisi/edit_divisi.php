<?php
include '../koneksi.php';

if (!isset($_GET['id']) || !isset($_GET['cabang_id'])) {
    die("Data tidak valid.");
}

$id = intval($_GET['id']);
$cabang_id = intval($_GET['cabang_id']);

$stmt = $conn->prepare("SELECT * FROM divisi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$divisi = $result->fetch_assoc();

if (!$divisi) {
    die("Divisi tidak ditemukan!");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Divisi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Edit Divisi</h1>

    <form method="POST" action="update_divisi.php">
        <input type="hidden" name="id" value="<?= $divisi['id'] ?>">
        <input type="hidden" name="cabang_id" value="<?= $cabang_id ?>">

        <label class="block">
            <span class="text-gray-700">Nama Divisi:</span>
            <input type="text" name="nama_divisi" value="<?= htmlspecialchars($divisi['nama_divisi']) ?>" required class="block w-full p-2 border rounded">
        </label>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-4">
            Simpan Perubahan
        </button>
    </form>

    <div class="mt-4">
        <a href="divisi.php?cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Divisi</a>
    </div>
</div>
</body>
</html>
