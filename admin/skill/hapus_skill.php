<?php
include '../koneksi.php';

if (!isset($_GET['id']) || !isset($_GET['divisi_id'])) {
    die("Data tidak valid.");
}

$id = intval($_GET['id']);
$divisi_id = intval($_GET['divisi_id']);

$stmt = $conn->prepare("DELETE FROM skill WHERE id_skill = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: skill.php?divisi_id=$divisi_id");
} else {
    echo "<p class='text-red-600'>Gagal menghapus skill.</p>";
}
?>
