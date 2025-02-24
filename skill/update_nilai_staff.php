<?php
include '../koneksi.php';

// Pastikan staff_id ada di POST
if (!isset($_POST['staff_id']) || empty($_POST['staff_id'])) {
    die("Staff tidak ditemukan!");
}

$staff_id = intval($_POST['staff_id']);
$nilai = $_POST['nilai']; // Nilai yang diinputkan

// Proses penyimpanan nilai skill
foreach ($nilai as $skill_id => $nilai_skill) {
    // Pastikan nilai skill hanya 1-4
    if ($nilai_skill < 1 || $nilai_skill > 4) {
        continue; // Skip jika nilai tidak valid
    }

    // Periksa apakah sudah ada nilai skill untuk staff ini
    $queryCheck = $conn->prepare("SELECT id FROM nilai_skill WHERE staff_id = ? AND skill_id = ?");
    $queryCheck->bind_param("ii", $staff_id, $skill_id);
    if (!$queryCheck->execute()) {
        die("Error executing SELECT query: " . $queryCheck->error);
    }
    $resultCheck = $queryCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Jika sudah ada, update nilai
        $queryUpdate = $conn->prepare("UPDATE nilai_skill SET nilai = ? WHERE staff_id = ? AND skill_id = ?");
        $queryUpdate->bind_param("iii", $nilai_skill, $staff_id, $skill_id);
        if (!$queryUpdate->execute()) {
            die("Error executing UPDATE query: " . $queryUpdate->error);
        }
    } else {
        // Jika belum ada, insert nilai
        $queryInsert = $conn->prepare("INSERT INTO nilai_skill (staff_id, skill_id, nilai) VALUES (?, ?, ?)");
        $queryInsert->bind_param("iii", $staff_id, $skill_id, $nilai_skill);
        if (!$queryInsert->execute()) {
            die("Error executing INSERT query: " . $queryInsert->error);
        }
    }
}

// Redirect kembali ke halaman input nilai
header("Location: input_nilai_staff.php?staff_id=$staff_id&divisi_id=" . $_GET['divisi_id']);
exit;
?>
