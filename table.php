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

    // Clear foreign key checks for easier table drops
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Reset foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // Create cabang table
    $sql = "CREATE TABLE IF NOT EXISTS cabang (
        id_cabang INT NOT NULL AUTO_INCREMENT,
        nama_cabang VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
        password VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id_cabang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";


    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel cabang: " . $conn->error);
    }

    // Create divisi table
    $sql = "CREATE TABLE IF NOT EXISTS divisi (
        id_divisi INT AUTO_INCREMENT PRIMARY KEY,
        nama_divisi VARCHAR(100) NOT NULL,
        id_cabang INT,
        password VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel divisi: " . $conn->error);
    }

    // Create skill table linked to divisi
    $sql = "CREATE TABLE IF NOT EXISTS skill (
        id_skill INT AUTO_INCREMENT PRIMARY KEY,
        nama_skill VARCHAR(100) NOT NULL,
        id_divisi INT,
        id_cabang INT,
        rata_rata_skill FLOAT DEFAULT 0,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel skill: " . $conn->error);
    }

    // Create staff table linked to skill
    $sql = "CREATE TABLE IF NOT EXISTS staff (
        id_staff INT AUTO_INCREMENT PRIMARY KEY,
        nama_staff VARCHAR(100) NOT NULL,
        id_skill INT,
        id_divisi INT,
        id_cabang INT,
        FOREIGN KEY (id_skill) REFERENCES skill(id_skill) ON DELETE CASCADE,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel staff: " . $conn->error);
    }

    // Create skill_matrix table linked to staff
    $sql = "CREATE TABLE IF NOT EXISTS skill_matrix (
        id_skill_matrix INT AUTO_INCREMENT PRIMARY KEY,
        id_staff INT,
        id_skill INT,
        id_divisi INT,
        id_cabang INT,
        total_look FLOAT DEFAULT 0,
        konsultasi_komunikasi FLOAT DEFAULT 0,
        teknik FLOAT DEFAULT 0,
        kerapian_kebersihan FLOAT DEFAULT 0,
        produk_knowledge FLOAT DEFAULT 0,
        catatan TEXT DEFAULT NULL,
        rata_rata FLOAT GENERATED ALWAYS AS (
            (total_look + konsultasi_komunikasi + teknik + kerapian_kebersihan + produk_knowledge) / 5
        ) STORED,
        FOREIGN KEY (id_staff) REFERENCES staff(id_staff) ON DELETE CASCADE,
        FOREIGN KEY (id_skill) REFERENCES skill(id_skill) ON DELETE CASCADE,
        FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE CASCADE,
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel skill_matrix: " . $conn->error);
    }

    // Daftar cabang - Pastikan urutan cabang tetap konsisten
    $cabang_list = ['Saidan', 'Solo', 'Sora', 'Grand Edge', 'Soal Rambut'];

    // Cek apakah cabang sudah ada dalam database
    $result = $conn->query("SELECT COUNT(*) as count FROM cabang");
    $row = $result->fetch_assoc();

    // Hanya masukkan cabang jika tabel kosong
    if ($row['count'] == 0) {
        // Insert cabang berurutan dan dapatkan ID secara eksplisit
        foreach ($cabang_list as $index => $cabang) {
            $stmt = $conn->prepare("INSERT INTO cabang (nama_cabang) VALUES (?)");
            $stmt->bind_param("s", $cabang);
            $stmt->execute();
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
                $stmt = $conn->prepare("INSERT INTO divisi (nama_divisi, id_cabang) VALUES (?, ?)");
                $stmt->bind_param("si", $divisi, $cabang_id);
                $stmt->execute();
            }
        }

    } else {
    }

    // Temporarily disable foreign key checks for data restoration
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Note: Data restoration is more complex now due to schema changes
    // This is a simplified approach - in production you'd need to handle data mapping carefully

    // 1. First restore skill data if backup exists
    $result = $conn->query("SHOW TABLES LIKE 'skill_backup'");
    if ($result->num_rows > 0) {
        // Check if the backup table has any data
        $checkData = $conn->query("SELECT COUNT(*) as count FROM skill_backup");
        $row = $checkData->fetch_assoc();

        if ($row['count'] > 0) {
            $restoreSkill = $conn->query("INSERT INTO skill (id_skill, nama_skill, id_divisi, id_cabang, rata_rata_skill) 
                         SELECT id_skill, nama_skill, id_divisi, id_cabang, rata_rata_skill FROM skill_backup");

            if (!$restoreSkill) {
                echo "❌ Gagal memulihkan data skill: " . $conn->error . "<br>";
            } else {
                echo "✅ Data skill berhasil dipulihkan.<br>";
            }
        }

        // Drop the backup table after restoration
        $conn->query("DROP TABLE IF EXISTS skill_backup");
    }

    // 2. Then restore staff data if backup exists - note the modified structure
    $result = $conn->query("SHOW TABLES LIKE 'staff_backup'");
    if ($result->num_rows > 0) {
        // Check if the backup table has any data
        $checkData = $conn->query("SELECT COUNT(*) as count FROM staff_backup");
        $row = $checkData->fetch_assoc();

        if ($row['count'] > 0) {
            // We'll need to transfer data carefully here due to schema changes
            // For simplicity, we'll assign a default skill based on division
            $result = $conn->query("SELECT * FROM staff_backup");
            $restored = 0;

            while ($staff = $result->fetch_assoc()) {
                // Find a matching skill for this division
                $skillQuery = $conn->prepare("SELECT id_skill FROM skill WHERE id_divisi = ? LIMIT 1");
                $skillQuery->bind_param("i", $staff['id_divisi']);
                $skillQuery->execute();
                $skillResult = $skillQuery->get_result();

                if ($skillResult->num_rows > 0) {
                    $skillRow = $skillResult->fetch_assoc();
                    $id_skill = $skillRow['id_skill'];

                    $insertStaff = $conn->prepare("INSERT INTO staff (id_staff, nama_staff, id_skill, id_divisi, id_cabang) 
                                    VALUES (?, ?, ?, ?, ?)");
                    $insertStaff->bind_param(
                        "isiii",
                        $staff['id_staff'],
                        $staff['nama_staff'],
                        $id_skill,
                        $staff['id_divisi'],
                        $staff['id_cabang']
                    );
                    if ($insertStaff->execute()) {
                        $restored++;
                    }
                }
            }

            echo "✅ $restored data staff berhasil dipulihkan.<br>";
        }

        // Drop the backup table after restoration
        $conn->query("DROP TABLE IF EXISTS staff_backup");
    }

    // 3. Finally restore skill_matrix data if backup exists
    $result = $conn->query("SHOW TABLES LIKE 'skill_matrix_backup'");
    if ($result->num_rows > 0) {
        // Check if the backup table has any data
        $checkData = $conn->query("SELECT COUNT(*) as count FROM skill_matrix_backup");
        $row = $checkData->fetch_assoc();

        if ($row['count'] > 0) {
            $restoreMatrix = $conn->query("INSERT INTO skill_matrix (id_skill_matrix, id_staff, id_skill, id_divisi, id_cabang,
                            total_look, konsultasi_komunikasi, teknik, kerapian_kebersihan, produk_knowledge) 
                         SELECT id_skill_matrix, id_staff, id_skill, id_divisi, id_cabang,
                            total_look, konsultasi_komunikasi, teknik, kerapian_kebersihan, produk_knowledge 
                         FROM skill_matrix_backup");

            if (!$restoreMatrix) {
                echo "❌ Gagal memulihkan data skill_matrix: " . $conn->error . "<br>";
            } else {
                echo "✅ Data skill_matrix berhasil dipulihkan.<br>";
            }
        }

        // Drop the backup table after restoration
        $conn->query("DROP TABLE IF EXISTS skill_matrix_backup");
    }

    // Add this code after the skill_matrix table creation:
    $sql = "CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Gagal membuat tabel users: " . $conn->error);
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");


} catch (Exception $e) {
    echo "❌ Terjadi kesalahan: " . $e->getMessage();
}
?>