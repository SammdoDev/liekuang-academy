<?php
include '../koneksi.php';

if (!isset($_GET['divisi_id'])) {
    die("Divisi tidak ditemukan!");
}

$divisi_id = intval($_GET['divisi_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_skill = $_POST['nama_skill'];

    $stmt = $conn->prepare("INSERT INTO skill (nama_skill, id_divisi) VALUES (?, ?)");
    $stmt->bind_param("si", $nama_skill, $divisi_id);

    if ($stmt->execute()) {
        header("Location: skill.php?divisi_id=$divisi_id");
        exit();
    } else {
        echo "<p class='text-red-600'>Gagal menambahkan skill.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Skill</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center mb-4">Tambah Skill</h1>

    <form method="POST">
        <label class="block">
            <span class="text-gray-700">Nama Skill:</span>
            <input type="text" name="nama_skill" required class="block w-full p-2 border rounded">
        </label>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-4">
            Simpan Skill
        </button>
    </form>

    <div class="mt-4">
        <a href="skill.php?divisi_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline">⬅ Kembali ke Skill</a>
    </div>
</div>
</body>
</html>
