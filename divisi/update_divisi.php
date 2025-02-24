<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $cabang_id = intval($_POST['cabang_id']);
    $nama_divisi = $_POST['nama_divisi'];

    $stmt = $conn->prepare("UPDATE divisi SET nama_divisi = ? WHERE id = ?");
    $stmt->bind_param("si", $nama_divisi, $id);
    $stmt->execute();

    header("Location: divisi.php?cabang_id=$cabang_id");
}
?>