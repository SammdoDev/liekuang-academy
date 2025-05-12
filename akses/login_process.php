<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Cek jika field username atau password kosong
    if (empty($username) || empty($password)) {
        header("Location: ../index.php?error=emptyfields");
        exit;
    }

    // Query untuk mengambil data user berdasarkan username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        // Cek jika username ditemukan di database
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Cek password menggunakan password_verify() untuk mencocokkan hash
            if (password_verify($password, $user['password'])) {
                // Simpan session user
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Tambahkan session_start() pada halaman berikutnya untuk memastikan session aktif
                session_regenerate_id(true); // Gantilah session_id untuk menghindari session fixation attacks

                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/cabang/cabang.php");
                } elseif ($user['role'] === 'kasir' || $user['role'] === 'guru') {
                    header("Location: ../cabang/cabang.php");
                } else {
                    header("Location: ../index.php?error=unknownrole");
                }
                $stmt->close();
                $conn->close();
                exit;
            } else {
                // Password salah
                $stmt->close();
                $conn->close();
                header("Location: ../index.php?error=wrongcredentials");
                exit;
            }
        } else {
            // Username tidak ditemukan
            $stmt->close();
            $conn->close();
            header("Location: ../index.php?error=wrongcredentials");
            exit;
        }
    } else {
        // Error SQL
        $stmt->close();
        $conn->close();
        header("Location: ../index.php?error=sqlerror");
        exit;
    }
}
?>
