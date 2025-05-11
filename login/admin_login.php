<?php
include '../koneksi.php';
session_start();

// Admin logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if already logged in as admin
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    $success_msg = "Anda sudah login sebagai admin!";
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'divisi.php?cabang_id=1';
    header("refresh:2;url=$redirect_url");
}

// Handle login attempt
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // In a real application, you would use proper password hashing and a secure database
    // Here's a simplified version for demonstration
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = true;
        header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'divisi.php?cabang_id=1'));
        exit;
    } else {
        $error_msg = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <!-- Notifikasi -->
    <?php if ($error_msg): ?>
    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= $error_msg ?>
        <button onclick="document.getElementById('error-alert').style.display='none'" class="ml-4 text-white">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('error-alert').style.display = 'none';
        }, 5000);
    </script>
    <?php endif; ?>

    <?php if (isset($success_msg)): ?>
    <div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?= $success_msg ?>
        <button onclick="document.getElementById('success-alert').style.display='none'" class="ml-4 text-white">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('success-alert').style.display = 'none';
        }, 5000);
    </script>
    <?php endif; ?>

    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 border border-gray-200 dark:border-gray-700">
            <div class="text-center mb-8">
                <i class="fas fa-user-shield text-primary-600 text-5xl mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Login Admin</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Masukkan kredensial admin untuk melanjutkan</p>
            </div>

            <form method="POST" action="">
                <div class="mb-6">
                    <label for="username" class="block text-gray-700 dark:text-gray-300 mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" name="username"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Username" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-gray-700 dark:text-gray-300 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Password" required>
                        <button type="button" onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 dark:text-gray-400">
                            <i id="eye-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input type="checkbox" id="remember" name="remember"
                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ingat saya</label>
                </div>

                <button type="submit"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../index.php" class="text-primary-600 hover:text-primary-700 text-sm flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Halaman Utama
                </a>
            </div>
        </div>

        <div class="mt-6 text-center text-gray-500 dark:text-gray-400 text-sm">
            &copy; <?= date('Y') ?> Sistem Manajemen Karyawan. All rights reserved.
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Dark mode toggle based on system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>

</html>