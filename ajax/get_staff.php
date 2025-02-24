<?php
include '../koneksi.php';

if (isset($_GET['divisi_id'])) {
    $divisi_id = intval($_GET['divisi_id']);
    $querySkills = $conn->prepare("SELECT id, nama_skill FROM skills WHERE id_divisi = ?");
    $querySkills->bind_param("i", $divisi_id);
    $querySkills->execute();
    $resultSkills = $querySkills->get_result();

    echo "<option value=''>-- Pilih Skill --</option>";
    while ($skill = $resultSkills->fetch_assoc()) {
        echo "<option value='" . $skill['id'] . "'>" . htmlspecialchars($skill['nama_skill']) . "</option>";
    }
}
?>
