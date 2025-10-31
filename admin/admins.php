<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$message = '';
$message_type = '';

// Handle admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $message = 'All fields are required.';
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $message = 'Username or email already exists.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                    $message = 'Admin created successfully!';
                    $message_type = 'success';
                }
            }
        } catch (PDOException $e) {
            $message = 'Error creating admin: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle admin deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $admin_id = $_POST['admin_id'];
    
    // Prevent deleting yourself
    if ($admin_id == $_SESSION['admin_id']) {
        $message = 'You cannot delete your own account.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            if ($stmt->execute([$admin_id])) {
                $message = 'Admin deleted successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error deleting admin: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch all admins
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, full_name, created_at, last_login FROM admins ORDER BY created_at DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching admins: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-user-shield text-2xl text-primary-600"></i>
                    <a href="dashboard.php" class="text-2xl font-bold text-primary-600">Health Locker Admin</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 text-sm"><i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($admin_name); ?></span>
                    <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>Log Out
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-users mr-3"></i>
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="family_members.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-user-friends mr-3"></i>
                            Family Members
                        </a>
                    </li>
                    <li>
                        <a href="medical_records.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-file-medical mr-3"></i>
                            Medical Records
                        </a>
                    </li>
                    <li>
                        <a href="reminders.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-bell mr-3"></i>
                            Reminders
                        </a>
                    </li>
                    <li>
                        <a href="admins.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
                            <i class="fas fa-user-shield mr-3"></i>
                            Administrators
                        </a>
                    </li>
                    <li>
                        <a href="analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-chart-line mr-3"></i>
                            Analytics
                        </a>
                    </li>
                    <li>
                        <a href="activity_logs.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
                            <i class="fas fa-history mr-3"></i>
                            Activity Logs
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Administrator Management</h1>
                    <p class="text-gray-600 mt-2">Manage admin accounts and permissions</p>
                </div>
                <button onclick="openCreateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-md">
                    <i class="fas fa-plus mr-2"></i>Add New Admin
                </button>
            </div>

            <?php if (!empty($message)): ?>
                <div id="alert-message" class="mb-6 p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Admins Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($admins as $admin): ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow p-6 <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'border-2 border-primary-500' : ''; ?>">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-primary-100 rounded-full p-3">
                                <i class="fas fa-user-shield text-2xl text-primary-600"></i>
                            </div>
                            <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                    You
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($admin['full_name']); ?>
                        </h3>
                        
                        <div class="space-y-2 mb-4">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-user fa-fw mr-2 text-gray-400"></i>
                                <strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-envelope fa-fw mr-2 text-gray-400"></i>
                                <strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-calendar-plus fa-fw mr-2 text-gray-400"></i>
                                <strong>Created:</strong> <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                            </p>
                            <?php if ($admin['last_login']): ?>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock fa-fw mr-2 text-gray-400"></i>
                                    <strong>Last Login:</strong> <?php echo date('M d, Y h:i A', strtotime($admin['last_login'])); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 italic">
                                    <i class="fas fa-clock fa-fw mr-2 text-gray-400"></i>
                                    Never logged in
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <div class="flex space-x-2 pt-4 border-t">
                                <button onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')" class="flex-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Create Admin Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800">Create New Administrator</h3>
                <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="admins.php" class="p-6">
                <input type="hidden" name="create_admin" value="1">
                
                <div class="space-y-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fas fa-plus mr-2"></i>Create Admin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete admin <span id="adminUsername" class="font-semibold"></span>? This action cannot be undone.</p>
                <form method="POST" action="admins.php">
                    <input type="hidden" name="admin_id" id="adminId">
                    <input type="hidden" name="delete_admin" value="1">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function confirmDelete(adminId, username) {
            document.getElementById('adminId').value = adminId;
            document.getElementById('adminUsername').textContent = username;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alert-message');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(() => alertMessage.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>
