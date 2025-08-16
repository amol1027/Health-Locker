<?php
session_start();
require_once '../config/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
    } else {
        try {
            // Fetch user from the database
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify the password
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start a session and store user ID
                $_SESSION['user_id'] = $user['id'];
                
                $message = 'Login successful! Redirecting to your dashboard...';
                // Redirect to dashboard
                header('Refresh: 2; URL=../frontend/dashboard.php');
            } else {
                $message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
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
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50">
    <!-- Header -->
    <header class="fixed w-full bg-white shadow-sm z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex justify-between items-center py-4">
                <a href="../index.php" class="text-2xl font-bold text-primary-600">Health Locker</a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="../index.php#features" class="text-gray-600 hover:text-primary-600 transition-colors duration-200 font-medium">Features</a>
                    <a href="../index.php#about" class="text-gray-600 hover:text-primary-600 transition-colors duration-200 font-medium">About</a>
                    <a href="../index.php#contact" class="text-gray-600 hover:text-primary-600 transition-colors duration-200 font-medium">Contact</a>
                    <a href="login.php" class="px-4 py-2 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition-colors duration-200 font-medium">Login</a>
                    <a href="register.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 font-medium">Sign Up</a>
                </div>
                <button class="md:hidden text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center pt-24 pb-12">
        <div class="w-full max-w-md px-4">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                        <p class="text-gray-600">Sign in to access your Health Locker</p>
                    </div>

                    <form action="login.php" method="POST" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                                placeholder="you@example.com"
                            >
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                                placeholder="••••••••"
                            >
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-primary-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-primary-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            Sign In
                        </button>

                        <?php if (!empty($message)): ?>
                            <div class="mt-4 p-3 rounded-lg text-sm <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="font-medium text-primary-600 hover:text-primary-500">Sign up</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 mb-4 md:mb-0">&copy; 2024 Health Locker. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Terms of Service</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
