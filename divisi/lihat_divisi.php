<?php
session_start();
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cabang_id = $_POST['cabang_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($cabang_id) || empty($password)) {
        $_SESSION['error_message'] = "ID cabang dan password harus diisi!";
        header("Location: ../cabang/cabang.php");
        exit();
    }

    // Siapkan dan eksekusi query
    $query = "SELECT password FROM cabang WHERE id_cabang = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cabang_id);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    if ($stored_password && $stored_password === $password) {
        // Simpan hak akses di session
        $_SESSION['cabang_akses'][$cabang_id] = true;

        // Redirect ke halaman divisi
        header("Location: divisi.php?cabang_id=" . $cabang_id);
        exit();
    } else {
        $_SESSION['error_message'] = "Password untuk cabang ini salah!";
        header("Location: ../cabang/cabang.php");
        exit();
    }

} else {
    // Jika bukan metode POST
    header("Location: ../cabang/cabang.php");
    exit();
}
?>
