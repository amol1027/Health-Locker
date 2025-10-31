<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Fetch statistics
$stats = [
    'total_users' => 0,
    'total_family_members' => 0,
    'total_medical_records' => 0,
    'total_reminders' => 0,
    'recent_users' => [],
    'recent_records' => []
];

try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Total family members
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM family_members");
    $stats['total_family_members'] = $stmt->fetch()['count'];
    
    // Total medical records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_records");
    $stats['total_medical_records'] = $stmt->fetch()['count'];
    
    // Total reminders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reminders");
    $stats['total_reminders'] = $stmt->fetch()['count'];
    
    // Recent users (last 5)
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stats['recent_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent medical records (last 5)
    $stmt = $pdo->query("
        SELECT mr.id, mr.record_type, mr.record_date, mr.created_at,
               fm.first_name, fm.last_name, u.name as user_name
        FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        JOIN users u ON fm.user_id = u.id
        ORDER BY mr.created_at DESC LIMIT 5
    ");
    $stats['recent_records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Additional statistics
    // New users this month
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['new_users_this_month'] = $stmt->fetch()['count'];
    
    // Records uploaded today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_records WHERE DATE(created_at) = CURDATE()");
    $stats['records_today'] = $stmt->fetch()['count'];
    
    // Pending reminders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reminders WHERE is_sent = 0 AND reminder_datetime > NOW()");
    $stats['pending_reminders'] = $stmt->fetch()['count'];
    
    // Overdue reminders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reminders WHERE is_sent = 0 AND reminder_datetime < NOW()");
    $stats['overdue_reminders'] = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                        <a href="admins.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition-colors">
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Dashboard Overview</h1>
                <p class="text-gray-600 mt-2">Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's what's happening.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Users</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_users']); ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-4">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Family Members -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Family Members</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_family_members']); ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-4">
                            <i class="fas fa-user-friends text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Medical Records -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Medical Records</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_medical_records']); ?></p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-4">
                            <i class="fas fa-file-medical text-2xl text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Reminders -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Reminders</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_reminders']); ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-4">
                            <i class="fas fa-bell text-2xl text-yellow-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-3"></i>
                    Quick Actions
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="users.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors group">
                        <i class="fas fa-users text-3xl text-blue-600 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium text-gray-700">Manage Users</span>
                    </a>
                    <a href="medical_records.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors group">
                        <i class="fas fa-file-medical text-3xl text-purple-600 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium text-gray-700">View Records</span>
                    </a>
                    <a href="reminders.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors group">
                        <i class="fas fa-bell text-3xl text-yellow-600 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium text-gray-700">Reminders</span>
                    </a>
                    <a href="analytics.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors group">
                        <i class="fas fa-chart-line text-3xl text-green-600 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium text-gray-700">Analytics</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Recent Users -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-4">
                        <h3 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-user-plus mr-3"></i>
                            Recent Users
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($stats['recent_users'])): ?>
                            <div class="space-y-4">
                                <?php foreach ($stats['recent_users'] as $user): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-center">
                                            <div class="bg-primary-100 rounded-full p-2 mr-3">
                                                <i class="fas fa-user text-primary-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No users yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Medical Records -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                        <h3 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-file-medical-alt mr-3"></i>
                            Recent Medical Records
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($stats['recent_records'])): ?>
                            <div class="space-y-4">
                                <?php foreach ($stats['recent_records'] as $record): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-center">
                                            <div class="bg-purple-100 rounded-full p-2 mr-3">
                                                <i class="fas fa-file-alt text-purple-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['record_type']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                    <span class="text-gray-400">•</span>
                                                    <?php echo htmlspecialchars($record['user_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No records yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Status & Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <!-- This Month Stats -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-check text-blue-500 mr-2"></i>
                        This Month
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-user-plus text-blue-600 mr-2"></i>
                                <span class="text-sm text-gray-700">New Users</span>
                            </div>
                            <span class="font-bold text-blue-600"><?php echo $stats['new_users_this_month']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Today's Activity -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-day text-green-500 mr-2"></i>
                        Today's Activity
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-file-upload text-green-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Records Uploaded</span>
                            </div>
                            <span class="font-bold text-green-600"><?php echo $stats['records_today']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Reminder Status -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bell text-yellow-500 mr-2"></i>
                        Reminder Status
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Pending</span>
                            </div>
                            <span class="font-bold text-yellow-600"><?php echo $stats['pending_reminders']; ?></span>
                        </div>
                        <?php if ($stats['overdue_reminders'] > 0): ?>
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Overdue</span>
                            </div>
                            <span class="font-bold text-red-600"><?php echo $stats['overdue_reminders']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl shadow-lg p-6 mt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-white mb-2">Need Help?</h3>
                        <p class="text-primary-100 text-sm">Check out our admin documentation or contact support</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="README.md" target="_blank" class="px-4 py-2 bg-white text-primary-600 rounded-lg hover:bg-primary-50 transition-colors font-medium">
                            <i class="fas fa-book mr-2"></i>Documentation
                        </a>
                        <a href="activity_logs.php" class="px-4 py-2 bg-primary-700 text-white rounded-lg hover:bg-primary-800 transition-colors font-medium">
                            <i class="fas fa-history mr-2"></i>View Logs
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
