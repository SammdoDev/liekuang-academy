<?php
include 'koneksi.php';
session_start();

// Cek apakah form dikirimkan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $cabang_id = $_POST['cabang_id'];
    $password = $_POST['password'];
    
    // Validasi input
    if (empty($cabang_id) || empty($password)) {
        $_SESSION['error'] = "ID cabang dan password harus diisi";
        header("Location: index.php");
        exit;
    }
    
    // Query untuk memeriksa password cabang
    $query = "SELECT * FROM cabang WHERE id_cabang = ?";
    
    // Persiapkan statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $cabang = $result->fetch_assoc();
        
        // Periksa apakah cabang memiliki password dan cocok
        // Dalam contoh ini, kita asumsikan password belum di-hash
        // Untuk produksi, gunakan password_verify() untuk membandingkan dengan password yang di-hash
        if (isset($cabang['password']) && $cabang['password'] === $password) {
            // Password benar, set session
            $_SESSION['cabang_akses'][$cabang_id] = true;
            $_SESSION['success'] = "Akses ke cabang " . $cabang['nama_cabang'] . " diberikan";
            
            // Redirect ke halaman divisi untuk cabang tersebut
            header("Location: divisi/divisi.php?cabang_id=" . $cabang_id);
            exit;
        } else {
            // Password salah
            $_SESSION['error'] = "Password tidak valid untuk cabang ini";
            header("Location: index.php");
            exit;
        }
    } else {
        // Cabang tidak ditemukan
        $_SESSION['error'] = "Cabang tidak ditemukan";
        header("Location: index.php");
        exit;
    }
    
    $stmt->close();
} else {
    // Jika tidak ada request POST, redirect ke halaman utama
    header("Location: index.php");
    exit;
}
?>