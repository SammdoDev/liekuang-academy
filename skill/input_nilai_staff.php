<?php
include '../koneksi.php';

if (!isset($_GET['staff_id']) || !isset($_GET['divisi_id'])) {
    die("Staff atau divisi tidak ditemukan!");
}

$staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : '';
$divisi_id = isset($_GET['divisi_id']) ? $_GET['divisi_id'] : '';

// Ambil data staff berdasarkan staff_id
$queryStaff = $conn->prepare("SELECT nama FROM staff WHERE id = ?");
$queryStaff->bind_param("i", $staff_id);
$queryStaff->execute();
$resultStaff = $queryStaff->get_result();
$staff = $resultStaff->fetch_assoc();

// Ambil daftar skill berdasarkan divisi
$querySkills = $conn->prepare("SELECT id, nama_skill FROM skills WHERE id_divisi = ?");
$querySkills->bind_param("i", $divisi_id);
$querySkills->execute();
$resultSkills = $querySkills->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Skill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Fungsi untuk mewarnai input berdasarkan nilai
        function updateColor(input) {
            let value = parseInt(input.value);
            if (value === 4) {
                input.style.backgroundColor = 'green';
                input.style.color = 'white';
            } else if (value === 3) {
                input.style.backgroundColor = 'blue';
                input.style.color = 'white';
            } else if (value === 2) {
                input.style.backgroundColor = 'yellow';
                input.style.color = 'black';
            } else if (value === 1) {
                input.style.backgroundColor = 'red';
                input.style.color = 'white';
            } else {
                input.style.backgroundColor = '';
                input.style.color = '';
            }
        }

        // Fungsi untuk hanya menerima angka 1-4
        function restrictInput(event) {
            const input = event.target;
            const value = input.value;

            // Memastikan input hanya bisa berupa angka 1, 2, 3, atau 4
            if (!/^[1-4]$/.test(value)) {
                input.value = ''; // Hapus input jika tidak valid
            }
        }
    </script>
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Input Nilai Skill Staff</h1>

        <?php if ($staff): ?>
            <p class="text-xl text-center mb-4">Nama Staff: <?= htmlspecialchars($staff['nama']) ?></p>
        <?php else: ?>
            <p class="text-red-500 text-center mb-4">Staff tidak ditemukan!</p>
        <?php endif; ?>

        <form method="POST" action="update_nilai_staff.php">
            <input type="hidden" name="staff_id" value="<?= $staff_id ?>">

            <table class="min-w-full table-auto">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border text-left">Skill</th>
                        <th class="px-4 py-2 border text-left">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($skill = $resultSkills->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($skill['nama_skill']) ?></td>
                            <td class="px-4 py-2 border">
                                <input type="text" name="nilai[<?= $skill['id'] ?>]" placeholder="1-4"
                                    class="w-full p-2 border rounded" oninput="updateColor(this)"
                                    oninput="restrictInput(event)" maxlength="1" required>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition mt-4">
                Simpan Nilai
            </button>
        </form>

        <br><br>
        <a href="staff/staff.php?staff_id=<?php echo $staff_id; ?>&divisi_id=<?php echo $divisi_id; ?>"
            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            Kembali ke Menu
        </a>

    </div>
</body>

</html>