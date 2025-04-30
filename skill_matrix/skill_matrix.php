<?php
include '../koneksi.php';

if (!isset($_GET['skill_id']) || !isset($_GET['divisi_id']) || !isset($_GET['cabang_id'])) {
    // Check if we're coming from staff page with staff ID
    if (isset($_GET['id_staff']) && isset($_GET['id_skill']) && isset($_GET['divisi_id'])) {
        $staff_id = intval($_GET['id_staff']);
        $skill_id = intval($_GET['id_skill']);
        $divisi_id = intval($_GET['divisi_id']);

        // Get cabang ID from divisi
        $cabang_query = $conn->prepare("SELECT id_cabang FROM divisi WHERE id_divisi = ?");
        $cabang_query->bind_param("i", $divisi_id);
        $cabang_query->execute();
        $cabang_result = $cabang_query->get_result();

        if ($cabang_result->num_rows > 0) {
            $cabang_data = $cabang_result->fetch_assoc();
            $cabang_id = $cabang_data['id_cabang'];
        } else {
            die("Divisi tidak ditemukan!");
        }
    } else {
        die("Data tidak lengkap!");
    }
} else {
    $skill_id = intval($_GET['skill_id']);
    $divisi_id = intval($_GET['divisi_id']);
    $cabang_id = intval($_GET['cabang_id']);
    $staff_id = isset($_GET['id_staff']) ? intval($_GET['id_staff']) : 0;
}

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

// Get staff from this division with specific staff ID filter if provided
$staff_query_sql = "
    SELECT id_staff, nama_staff
    FROM staff
    WHERE id_divisi = ?
";

// Add staff_id filter if provided
if ($staff_id > 0) {
    $staff_query = $conn->prepare("
        SELECT id_staff, nama_staff
        FROM staff
        WHERE id_divisi = ? AND id_staff = ?
    ");
    $staff_query->bind_param("ii", $divisi_id, $staff_id);
} else {
    die("ID staff wajib disertakan!");
}


$staff_query->execute();
$staff_result = $staff_query->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    foreach ($_POST['staff'] as $current_staff_id => $values) {
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
        $check_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $current_staff_id);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing record - removed rata_rata from the query since it's a generated column
            $update_query = $conn->prepare("
                UPDATE skill_matrix
                SET total_look = ?,
                    konsultasi_komunikasi = ?,
                    teknik = ?,
                    kerapian_kebersihan = ?,
                    produk_knowledge = ?
                WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
            ");
            // 5 nilai double, 4 nilai integer → total 9
            $update_query->bind_param(
                "dddddiiii",
                $total_look,
                $konsultasi_komunikasi,
                $teknik,
                $kerapian_kebersihan,
                $produk_knowledge,
                $skill_id,
                $divisi_id,
                $cabang_id,
                $current_staff_id
            );

            $update_query->execute();
        } else {
            // Insert new record - removed rata_rata from the query since it's a generated column
            $insert_query = $conn->prepare("
                INSERT INTO skill_matrix (
                    id_skill, id_divisi, id_cabang, id_staff, 
                    total_look, konsultasi_komunikasi, teknik, 
                    kerapian_kebersihan, produk_knowledge
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_query->bind_param(
                "iiiiidddd",
                $skill_id,
                $divisi_id,
                $cabang_id,
                $current_staff_id,
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
    header("Location: " . $_SERVER['PHP_SELF'] . "?skill_id=$skill_id&divisi_id=$divisi_id&cabang_id=$cabang_id&success=1");
    exit;
}

// Check if we have a success message
$success_message = isset($_GET['success']) && $_GET['success'] == 1 ? "Data berhasil disimpan!" : "";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Matrix - <?php echo htmlspecialchars($skill_name); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        .table-fixed {
            table-layout: fixed;
        }

        .table-fixed th,
        .table-fixed td {
            vertical-align: middle;
        }

        .rating-input {
            width: 60px;
        }

        .alert-float {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2>Skill Matrix: <?php echo htmlspecialchars($skill_name); ?></h2>
                <p class="lead">
                    Divisi: <?php echo htmlspecialchars($divisi_name); ?> |
                    Cabang: <?php echo htmlspecialchars($cabang_name); ?>
                </p>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-float alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="table-responsive">
                <table class="table table-bordered table-fixed">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" style="width: 200px;">Nama Staff</th>
                            <th colspan="5" class="text-center">Kriteria Penilaian</th>
                            <th rowspan="2" style="width: 80px;">Rata-rata</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="width: 100px;">Total Look</th>
                            <th class="text-center" style="width: 100px;">Konsultasi & Komunikasi</th>
                            <th class="text-center" style="width: 100px;">Teknik</th>
                            <th class="text-center" style="width: 100px;">Kerapian & Kebersihan</th>
                            <th class="text-center" style="width: 100px;">Produk Knowledge</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($staff_result->num_rows > 0) {
                            while ($staff = $staff_result->fetch_assoc()) {
                                // Get existing skill matrix data for this staff
                                $matrix_query = $conn->prepare("
                                SELECT * FROM skill_matrix
                                WHERE id_skill = ? AND id_divisi = ? AND id_cabang = ? AND id_staff = ?
                            ");
                                $matrix_query->bind_param("iiii", $skill_id, $divisi_id, $cabang_id, $staff['id_staff']);
                                $matrix_query->execute();
                                $matrix_result = $matrix_query->get_result();

                                $total_look = "";
                                $konsultasi_komunikasi = "";
                                $teknik = "";
                                $kerapian_kebersihan = "";
                                $produk_knowledge = "";
                                $rata_rata = "";

                                if ($matrix_result->num_rows > 0) {
                                    $matrix_data = $matrix_result->fetch_assoc();
                                    $total_look = $matrix_data['total_look'];
                                    $konsultasi_komunikasi = $matrix_data['konsultasi_komunikasi'];
                                    $teknik = $matrix_data['teknik'];
                                    $kerapian_kebersihan = $matrix_data['kerapian_kebersihan'];
                                    $produk_knowledge = $matrix_data['produk_knowledge'];
                                    $rata_rata = $matrix_data['rata_rata'];
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staff['nama_staff']); ?></td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="5"
                                            class="form-control rating-input total-look"
                                            name="staff[<?php echo $staff['id_staff']; ?>][total_look]"
                                            value="<?php echo $total_look; ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="5"
                                            class="form-control rating-input konsultasi"
                                            name="staff[<?php echo $staff['id_staff']; ?>][konsultasi_komunikasi]"
                                            value="<?php echo $konsultasi_komunikasi; ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="5" class="form-control rating-input teknik"
                                            name="staff[<?php echo $staff['id_staff']; ?>][teknik]"
                                            value="<?php echo $teknik; ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="5"
                                            class="form-control rating-input kerapian"
                                            name="staff[<?php echo $staff['id_staff']; ?>][kerapian_kebersihan]"
                                            value="<?php echo $kerapian_kebersihan; ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="5" class="form-control rating-input produk"
                                            name="staff[<?php echo $staff['id_staff']; ?>][produk_knowledge]"
                                            value="<?php echo $produk_knowledge; ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control rata-rata" readonly
                                            value="<?php echo $rata_rata ? number_format($rata_rata, 1) : ''; ?>">
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">Tidak ada staff ditemukan pada divisi ini.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="submit" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Calculate average when input values change
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const inputs = row.querySelectorAll('.rating-input');
                const rataRata = row.querySelector('.rata-rata');

                if (inputs.length > 0 && rataRata) {
                    inputs.forEach(input => {
                        input.addEventListener('input', function () {
                            calculateAverage(inputs, rataRata);
                        });
                    });

                    // Calculate initial values
                    calculateAverage(inputs, rataRata);
                }
            });

            // Auto-dismiss alert after 5 seconds
            const alertElement = document.querySelector('.alert-float');
            if (alertElement) {
                setTimeout(function () {
                    const closeButton = alertElement.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            }
        });

        function calculateAverage(inputs, outputElement) {
            let sum = 0;
            let validCount = 0;

            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    sum += value;
                    validCount++;
                }
            });

            if (validCount > 0) {
                const average = sum / validCount;
                outputElement.value = average.toFixed(1);
            } else {
                outputElement.value = '';
            }
        }
    </script>
</body>

</html>