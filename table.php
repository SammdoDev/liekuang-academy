<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "liekuang_academy";

try {
    $conn = new mysqli($servername, $username, $password);

    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if (!$conn->query($sql))
        throw new Exception("Gagal membuat database");

    if (!$conn->select_db($dbname)) {
        throw new Exception("Gagal memilih database: " . $conn->error);
    }

    // Drop tables in correct order (to avoid foreign key constraint issues)
    $conn->query("DROP TABLE IF EXISTS skill_matrix");
    $conn->query("DROP TABLE IF EXISTS skill");
    $conn->query("DROP TABLE IF EXISTS staff"); // Drop old staff table if exists
    $conn->query("DROP TABLE IF EXISTS staff_backup"); // Drop old staff_backup table if exists
    $conn->query("DROP TABLE IF EXISTS divisi");
    $conn->query("DROP TABLE IF EXISTS cabang");

    $sql = "CREATE TABLE cabang (   
        id_cabang INT AUTO_INCREMENT PRIMARY KEY,
        nama_cabang VARCHAR(100) NOT NULL UNIQUE
    )";
    $conn->query($sql);

    $sql = "CREATE TABLE divisi (
        id_divisi INT AUTO_INCREMENT PRIMARY KEY,
        nama_divisi VARCHAR(100) NOT NULL,
        id_cabang INT,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    )";
    $conn->query($sql);

    $sql = "CREATE TABLE skill (
        id_skill INT AUTO_INCREMENT PRIMARY KEY,
        nama_skill VARCHAR(100) NOT NULL,
        id_divisi INT,
        id_cabang INT,
        rata_rata_skill FLOAT DEFAULT 0,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    )";
    $conn->query($sql);

    $sql = "CREATE TABLE skill_matrix (
        id_skill_matrix INT AUTO_INCREMENT PRIMARY KEY,
        id_skill INT,
        id_divisi INT,
        id_cabang INT,
        total_look FLOAT DEFAULT 0,
        konsultasi_komunikasi FLOAT DEFAULT 0,
        teknik FLOAT DEFAULT 0,
        kerapian_kebersihan FLOAT DEFAULT 0,
        produk_knowledge FLOAT DEFAULT 0,
        rata_rata FLOAT GENERATED ALWAYS AS (
            (total_look + konsultasi_komunikasi + teknik + kerapian_kebersihan + produk_knowledge) / 5
        ) STORED,
        FOREIGN KEY (id_skill) REFERENCES skill(id_skill) ON DELETE CASCADE,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    )";
    $conn->query($sql);

    $sql = "CREATE TABLE staff (
        id_staff INT AUTO_INCREMENT PRIMARY KEY,
        nama_staff VARCHAR(100) NOT NULL,
        id_divisi INT,
        id_cabang INT,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    )";
    $conn->query($sql);

    // Daftar cabang - Pastikan urutan cabang tetap konsisten
    $cabang_list = ['Saidan', 'Solo', 'Sora', 'Grand Edge', 'Soal Rambut'];

    // Insert cabang berurutan dan dapatkan ID secara eksplisit
    foreach ($cabang_list as $index => $cabang) {
        $conn->query("INSERT INTO cabang (nama_cabang) VALUES ('$cabang')");
        $cabang_id = $index + 1; // ID cabang seharusnya berurut 1, 2, 3, ...

        // Daftar divisi
        $divisi_list = [
            'Treatment',
            'Meni Pedi',
            'Nail Art',
            'Blow Dry',
            'Smothing',
            'Perming',
            'Color',
            'Cutting',
            'Hair Do',
            'Make Up',
            'Waxing',
            'Hair Extension'
        ];

        // Insert divisi untuk cabang saat ini
        foreach ($divisi_list as $divisi) {
            $conn->query("INSERT INTO divisi (nama_divisi, id_cabang) VALUES ('$divisi', $cabang_id)");
        }
    }


} catch (Exception $e) {
    echo "❌ Terjadi kesalahan: " . $e->getMessage();
}
?>