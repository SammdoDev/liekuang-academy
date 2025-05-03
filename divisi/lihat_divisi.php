<?php
include '../koneksi.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cabang_id = $_POST['cabang_id'];
    $password = $_POST['password'];

    // Query untuk memeriksa password cabang dari database
    $query = "SELECT * FROM cabang WHERE id_cabang = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $cabang_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Password benar, simpan akses dalam session
        $_SESSION['cabang_akses'][$cabang_id] = true;

        // Redirect ke halaman divisi dengan parameter cabang_id
        header("Location: divisi.php?cabang_id=" . $cabang_id);
        exit();
    } else {
        // Password salah atau cabang tidak ditemukan
        // Simpan pesan error dalam session
        $_SESSION['error_message'] = "Password untuk cabang ini salah!";
        header("Location: ../cabang/cabang.php");
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke halaman utama
    header("Location: ../cabang/cabang.php");
    exit();
}
?>