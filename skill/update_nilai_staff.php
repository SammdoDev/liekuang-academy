<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['staff_id']) || !isset($_POST['divisi_id']) || !isset($_POST['nilai'])) {
        die("Data tidak lengkap!");
    }

    $staff_id = intval($_POST['staff_id']);
    $divisi_id = intval($_POST['divisi_id']);
    $nilai_list = $_POST['nilai'];

    $totalNilai = 0;
    $jumlahNilai = 0;

    foreach ($nilai_list as $skill_id => $nilai) {
        $skill_id = intval($skill_id);
        $nilai = intval($nilai);

        if ($nilai < 1 || $nilai > 4) {
            die("Nilai harus antara 1-4.");
        }

        // Simpan nilai ke database
        $query = $conn->prepare("INSERT INTO nilai_skill (staff_id, skill_id, nilai) 
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = ?");
        $query->bind_param("iiii", $staff_id, $skill_id, $nilai, $nilai);
        $query->execute();
        $query->close();

        // Hitung total nilai dan jumlah nilai untuk rata-rata
        $totalNilai += $nilai;
        $jumlahNilai++;
    }

    // Hitung rata-rata
    $rataRata = ($jumlahNilai > 0) ? round($totalNilai / $jumlahNilai, 2) : 0;

    // Simpan rata-rata ke database di tabel staff_scores
    $queryRata = $conn->prepare("INSERT INTO staff_scores (staff_id, rata_rata) 
        VALUES (?, ?) ON DUPLICATE KEY UPDATE rata_rata = ?");
    $queryRata->bind_param("idi", $staff_id, $rataRata, $rataRata);
    $queryRata->execute();
    $queryRata->close();

    // Redirect kembali ke halaman input dengan rata-rata ditampilkan
    header("Location: input_nilai_staff.php?staff_id=$staff_id&divisi_id=$divisi_id&rata=$rataRata");
    exit();
}
?>
