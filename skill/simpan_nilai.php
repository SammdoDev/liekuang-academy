<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'] ?? 0;
    $divisi_id = $_POST['divisi_id'] ?? 0;

    // Pastikan staff_id dan divisi_id ada dan valid
    if ($staff_id <= 0 || $divisi_id <= 0 || empty($_POST['nilai'])) {
        die("❌ Data tidak valid!");
    }

    $stmt = $conn->prepare("REPLACE INTO kinerja_staff (staff_id, kriteria, nilai) VALUES (?, ?, ?)");

    foreach ($_POST['nilai'] as $kriteria => $nilai) {
        $nilai = intval($nilai);
        if ($nilai >= 1 && $nilai <= 4) { // Validasi nilai antara 1-4
            $stmt->bind_param("isi", $staff_id, $kriteria, $nilai);
            $stmt->execute();
        }
    }

    $stmt->close();
    $conn->close();

    // Redirect kembali ke materi_staff.php
    header("Location: materi_staff.php?staff_id=$staff_id&divisi_id=$divisi_id");
    exit();
} else {
    echo "❌ Akses ditolak!";
}
?>
