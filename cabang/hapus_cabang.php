<?php
include '../koneksi.php';

// Periksa apakah ID cabang ada
if (!isset($_GET['id'])) {
    die("Data tidak valid.");
}

// Ambil ID cabang dari parameter GET
$cabang_id = intval($_GET['id']);

// Query untuk mendapatkan data cabang sebelum menghapus
$query = $conn->prepare("SELECT * FROM cabang WHERE id_cabang = ?");
$query->bind_param("i", $cabang_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    die("Cabang tidak ditemukan.");
}

$cabang = $result->fetch_assoc();

// Query untuk menghapus cabang berdasarkan id_cabang
$stmt = $conn->prepare("DELETE FROM cabang WHERE id_cabang = ?");
$stmt->bind_param("i", $cabang_id);

if ($stmt->execute()) {
    // Redirect ke halaman daftar cabang setelah berhasil menghapus cabang
    header("Location: cabang.php"); // Halaman daftar cabang
    exit;
} else {
    echo "<p class='text-red-600'>Gagal menghapus cabang.</p>";
}
?>
