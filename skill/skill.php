<?php
include '../koneksi.php';

if (!isset($_GET['divisi_id'])) {
    die("Divisi tidak ditemukan!");
}

$divisi_id = intval($_GET['divisi_id']);

// Get the division name and branch info
$divisi_query = $conn->prepare("
    SELECT d.nama_divisi, c.nama_cabang, c.id_cabang
    FROM divisi d
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE d.id_divisi = ?
");
$divisi_query->bind_param("i", $divisi_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();

if ($divisi_result->num_rows > 0) {
    $divisi_data = $divisi_result->fetch_assoc();
    $divisi_name = $divisi_data['nama_divisi'];
    $cabang_name = $divisi_data['nama_cabang'];
    $cabang_id = $divisi_data['id_cabang'];
} else {
    $divisi_name = 'Unknown Division';
    $cabang_name = 'Unknown Branch';
    $cabang_id = 0;
}

// Modified query to remove average rating calculation
$query = $conn->prepare("
    SELECT s.*, 
           COUNT(DISTINCT st.id_staff) as jumlah_staff
    FROM skill s
    LEFT JOIN staff st ON s.id_skill = st.id_skill
    WHERE s.id_divisi = ?
    GROUP BY s.id_skill
");
$query->bind_param("i", $divisi_id);
$query->execute();
$result = $query->get_result();

// Get the total staff count for this division
$staff_count_query = $conn->prepare("
    SELECT COUNT(*) as total
    FROM staff
    WHERE id_divisi = ?
");
$staff_count_query->bind_param("i", $divisi_id);
$staff_count_query->execute();
$staff_count_result = $staff_count_query->get_result();
$staff_count_data = $staff_count_result->fetch_assoc();
$total_staff = $staff_count_data['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Skill - <?= htmlspecialchars($divisi_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">   
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold">Daftar Skill</h1>
            <div class="text-lg text-gray-600 mt-1">
                Divisi: <?= htmlspecialchars($divisi_name) ?>
            </div>
            <div class="text-lg text-gray-600 mt-1">
                Total Staff: <?= $total_staff ?> orang
            </div>
        </div>
        <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded">
            <?= htmlspecialchars($cabang_name) ?>
        </span>
    </div>

    <a href="tambah_skill.php?divisi_id=<?= $divisi_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4 inline-block">
        + Tambah Skill
    </a>

    <div class="overflow-x-auto mt-4">
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-200 text-gray-700">
                    <th class="py-2 px-4 text-left">Nama Skill</th>
                    <th class="py-2 px-4 text-center">Jumlah Staff</th>
                    <th class="py-2 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($skill = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <a href="../staff/staff.php?skill_id=<?= $skill['id_skill'] ?>&divisi_id=<?= $divisi_id ?>&cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">
                                    <?= htmlspecialchars($skill['nama_skill']) ?>
                                </a>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm font-medium">
                                    <?= $skill['jumlah_staff'] ?> staff
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="edit_skill.php?id=<?= $skill['id_skill'] ?>&divisi_id=<?= $divisi_id ?>" 
                                   class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Edit</a>
                                <a href="hapus_skill.php?id=<?= $skill['id_skill'] ?>&divisi_id=<?= $divisi_id ?>" 
                                   class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-4 px-4 text-center text-gray-500">Belum ada skill yang terdaftar</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="../divisi/divisi.php?cabang_id=<?= $cabang_id ?>" class="text-blue-600 hover:underline">Kembali ke Divisi</a>
    </div>
</div>
</body>
</html>