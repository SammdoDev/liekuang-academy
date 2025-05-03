<?php
include '../koneksi.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    die("Parameter tidak lengkap!");
}

$cabang_id = intval($_GET['id']);

// Get current cabang data
$query = $conn->prepare("SELECT * FROM cabang WHERE id_cabang = ?");
$query->bind_param("i", $cabang_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    die("Cabang tidak ditemukan!");
}

$cabang = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_cabang = $_POST['nama_cabang'];

    // Validate input
    if (empty($nama_cabang)) {
        $error = "Nama cabang tidak boleh kosong!";
    } else {
        // Update the cabang
        $update_query = $conn->prepare("UPDATE cabang SET nama_cabang = ? WHERE id_cabang = ?");
        $update_query->bind_param("si", $nama_cabang, $cabang_id);

        if ($update_query->execute()) {
            // Redirect back to the cabang list
            header("Location: cabang.php"); // Redirect to cabang list page
            exit;
        } else {
            $error = "Gagal memperbarui cabang: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cabang - <?= htmlspecialchars($cabang['nama_cabang']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Edit Cabang</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="nama_cabang" class="block text-gray-700 font-bold mb-2">Nama Cabang</label>
            <input type="text" id="nama_cabang" name="nama_cabang" value="<?= htmlspecialchars($cabang['nama_cabang']) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500" required>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="cabang.php" class="text-blue-600 hover:underline">Kembali</a>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Simpan Perubahan</button>
        </div>
    </form>
</div>

</body>
</html>
