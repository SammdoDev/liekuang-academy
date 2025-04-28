<?php
include '../koneksi.php';

// Check if ID is provided
if (!isset($_GET['id']) || !isset($_GET['divisi_id'])) {
    die("Parameter tidak lengkap!");
}

$skill_id = intval($_GET['id']);
$divisi_id = intval($_GET['divisi_id']);

// Get current skill data
$query = $conn->prepare("SELECT * FROM skill WHERE id_skill = ?");
$query->bind_param("i", $skill_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    die("Skill tidak ditemukan!");
}

$skill = $result->fetch_assoc();

// Get division name
$divisi_query = $conn->prepare("
    SELECT d.nama_divisi, c.nama_cabang 
    FROM divisi d
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE d.id_divisi = ?
");
$divisi_query->bind_param("i", $divisi_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();
$divisi_data = $divisi_result->fetch_assoc();

$divisi_name = $divisi_data['nama_divisi'] ?? 'Unknown Division';
$cabang_name = $divisi_data['nama_cabang'] ?? 'Unknown Branch';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_skill = $_POST['nama_skill'];
    
    // Validate input
    if (empty($nama_skill)) {
        $error = "Nama skill tidak boleh kosong!";
    } else {
        // Update the skill
        $update_query = $conn->prepare("UPDATE skill SET nama_skill = ? WHERE id_skill = ?");
        $update_query->bind_param("si", $nama_skill, $skill_id);
        
        if ($update_query->execute()) {
            // Redirect back to the skills list
            header("Location: skill.php?divisi_id=" . $divisi_id);
            exit;
        } else {
            $error = "Gagal memperbarui skill: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Skill - <?= htmlspecialchars($skill['nama_skill']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Edit Skill</h1>
        <div class="text-lg text-gray-600 mt-1">
            Divisi: <?= htmlspecialchars($divisi_name) ?>
        </div>
        <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded mt-2 inline-block">
            <?= htmlspecialchars($cabang_name) ?>
        </span>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="nama_skill" class="block text-gray-700 font-bold mb-2">Nama Skill</label>
            <input type="text" id="nama_skill" name="nama_skill" value="<?= htmlspecialchars($skill['nama_skill']) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500" required>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="skill.php?divisi_id=<?= $divisi_id ?>" class="text-blue-600 hover:underline">Kembali</a>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Simpan Perubahan</button>
        </div>
    </form>
</div>

</body>
</html>