<?php
include '../koneksi.php';

if (!isset($_GET['staff_id']) || !isset($_GET['divisi_id']) || !isset($_GET['skill'])) {
    die("<p class='text-red-500 font-bold text-center'>❌ Data tidak lengkap! Periksa URL.</p>");
}

$staff_id = intval($_GET['staff_id']);
$divisi_id = intval($_GET['divisi_id']);
$skill_judul = htmlspecialchars($_GET['skill']);

// Ambil Nama Staff
$queryStaff = $conn->prepare("SELECT nama FROM staff WHERE id = ?");
$queryStaff->bind_param("i", $staff_id);
$queryStaff->execute();
$resultStaff = $queryStaff->get_result();
$staff = $resultStaff->fetch_assoc();
$queryStaff->close();

if (!$staff) {
    die("<p class='text-red-500 font-bold text-center'>❌ Staff tidak ditemukan!</p>");
}

// Daftar sub-skill
$subSkills = [
    "total_look" => "Total Look",
    "konsultasi_komunikasi" => "Konsultasi & Komunikasi",
    "teknik" => "Teknik",
    "kerapian_kebersihan" => "Kerapian & Kebersihan",
    "speed" => "Speed",
    "produk_knowledge" => "Produk Knowledge"
];

// Ambil nilai yang sudah tersimpan
$nilai_staff = [];
$totalNilai = 0;
$jumlahSkill = count($subSkills);

foreach ($subSkills as $key => $name) {
    $queryNilai = $conn->prepare("SELECT nilai FROM kinerja_staff WHERE staff_id = ? AND kriteria = ?");
    $queryNilai->bind_param("is", $staff_id, $key);
    $queryNilai->execute();
    $resultNilai = $queryNilai->get_result();
    $nilai = $resultNilai->fetch_assoc()['nilai'] ?? null;
    $queryNilai->close();

    $nilai_staff[$key] = $nilai;
    if ($nilai !== null) {
        $totalNilai += $nilai;
    }
}

// Hitung rata-rata nilai
$rataRata = $jumlahSkill > 0 ? round($totalNilai / $jumlahSkill, 2) : 0;

// Simpan rata-rata nilai ke tabel materi_staff
$querySimpan = $conn->prepare("REPLACE INTO materi_staff (staff_id, divisi_id, skill, nilai_rata_rata) VALUES (?, ?, ?, ?)");
$querySimpan->bind_param("iisd", $staff_id, $divisi_id, $skill_judul, $rataRata);
$querySimpan->execute();
$querySimpan->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian - <?= htmlspecialchars($staff['nama']) ?> - <?= $skill_judul ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">
            Penilaian - <?= htmlspecialchars($staff['nama']) ?> - <?= $skill_judul ?>
        </h1>

        <form method="POST" action="simpan_nilai.php">
            <input type="hidden" name="staff_id" value="<?= $staff_id ?>">
            <input type="hidden" name="divisi_id" value="<?= $divisi_id ?>">

            <table class="min-w-full bg-white border rounded shadow mb-4">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 border">Skill</th>
                        <th class="px-4 py-2 border">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subSkills as $key => $name): ?>
                        <tr>
                            <td class="px-4 py-2 border text-left font-semibold"> <?= htmlspecialchars($name) ?> </td>
                            <td class="px-4 py-2 border text-center font-semibold">
                                <input type="number" name="nilai[<?= $key ?>]" min="1" max="4"
                                    value="<?= $nilai_staff[$key] !== null ? $nilai_staff[$key] : '' ?>"
                                    class="w-full p-2 border rounded text-center bg-gray-300 text-black" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-200">
                        <td class="px-4 py-2 border font-bold text-left">Rata-rata</td>
                        <td class="px-4 py-2 border font-bold text-center"><?= $rataRata ?></td>
                    </tr>
                </tfoot>
            </table>

            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded mt-2 hover:bg-blue-600 transition">
                Simpan Semua Nilai
            </button>
        </form>

    </div>
</body>

</html>
