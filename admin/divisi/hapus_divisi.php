<?php
include '../../koneksi.php';

// Periksa apakah ID divisi ada
if (!isset($_GET['id'])) {
    die("Data tidak valid.");
}

// Ambil ID divisi dari parameter GET
$divisi_id = intval($_GET['id']);

// Query untuk mendapatkan data divisi sebelum menghapus
$query = $conn->prepare("SELECT * FROM divisi WHERE id_divisi = ?");
$query->bind_param("i", $divisi_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    die("Divisi tidak ditemukan.");
}

$divisi = $result->fetch_assoc();
$cabang_id = $divisi['id_cabang']; // Ambil cabang_id dari divisi yang akan dihapus

// Query untuk menghapus divisi berdasarkan id_divisi
$stmt = $conn->prepare("DELETE FROM divisi WHERE id_divisi = ?");
$stmt->bind_param("i", $divisi_id);

if ($stmt->execute()) {
    // Redirect ke halaman daftar divisi sesuai dengan cabang_id setelah berhasil menghapus divisi
    header("Location: divisi.php?cabang_id=" . $cabang_id);
    exit;
} else {
    echo "<p class='text-red-600'>Gagal menghapus divisi.</p>";
}
?>
