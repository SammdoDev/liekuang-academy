<?php
include 'koneksi.php';

$username = 'agus';
$plain_password = 'liekuang';

// Hash password
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Update password ke database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $username);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "✅ Password berhasil dienkripsi dan diperbarui.";
} else {
    echo "❌ Gagal memperbarui password atau tidak ada perubahan.";
}
?>
