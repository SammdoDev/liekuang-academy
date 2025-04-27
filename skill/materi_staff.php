<?php
include '../koneksi.php';

if (!isset($_GET['staff_id']) || !is_numeric($_GET['staff_id'])) {
    die("<p class='text-red-500 text-center font-bold'>❌ Staff tidak ditemukan! Periksa URL.</p>");
}

$staff_id = intval($_GET['staff_id']);

// Ambil informasi staff termasuk divisinya
$queryStaff = $conn->prepare("SELECT nama, id_divisi FROM staff WHERE id = ?");
$queryStaff->bind_param("i", $staff_id);
$queryStaff->execute();
$resultStaff = $queryStaff->get_result();
$staff = $resultStaff->fetch_assoc();
$queryStaff->close();

// Jika staff tidak ditemukan
if (!$staff) {
    die("<p class='text-red-500 text-center font-bold'>❌ Staff tidak ditemukan!</p>");
}

$id_divisi = $staff['id_divisi']; // Divisi staff

// Ambil daftar skill yang sesuai dengan divisi
$query = $conn->prepare("
    SELECT m.id, m.skill AS nama_materi, m.nilai_rata_rata
    FROM materi_staff m
    WHERE (m.staff_id = ? OR m.staff_id IS NULL) 
    AND m.id_divisi = ?
");
$query->bind_param("ii", $staff_id, $id_divisi);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Penilaian Skill Staff</h1>
        <p class="text-xl text-center mb-4">Nama Staff: <?= htmlspecialchars($staff['nama']) ?></p>

        <?php if ($result->num_rows > 0): ?>
            <table class="min-w-full border border-gray-300 shadow-md rounded-lg">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 border">Skill</th>
                        <th class="px-4 py-2 border">Nilai Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="cursor-pointer hover:bg-blue-100 transition duration-200"
                            onclick="window.location.href='input_kinerja_staff.php?staff_id=<?= $staff_id ?>&skill=<?= urlencode($row['nama_materi']) ?>'">
                            <td class="px-4 py-2 border font-semibold"><?= htmlspecialchars($row['nama_materi']) ?></td>
                            <td class="px-4 py-2 border text-center font-bold">
                                <?= ($row['nilai_rata_rata'] > 0) ? $row['nilai_rata_rata'] : '<span class="text-gray-400">Belum Dinilai</span>' ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500 text-center font-bold mt-4">🚫 Tidak ada skill yang tersedia untuk divisi ini.</p>
        <?php endif; ?>

        <a href="staff.php"
            class="block mt-4 text-center bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
            Kembali ke Menu Staff
        </a>
    </div>
</body>

</html>
