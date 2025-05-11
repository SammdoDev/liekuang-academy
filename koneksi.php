<?php
include  'table.php';
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "liekuang_academy";

// Buat koneksi
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Periksa koneksi
    if ($conn->connect_error) {
        throw new Exception("Koneksi gagal: " . $conn->connect_error);
    }
    
    // Set mode error ke exception
    $conn->set_charset("utf8mb4");
    
    // Debug mode (hapus atau ubah ke false untuk production)
    $debug_mode = true;
    
    if ($debug_mode) {
        function debug_log($message) {
            echo "<div style='background-color:#f8f9fa;padding:5px;margin:5px 0;border-left:4px solid #17a2b8;'>";
            echo " DEBUG: $message";
            echo "</div>";
        }
    } else {
        function debug_log($message) {
            // Tidak lakukan apa-apa dalam production mode
        }
    }
    
} catch (Exception $e) {
    die("Fatal Error: " . $e->getMessage());
}

// Fungsi untuk sanitasi input
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Fungsi untuk validasi apakah divisi dan cabang valid
function is_valid_divisi($conn, $divisi_id) {
    $stmt = $conn->prepare("SELECT id_divisi FROM divisi WHERE id_divisi = ?");
    $stmt->bind_param("i", $divisi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function is_valid_cabang($conn, $cabang_id) {
    $stmt = $conn->prepare("SELECT id_cabang FROM cabang WHERE id_cabang = ?");
    $stmt->bind_param("i", $cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Fungsi untuk mendapatkan id_cabang dari id_divisi
function get_cabang_id_from_divisi($conn, $divisi_id) {
    $stmt = $conn->prepare("SELECT id_cabang FROM divisi WHERE id_divisi = ?");
    $stmt->bind_param("i", $divisi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['id_cabang'] : null;
}

// Fungsi untuk menambahkan staff baru
function add_staff($conn, $nama_staff, $divisi_id, $cabang_id = null) {
    // Jika cabang_id tidak diberikan, ambil dari divisi
    if ($cabang_id === null) {
        $cabang_id = get_cabang_id_from_divisi($conn, $divisi_id);
        if (!$cabang_id) {
            return [
                'success' => false,
                'message' => 'Divisi tidak ditemukan atau tidak terkait dengan cabang manapun'
            ];
        }
    }
    
    // Validasi divisi dan cabang
    if (!is_valid_divisi($conn, $divisi_id)) {
        return [
            'success' => false,
            'message' => 'ID Divisi tidak valid'
        ];
    }
    
    if (!is_valid_cabang($conn, $cabang_id)) {
        return [
            'success' => false,
            'message' => 'ID Cabang tidak valid'
        ];
    }
    
    // Persiapkan dan jalankan query
    $stmt = $conn->prepare("INSERT INTO staff (nama_staff, id_divisi, id_cabang) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $nama_staff, $divisi_id, $cabang_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Staff berhasil ditambahkan',
            'staff_id' => $stmt->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal menambahkan staff: ' . $stmt->error,
            'error_code' => $stmt->errno
        ];
    }
}

// Fungsi untuk mengambil daftar cabang
function get_cabang_list($conn) {
    $result = $conn->query("SELECT id_cabang, nama_cabang FROM cabang ORDER BY id_cabang");
    $cabang_list = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cabang_list[] = $row;
        }
    }
    
    return $cabang_list;
}

// Fungsi untuk mengambil daftar divisi (bisa difilter berdasarkan cabang)
function get_divisi_list($conn, $cabang_id = null) {
    $sql = "SELECT id_divisi, nama_divisi, id_cabang FROM divisi";
    
    if ($cabang_id !== null) {
        $sql .= " WHERE id_cabang = " . intval($cabang_id);
    }
    
    $sql .= " ORDER BY nama_divisi";
    $result = $conn->query($sql);
    $divisi_list = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $divisi_list[] = $row;
        }
    }
    
    return $divisi_list;
}

// Fungsi untuk mengambil daftar staff (bisa difilter berdasarkan divisi dan/atau cabang)
function get_staff_list($conn, $divisi_id = null, $cabang_id = null) {
    $sql = "SELECT s.id_staff, s.nama_staff, s.id_divisi, d.nama_divisi, s.id_cabang, c.nama_cabang 
            FROM staff s
            JOIN divisi d ON s.id_divisi = d.id_divisi
            JOIN cabang c ON s.id_cabang = c.id_cabang";
    
    $where_conditions = [];
    
    if ($divisi_id !== null) {
        $where_conditions[] = "s.id_divisi = " . intval($divisi_id);
    }
    
    if ($cabang_id !== null) {
        $where_conditions[] = "s.id_cabang = " . intval($cabang_id);
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY s.nama_staff";
    $result = $conn->query($sql);
    $staff_list = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staff_list[] = $row;
        }
    }
    
    return $staff_list;
}

// Fungsi untuk mengupdate staff
function update_staff($conn, $id_staff, $nama_staff, $divisi_id, $cabang_id = null) {
    // Jika cabang_id tidak diberikan, ambil dari divisi
    if ($cabang_id === null) {
        $cabang_id = get_cabang_id_from_divisi($conn, $divisi_id);
        if (!$cabang_id) {
            return [
                'success' => false,
                'message' => 'Divisi tidak ditemukan atau tidak terkait dengan cabang manapun'
            ];
        }
    }
    
    // Validasi divisi dan cabang
    if (!is_valid_divisi($conn, $divisi_id)) {
        return [
            'success' => false,
            'message' => 'ID Divisi tidak valid'
        ];
    }
    
    if (!is_valid_cabang($conn, $cabang_id)) {
        return [
            'success' => false,
            'message' => 'ID Cabang tidak valid'
        ];
    }
    
    // Persiapkan dan jalankan query
    $stmt = $conn->prepare("UPDATE staff SET nama_staff = ?, id_divisi = ?, id_cabang = ? WHERE id_staff = ?");
    $stmt->bind_param("siii", $nama_staff, $divisi_id, $cabang_id, $id_staff);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Staff berhasil diupdate',
            'affected_rows' => $stmt->affected_rows
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal mengupdate staff: ' . $stmt->error,
            'error_code' => $stmt->errno
        ];
    }
}

// Fungsi untuk menghapus staff
function delete_staff($conn, $id_staff) {
    $stmt = $conn->prepare("DELETE FROM staff WHERE id_staff = ?");
    $stmt->bind_param("i", $id_staff);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Staff berhasil dihapus',
            'affected_rows' => $stmt->affected_rows
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal menghapus staff: ' . $stmt->error,
            'error_code' => $stmt->errno
        ];
    }
}

// Fungsi untuk menambahkan skill baru
function add_skill($conn, $nama_skill, $divisi_id, $cabang_id = null) {
    // Jika cabang_id tidak diberikan, ambil dari divisi
    if ($cabang_id === null) {
        $cabang_id = get_cabang_id_from_divisi($conn, $divisi_id);
        if (!$cabang_id) {
            return [
                'success' => false,
                'message' => 'Divisi tidak ditemukan atau tidak terkait dengan cabang manapun'
            ];
        }
    }
    
    // Validasi divisi dan cabang
    if (!is_valid_divisi($conn, $divisi_id)) {
        return [
            'success' => false,
            'message' => 'ID Divisi tidak valid'
        ];
    }
    
    if (!is_valid_cabang($conn, $cabang_id)) {
        return [
            'success' => false,
            'message' => 'ID Cabang tidak valid'
        ];
    }
    
    // Persiapkan dan jalankan query
    $stmt = $conn->prepare("INSERT INTO skill (nama_skill, id_divisi, id_cabang) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $nama_skill, $divisi_id, $cabang_id);
    
    if ($stmt->execute()) {
        $skill_id = $stmt->insert_id;
        
        // Buat entri skill_matrix juga
        $stmt2 = $conn->prepare("INSERT INTO skill_matrix (id_skill, id_divisi, id_cabang) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $skill_id, $divisi_id, $cabang_id);
        $stmt2->execute();
        
        return [
            'success' => true,
            'message' => 'Skill berhasil ditambahkan',
            'skill_id' => $skill_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal menambahkan skill: ' . $stmt->error,
            'error_code' => $stmt->errno
        ];
    }
}

// Fungsi untuk mendapatkan daftar skill (bisa difilter berdasarkan divisi dan/atau cabang)
function get_skill_list($conn, $divisi_id = null, $cabang_id = null) {
    $sql = "SELECT s.id_skill, s.nama_skill, s.id_divisi, d.nama_divisi, s.id_cabang, c.nama_cabang, s.rata_rata_skill 
            FROM skill s
            JOIN divisi d ON s.id_divisi = d.id_divisi
            JOIN cabang c ON s.id_cabang = c.id_cabang";
    
    $where_conditions = [];
    
    if ($divisi_id !== null) {
        $where_conditions[] = "s.id_divisi = " . intval($divisi_id);
    }
    
    if ($cabang_id !== null) {
        $where_conditions[] = "s.id_cabang = " . intval($cabang_id);
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY s.nama_skill";
    $result = $conn->query($sql);
    $skill_list = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $skill_list[] = $row;
        }
    }
    
    return $skill_list;
}

// Fungsi untuk update skill matrix
function update_skill_matrix($conn, $id_skill, $total_look, $konsultasi_komunikasi, $teknik, $kerapian_kebersihan, $produk_knowledge, $catatan) {
    // Dapatkan skill untuk mendapatkan id_divisi dan id_cabang
    $stmt0 = $conn->prepare("SELECT id_divisi, id_cabang FROM skill WHERE id_skill = ?");
    $stmt0->bind_param("i", $id_skill);
    $stmt0->execute();
    $result = $stmt0->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Skill tidak ditemukan'
        ];
    }
    
    $skill_data = $result->fetch_assoc();
    $id_divisi = $skill_data['id_divisi'];
    $id_cabang = $skill_data['id_cabang'];
    
    // Periksa apakah entri skill_matrix sudah ada
    $stmt1 = $conn->prepare("SELECT id_skill_matrix FROM skill_matrix WHERE id_skill = ?");
    $stmt1->bind_param("i", $id_skill);
    $stmt1->execute();
    $result = $stmt1->get_result();
    
    if ($result->num_rows === 0) {
        // Buat baru jika belum ada
        $stmt = $conn->prepare("INSERT INTO skill_matrix (id_skill, id_divisi, id_cabang, total_look, konsultasi_komunikasi, teknik, kerapian_kebersihan, produk_knowledge, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddddd", $id_skill, $id_divisi, $id_cabang, $total_look, $konsultasi_komunikasi, $teknik, $kerapian_kebersihan, $produk_knowledge, $catatan);
    } else {
        // Update jika sudah ada
        $stmt = $conn->prepare("UPDATE skill_matrix SET total_look = ?, konsultasi_komunikasi = ?, teknik = ?, kerapian_kebersihan = ?, produk_knowledge = ? WHERE id_skill = ?");
        $stmt->bind_param("dddddi", $total_look, $konsultasi_komunikasi, $teknik, $kerapian_kebersihan, $produk_knowledge, $catatan, $id_skill);
    }
    
    if ($stmt->execute()) {
        // Hitung rata-rata baru
        $avg = ($total_look + $konsultasi_komunikasi + $teknik + $kerapian_kebersihan + $produk_knowledge) / 5;
        
        // Update rata-rata di tabel skill
        $stmt2 = $conn->prepare("UPDATE skill SET rata_rata_skill = ? WHERE id_skill = ?");
        $stmt2->bind_param("di", $avg, $id_skill);
        $stmt2->execute();
        
        return [
            'success' => true,
            'message' => 'Skill matrix berhasil diupdate',
            'rata_rata' => $avg
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal mengupdate skill matrix: ' . $stmt->error,
            'error_code' => $stmt->errno
        ];
    }
}
?>
