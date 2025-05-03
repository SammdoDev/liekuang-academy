<?php
include '../koneksi.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    die("Parameter tidak lengkap!");
}



$divisi_id = intval($_GET['id']);


// Get current division data
$query = $conn->prepare("SELECT d.*, c.nama_cabang 
                         FROM divisi d
                         JOIN cabang c ON d.id_cabang = c.id_cabang
                         WHERE d.id_divisi = ?");
$query->bind_param("i", $divisi_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    die("Divisi tidak ditemukan!");
}

$divisi = $result->fetch_assoc();
$cabang_id = $divisi['id_cabang'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_divisi = $_POST['nama_divisi'];

    // Validate input
    if (empty($nama_divisi)) {
        $error = "Nama divisi tidak boleh kosong!";
    } else {
        // Update the division
        $update_query = $conn->prepare("UPDATE divisi SET nama_divisi = ? WHERE id_divisi = ?");
        $update_query->bind_param("si", $nama_divisi, $divisi_id);

        if ($update_query->execute()) {
            // Redirect back to the division list
            $cabang_id = $divisi['id_cabang']; // ambil dari hasil query awal
            header("Location: divisi.php?cabang_id=" . $cabang_id);
            exit;
            
        } else {
            $error = "Gagal memperbarui divisi: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Divisi - <?= htmlspecialchars($divisi['nama_divisi']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Edit Divisi</h1>
        <div class="text-lg text-gray-600 mt-1">
            Cabang: <?= htmlspecialchars($divisi['nama_cabang']) ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="nama_divisi" class="block text-gray-700 font-bold mb-2">Nama Divisi</label>
            <input type="text" id="nama_divisi" name="nama_divisi" value="<?= htmlspecialchars($divisi['nama_divisi']) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500" required>
        </div>

        <div class="flex items-center justify-between mt-6">
        <a href="divisi.php?cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">Kembali</a>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Simpan Perubahan</button>
        </div>
    </form>
</div>

</body>
</html>
