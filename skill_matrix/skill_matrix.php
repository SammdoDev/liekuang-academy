<?php
include '../koneksi.php';

if (!isset($_GET['skill_id']) || !isset($_GET['divisi_id']) || !isset($_GET['cabang_id'])) {
    die("Data tidak lengkap!");
}

$skill_id = intval($_GET['skill_id']);
$divisi_id = intval($_GET['divisi_id']);
$cabang_id = intval($_GET['cabang_id']);

// Get skill information
$skill_query = $conn->prepare("
    SELECT nama_skill
    FROM skill
    WHERE id_skill = ?
");
$skill_query->bind_param("i", $skill_id);
$skill_query->execute();
$skill_result = $skill_query->get_result();

if ($skill_result->num_rows == 0) {
    die("Skill tidak ditemukan!");
}

$skill_data = $skill_result->fetch_assoc();
$skill_name = $skill_data['nama_skill'];

// Get division and branch information
$divisi_query = $conn->prepare("
    SELECT d.nama_divisi, c.nama_cabang
    FROM divisi d
    JOIN cabang c ON d.id_cabang = c.id_cabang
    WHERE d.id_divisi = ? AND c.id_cabang = ?
");
$divisi_query->bind_param("ii", $divisi_id, $cabang_id);
$divisi_query->execute();
$divisi_result = $divisi_query->get_result();

if ($divisi_result->num_rows == 0) {
    die("Divisi atau cabang tidak ditemukan!");
}

$divisi_data = $divisi_result->fetch_assoc();
$divisi_name = $divisi_data['nama_divisi'];
$cabang_name = $divisi_data['nama_cabang'];

// Get staff from this division
$staff_query = $conn->prepare("
    SELECT id_staff, nama_staff
    FROM staff
    WHERE id_divisi = ?
    ORDER BY nama_staff ASC
");
$staff_query->bind_param("i", $divisi_id);
$staff_query->execute();
$staff_result = $staff_query->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    foreach ($_POST['staff'] as $staff_id => $values) {
        $total_look = floatval($values['total_look']);
        $konsultasi_komunikasi = floatval($values['konsultasi_komunikasi']);
        $teknik = floatval($values['teknik']);
        $kerapian_kebersihan = floatval($values['kerapian_kebersihan']);
        $produk_knowledge = floatval($values['produk_knowledge']);
        
        // Check if record exists
        $check_query = $conn->prepare("
            SELECT id_skill_matrix
            FROM skill_matrix
            WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
        ");
        $check_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $staff_id);
        $check_query->execute();
        $check_result = $check_query->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $update_query = $conn->prepare("
                UPDATE skill_matrix
                SET total_look = ?,
                    konsultasi_komunikasi = ?,
                    teknik = ?,
                    kerapian_kebersihan = ?,
                    produk_knowledge = ?
                WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
            ");
            $update_query->bind_param(
                "dddddiiii", // Diubah menjadi 9 parameter (5 double, 4 integer)
                $total_look,
                $konsultasi_komunikasi,
                $teknik,
                $kerapian_kebersihan,
                $produk_knowledge,
                $skill_id,
                $divisi_id,
                $cabang_id,
                $staff_id
            );
            $update_query->execute();
        } else {
            // Insert new record
            $insert_query = $conn->prepare("
                INSERT INTO skill_matrix (
                    id_skill, id_divisi, id_cabang, id_staff,
                    total_look, konsultasi_komunikasi, teknik, kerapian_kebersihan, produk_knowledge
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_query->bind_param(
                "iiiiddddd",
                $skill_id,
                $divisi_id,
                $cabang_id,
                $staff_id,
                $total_look,
                $konsultasi_komunikasi,
                $teknik,
                $kerapian_kebersihan,
                $produk_knowledge
            );
            $insert_query->execute();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: skill_matrix.php?skill_id=$skill_id&divisi_id=$divisi_id&cabang_id=$cabang_id&status=success");
    exit;
}

// Get existing skill matrix data
$matrix_data = [];

// Fetch skill matrix data with proper join to staff table
$matrix_query = $conn->prepare("
    SELECT sm.*, s.nama_staff
    FROM skill_matrix sm
    JOIN staff s ON sm.id_staff = s.id_staff
    WHERE sm.id_skill = ? AND sm.id_divisi = ? AND sm.id_cabang = ?
");

$matrix_query->bind_param("iii", $skill_id, $divisi_id, $cabang_id);
$matrix_query->execute();
$matrix_result = $matrix_query->get_result();

while ($row = $matrix_result->fetch_assoc()) {
    $matrix_data[$row['id_staff']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Matrix - <?= htmlspecialchars($skill_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }
        .number-input {
            position: relative;
            width: 100%;
        }
        .rating-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .input-with-icon {
            position: relative;
        }
        .input-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hover-scale:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-gradient {
            background: linear-gradient(to right, #4ade80, #22c55e);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(to right, #22c55e, #16a34a);
            transform: translateY(-2px);
        }
        .table-header {
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }
    </style>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="card bg-white p-6 rounded-xl shadow-md mb-6 animate-fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-chart-bar text-green-500 mr-3"></i>
                    <?= htmlspecialchars($skill_name) ?>
                </h1>
                <div class="flex items-center text-gray-600 mt-2">
                    <i class="fas fa-building mr-2"></i>
                    <span class="font-medium"><?= htmlspecialchars($divisi_name) ?></span>
                    <span class="mx-2">•</span>
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?= htmlspecialchars($cabang_name) ?>
                    </span>
                </div>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="../skill/skill.php?divisi_id=<?= $divisi_id ?>" 
                   class="flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Daftar Skill
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow animate-fade-in" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
            <p class="font-medium">Data berhasil disimpan!</p>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="card bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-4 table-header">
                <h2 class="text-xl font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-user-check mr-2"></i>
                    Penilaian Skill Matrix
                </h2>
                <p class="text-sm text-gray-500 mt-1">Beri nilai 0-5 untuk setiap kriteria penilaian</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-left font-semibold">Nama Staff</th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-user-tie text-blue-500 mb-1"></i>
                                    <span>Total Look</span>
                                    <span class="text-xs text-gray-500">(0-5)</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-comments text-purple-500 mb-1"></i>
                                    <span>Konsultasi & Komunikasi</span>
                                    <span class="text-xs text-gray-500">(0-5)</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-tools text-indigo-500 mb-1"></i>
                                    <span>Teknik</span>
                                    <span class="text-xs text-gray-500">(0-5)</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-broom text-yellow-500 mb-1"></i>
                                    <span>Kerapian & Kebersihan</span>
                                    <span class="text-xs text-gray-500">(0-5)</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-book text-red-500 mb-1"></i>
                                    <span>Produk Knowledge</span>
                                    <span class="text-xs text-gray-500">(0-5)</span>
                                </div>
                            </th>
                            <th class="py-3 px-4 border-b-2 border-gray-200 text-center font-semibold">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-star text-amber-500 mb-1"></i>
                                    <span>Rata-Rata</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($staff_result->num_rows > 0): ?>
                            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                                <?php 
                                $staff_id = $staff['id_staff'];
                                $total_look = $matrix_data[$staff_id]['total_look'] ?? 0;
                                $konsultasi_komunikasi = $matrix_data[$staff_id]['konsultasi_komunikasi'] ?? 0;
                                $teknik = $matrix_data[$staff_id]['teknik'] ?? 0;
                                $kerapian_kebersihan = $matrix_data[$staff_id]['kerapian_kebersihan'] ?? 0;
                                $produk_knowledge = $matrix_data[$staff_id]['produk_knowledge'] ?? 0;
                                $rata_rata = $matrix_data[$staff_id]['rata_rata'] ?? 0;
                                
                                $ratingClass = 'bg-red-100 text-red-800';
                                $ratingIcon = 'fa-exclamation-circle';
                                
                                if ($rata_rata >= 4.5) {
                                    $ratingClass = 'bg-green-500 text-white';
                                    $ratingIcon = 'fa-crown';
                                } elseif ($rata_rata >= 3.5) {
                                    $ratingClass = 'bg-green-100 text-green-800';
                                    $ratingIcon = 'fa-check-circle';
                                } elseif ($rata_rata >= 2.5) {
                                    $ratingClass = 'bg-yellow-100 text-yellow-800';
                                    $ratingIcon = 'fa-exclamation-triangle';
                                }
                                ?>
                                <tr class="border-b hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-4 border-r">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 mr-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="font-medium"><?= htmlspecialchars($staff['nama_staff']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 border-r text-center">
                                        <div class="number-input mx-auto w-20">
                                            <input type="number" name="staff[<?= $staff_id ?>][total_look]" 
                                                value="<?= $total_look ?>" min="0" max="5" step="0.1"
                                                class="w-full p-2 border border-gray-300 rounded text-center hover:border-blue-500 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:outline-none transition-all"
                                                onchange="calculateAverage(<?= $staff_id ?>)">
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 border-r text-center">
                                        <div class="number-input mx-auto w-20">
                                            <input type="number" name="staff[<?= $staff_id ?>][konsultasi_komunikasi]" 
                                                value="<?= $konsultasi_komunikasi ?>" min="0" max="5" step="0.1"
                                                class="w-full p-2 border border-gray-300 rounded text-center hover:border-purple-500 focus:border-purple-500 focus:ring focus:ring-purple-200 focus:outline-none transition-all"
                                                onchange="calculateAverage(<?= $staff_id ?>)">
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 border-r text-center">
                                        <div class="number-input mx-auto w-20">
                                            <input type="number" name="staff[<?= $staff_id ?>][teknik]" 
                                                value="<?= $teknik ?>" min="0" max="5" step="0.1"
                                                class="w-full p-2 border border-gray-300 rounded text-center hover:border-indigo-500 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:outline-none transition-all"
                                                onchange="calculateAverage(<?= $staff_id ?>)">
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 border-r text-center">
                                        <div class="number-input mx-auto w-20">
                                            <input type="number" name="staff[<?= $staff_id ?>][kerapian_kebersihan]" 
                                                value="<?= $kerapian_kebersihan ?>" min="0" max="5" step="0.1"
                                                class="w-full p-2 border border-gray-300 rounded text-center hover:border-yellow-500 focus:border-yellow-500 focus:ring focus:ring-yellow-200 focus:outline-none transition-all"
                                                onchange="calculateAverage(<?= $staff_id ?>)">
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 border-r text-center">
                                        <div class="number-input mx-auto w-20">
                                            <input type="number" name="staff[<?= $staff_id ?>][produk_knowledge]" 
                                                value="<?= $produk_knowledge ?>" min="0" max="5" step="0.1"
                                                class="w-full p-2 border border-gray-300 rounded text-center hover:border-red-500 focus:border-red-500 focus:ring focus:ring-red-200 focus:outline-none transition-all"
                                                onchange="calculateAverage(<?= $staff_id ?>)">
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="rating-circle <?= $ratingClass ?>" id="avg_container_<?= $staff_id ?>">
                                            <span id="avg_<?= $staff_id ?>"><?= number_format($rata_rata, 1) ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-8 px-4 text-center text-gray-500 border">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-user-slash text-4xl text-gray-400 mb-3"></i>
                                        <p>Belum ada staff yang terdaftar pada divisi ini</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($staff_result->num_rows > 0): ?>
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button type="submit" name="submit" class="btn-gradient text-white px-6 py-3 rounded-lg shadow-md hover:shadow-lg flex items-center font-medium">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Data
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    function calculateAverage(staffId) {
        const totalLook = parseFloat(document.querySelector(`input[name="staff[${staffId}][total_look]"]`).value) || 0;
        const konsultasiKomunikasi = parseFloat(document.querySelector(`input[name="staff[${staffId}][konsultasi_komunikasi]"]`).value) || 0;
        const teknik = parseFloat(document.querySelector(`input[name="staff[${staffId}][teknik]"]`).value) || 0;
        const kerapianKebersihan = parseFloat(document.querySelector(`input[name="staff[${staffId}][kerapian_kebersihan]"]`).value) || 0;
        const produkKnowledge = parseFloat(document.querySelector(`input[name="staff[${staffId}][produk_knowledge]"]`).value) || 0;
        
        const average = (totalLook + konsultasiKomunikasi + teknik + kerapianKebersihan + produkKnowledge) / 5;
        const avgElement = document.getElementById(`avg_${staffId}`);
        const avgContainer = document.getElementById(`avg_container_${staffId}`);
        
        avgElement.textContent = average.toFixed(1);
        
        // Update color based on average
        avgContainer.className = 'rating-circle';
        
        if (average >= 4.5) {
            avgContainer.classList.add('bg-green-500', 'text-white');
            // Add animation effect
            avgContainer.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.2)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                iterations: 1
            });
        } else if (average >= 3.5) {
            avgContainer.classList.add('bg-green-100', 'text-green-800');
        } else if (average >= 2.5) {
            avgContainer.classList.add('bg-yellow-100', 'text-yellow-800');
        } else {
            avgContainer.classList.add('bg-red-100', 'text-red-800');
        }
    }

    // Add input validation
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value < 0) this.value = 0;
            if (value > 5) this.value = 5;
        });
    });
</script>
</body>
</html>