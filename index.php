<?php
include 'koneksi.php';
include 'table.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liekuang Academy - Login</title>
    <!-- Tailwind CSS via CDN -->
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
    <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md w-96 border border-gray-200 dark:border-gray-700">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full mb-4">
                <i class="fas fa-layer-group text-3xl text-primary-600 dark:text-primary-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Liekuang Academy</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Sign in to your account</p>
        </div>
        
        <?php
        // Display error message if any
        if (isset($_GET['error'])) {
            echo '<div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded mb-4">';
            
            if ($_GET['error'] == "emptyfields") {
                echo "Please fill in all fields.";
            } else if ($_GET['error'] == "wrongcredentials") {
                echo "Invalid username or password.";
            } else if ($_GET['error'] == "sqlerror") {
                echo "Database error. Please try again later.";
            }
            
            echo '</div>';
        }
        
        // Display success message if any
        if (isset($_GET['success'])) {
            echo '<div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded mb-4">';
            
            if ($_GET['success'] == "registered") {
                echo "Registration successful! Please login.";
            } else if ($_GET['success'] == "logout") {
                echo "You have been successfully logged out.";
            }
            
            echo '</div>';
        }
        ?>
        
        <form action="akses/login_process.php" method="post" class="space-y-4">
            <div>
                <label for="username" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="username" name="username"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="password" name="password"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
            
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 border border-gray-300 rounded text-primary-600 focus:ring-primary-500">
                    <label for="remember" class="ml-2 block text-gray-700 dark:text-gray-300">Remember me</label>
                </div>
                <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">Forgot password?</a>
            </div>
            
            <div>
                <button type="submit" name="login-submit"
                         class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-md transition duration-300 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </div>
        </form>
        
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
            <button id="darkModeToggle" class="text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center mx-auto hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-moon mr-2"></i>
                <span>Toggle Dark Mode</span>
            </button>
        </div>
    </div>
    
    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;

        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark');
        }

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            if (savedTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }

        // Toggle theme
        darkModeToggle.addEventListener('click', () => {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                darkModeToggle.innerHTML = '<i class="fas fa-moon mr-2"></i><span>Toggle Dark Mode</span>';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun mr-2"></i><span>Toggle Light Mode</span>';
            }
        });

        // Update toggle text on load
        if (html.classList.contains('dark')) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun mr-2"></i><span>Toggle Light Mode</span>';
        }
    </script>
</body>
</html>