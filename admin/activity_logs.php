<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Fetch activity logs
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$logs = [];

try {
    $sql = "
        SELECT al.*, a.username as admin_username, a.full_name
        FROM activity_logs al
        JOIN admins a ON al.admin_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (al.action LIKE ? OR al.table_name LIKE ? OR a.username LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($filter !== 'all') {
        $sql .= " AND al.table_name = ?";
        $params[] = $filter;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching activity logs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Dashboard</title>
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
                        <a href="activity_logs.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-800">Activity Logs</h1>
                <p class="text-gray-600 mt-2">Track all administrative actions and changes</p>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="activity_logs.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by action, table, or admin..." 
                                class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                        </div>
                    </div>
                    <div>
                        <select name="filter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Tables</option>
                            <option value="users" <?php echo $filter === 'users' ? 'selected' : ''; ?>>Users</option>
                            <option value="family_members" <?php echo $filter === 'family_members' ? 'selected' : ''; ?>>Family Members</option>
                            <option value="medical_records" <?php echo $filter === 'medical_records' ? 'selected' : ''; ?>>Medical Records</option>
                            <option value="reminders" <?php echo $filter === 'reminders' ? 'selected' : ''; ?>>Reminders</option>
                            <option value="admins" <?php echo $filter === 'admins' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex space-x-3">
                        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <?php if (!empty($search) || $filter !== 'all'): ?>
                            <a href="activity_logs.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-times mr-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Activity Timeline -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="space-y-4">
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $iconClass = 'text-blue-500';
                            $icon = 'fa-info-circle';
                            if (strpos($log['action'], 'DELETE') !== false) {
                                $iconClass = 'text-red-500';
                                $icon = 'fa-trash';
                            } elseif (strpos($log['action'], 'CREATE') !== false) {
                                $iconClass = 'text-green-500';
                                $icon = 'fa-plus';
                            } elseif (strpos($log['action'], 'UPDATE') !== false) {
                                $iconClass = 'text-yellow-500';
                                $icon = 'fa-edit';
                            } elseif (strpos($log['action'], 'LOGIN') !== false) {
                                $iconClass = 'text-purple-500';
                                $icon = 'fa-sign-in-alt';
                            }
                            ?>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow">
                                        <i class="fas <?php echo $icon; ?> <?php echo $iconClass; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($log['action']); ?></p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                By <span class="font-medium"><?php echo htmlspecialchars($log['full_name']); ?></span> 
                                                (<?php echo htmlspecialchars($log['admin_username']); ?>)
                                                <?php if ($log['table_name']): ?>
                                                    • Table: <span class="font-medium"><?php echo htmlspecialchars($log['table_name']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($log['record_id']): ?>
                                                    • ID: <span class="font-medium">#<?php echo $log['record_id']; ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($log['details']): ?>
                                                <p class="text-xs text-gray-500 mt-2 bg-white p-2 rounded border border-gray-200">
                                                    <?php echo htmlspecialchars($log['details']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-400 mt-2">
                                                <i class="fas fa-network-wired mr-1"></i><?php echo htmlspecialchars($log['ip_address']); ?>
                                            </p>
                                        </div>
                                        <span class="text-xs text-gray-500 whitespace-nowrap ml-4">
                                            <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-20">
                            <i class="fas fa-history text-6xl text-gray-300 mb-4"></i>
                            <p class="text-xl text-gray-500">No activity logs found</p>
                            <p class="text-gray-400 mt-2">Start performing actions to see logs here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
