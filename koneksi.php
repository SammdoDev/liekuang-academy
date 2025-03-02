<?php
$host = "localhost";
$user = "root";
$pass = "123";
$db = "liekuang_academy";

// Buat koneksi ke MySQL
$conn = new mysqli($host, $user, $pass);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat database jika belum ada
$sql_db = "CREATE DATABASE IF NOT EXISTS $db";
$conn->query($sql_db);
$conn->select_db($db);

// === Membuat Tabel Cabang ===
$sql_table_cabang = "CREATE TABLE IF NOT EXISTS cabang (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama_cabang VARCHAR(100) NOT NULL
)";
$conn->query($sql_table_cabang);

// === Membuat Tabel Divisi ===
$sql_table_divisi = "CREATE TABLE IF NOT EXISTS divisi (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama_divisi VARCHAR(100) NOT NULL,
    cabang_id INT(11) NOT NULL,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE
)";
$conn->query($sql_table_divisi);

// === Menambahkan Data Cabang (jika belum ada) ===
$data_cabang = ['Saidan', 'Solo', 'Sora', 'Grand Edge', 'Soal Rambut'];

foreach ($data_cabang as $nama) {
    $cek = $conn->query("SELECT * FROM cabang WHERE nama_cabang='$nama'");
    if ($cek->num_rows == 0) {
        $conn->query("INSERT INTO cabang (nama_cabang) VALUES ('$nama')");
    }
}

// === Menambahkan Data Divisi ke Setiap Cabang ===
$data_divisi = [
    'Treatments', 'Meni Pedi', 'Nail Art', 'Blow Dry', 'Smothing',
    'Perming', 'Color', 'Cutting', 'Hair Do', 'Make Up',
    'Waxing', 'Hair Extension'
];

$cabang_query = $conn->query("SELECT id FROM cabang");
while ($row = $cabang_query->fetch_assoc()) {
    $cabang_id = $row['id'];

    foreach ($data_divisi as $divisi) {
        $cek = $conn->query("SELECT * FROM divisi WHERE nama_divisi='$divisi' AND cabang_id='$cabang_id'");
        if ($cek->num_rows == 0) {
            $conn->query("INSERT INTO divisi (nama_divisi, cabang_id) VALUES ('$divisi', '$cabang_id')");
        }
    }
}

// === Merapikan ID agar tetap urut ===
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("SET @new_id = 0");
$conn->query("UPDATE cabang SET id = (@new_id := @new_id + 1) ORDER BY id");
$conn->query("ALTER TABLE cabang AUTO_INCREMENT = 6");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
?>
