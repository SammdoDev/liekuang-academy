<?php
session_start();
include '../koneksi.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    die("❌ Username dan password harus diisi.");
}

$stmt = $conn->prepare("SELECT id_user, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $username;
        header("Location: ../cabang/cabang.php");
        exit;
    } else {
        echo "❌ Password salah.";
    }
} else {
    echo "❌ Username tidak ditemukan.";
}
?>
