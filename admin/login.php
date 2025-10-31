<?php
session_start();
require_once '../config/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, email FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Successful login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                $message = 'Login successful! Redirecting to admin dashboard...';
                header('Refresh: 2; URL=dashboard.php');
            } else {
                $message = 'Invalid username or password.';
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
    <title>Admin Login - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-primary-50 to-primary-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 p-8 text-center">
                <i class="fas fa-user-shield text-5xl text-white mb-4"></i>
                <h2 class="text-3xl font-bold text-white mb-2">Admin Portal</h2>
                <p class="text-primary-100">Health Locker Administration</p>
            </div>

            <div class="p-8">
                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user mr-2 text-gray-400"></i>Username or Email
                        </label>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                            placeholder="Enter your username or email"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>Password
                        </label>
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
                        class="w-full bg-primary-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-primary-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center justify-center"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In to Admin Panel
                    </button>

                    <?php if (!empty($message)): ?>
                        <div class="mt-4 p-3 rounded-lg text-sm <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <i class="fas <?php echo strpos($message, 'successful') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-600">
                    <a href="../index.php" class="font-medium text-primary-600 hover:text-primary-500">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Main Site
                    </a>
                </p>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-600">
                <i class="fas fa-shield-alt mr-1"></i>
                Secure Admin Access Only
            </p>
        </div>
    </div>
</body>
</html>
