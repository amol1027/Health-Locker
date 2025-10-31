<?php
// Prevent infinite loops and timeout issues
set_time_limit(30); // 30 seconds max
ini_set('memory_limit', '256M');

session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Date range filter (default: last 30 days)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Initialize analytics data
$analytics = [
    'user_growth' => [],
    'record_types' => [],
    'age_distribution' => [],
    'reminders_status' => [],
    'monthly_activity' => [],
    'top_users' => [],
    'record_trends' => [],
    'system_stats' => []
];

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. User Growth Over Time (Daily for selected period)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
        LIMIT 365
    ");
    $stmt->execute([$date_from, $date_to]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['user_growth'] = $result ?: [];

    // 2. Medical Record Types Distribution (Limit to top 15 for better display)
    $stmt = $pdo->query("
        SELECT TRIM(record_type) as record_type, COUNT(*) as count
        FROM medical_records
        WHERE record_type IS NOT NULL 
        AND record_type != '' 
        AND TRIM(record_type) != ''
        AND LENGTH(TRIM(record_type)) > 0
        AND LENGTH(TRIM(record_type)) < 200
        GROUP BY TRIM(record_type)
        ORDER BY count DESC
        LIMIT 15
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['record_types'] = $result ?: [];

    // 3. Age Distribution (calculated from date_of_birth)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN '0-17'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN '51-70'
                ELSE '71+'
            END as age_group,
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 1
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN 2
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN 3
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN 4
                ELSE 5
            END as sort_order,
            COUNT(*) as count
        FROM family_members
        WHERE date_of_birth IS NOT NULL 
            AND date_of_birth <= CURDATE()
            AND date_of_birth >= DATE_SUB(CURDATE(), INTERVAL 150 YEAR)
        GROUP BY age_group, sort_order
        ORDER BY sort_order
        LIMIT 10
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['age_distribution'] = $result ?: [];

    // 5. Reminders Status
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN is_sent = 1 THEN 'Sent'
                WHEN reminder_datetime < NOW() AND is_sent = 0 THEN 'Missed'
                ELSE 'Pending'
            END as status,
            COUNT(*) as count
        FROM reminders
        WHERE reminder_datetime IS NOT NULL
        GROUP BY 
            CASE 
                WHEN is_sent = 1 THEN 'Sent'
                WHEN reminder_datetime < NOW() AND is_sent = 0 THEN 'Missed'
                ELSE 'Pending'
            END
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['reminders_status'] = $result ?: [];

    // 6. Monthly Activity (Records uploaded per month - last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM medical_records
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND created_at <= NOW()
        GROUP BY month
        ORDER BY month ASC
        LIMIT 12
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['monthly_activity'] = $result ?: [];

    // 7. Top Users by Medical Records
    $stmt = $pdo->query("
        SELECT 
            u.id, u.name, u.email,
            COUNT(mr.id) as record_count,
            COUNT(DISTINCT fm.id) as family_member_count
        FROM users u
        LEFT JOIN family_members fm ON u.id = fm.user_id
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
        GROUP BY u.id, u.name, u.email
        HAVING record_count > 0
        ORDER BY record_count DESC
        LIMIT 10
    ");
    $analytics['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Record Upload Trends (Last 7 days)
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM medical_records
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND created_at <= NOW()
        GROUP BY DATE(created_at)
        ORDER BY date ASC
        LIMIT 7
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $analytics['record_trends'] = $result ?: [];

    // 9. System Statistics
    $stats = [];
    
    // Average family members per user
    $stmt = $pdo->query("
        SELECT AVG(member_count) as avg_members
        FROM (
            SELECT user_id, COUNT(*) as member_count
            FROM family_members
            GROUP BY user_id
        ) as temp
    ");
    $result = $stmt->fetch();
    $stats['avg_family_members'] = $result && $result['avg_members'] ? round($result['avg_members'], 2) : 0;

    // Average records per family member
    $stmt = $pdo->query("
        SELECT AVG(record_count) as avg_records
        FROM (
            SELECT member_id, COUNT(*) as record_count
            FROM medical_records
            GROUP BY member_id
        ) as temp
    ");
    $result = $stmt->fetch();
    $stats['avg_records_per_member'] = $result && $result['avg_records'] ? round($result['avg_records'], 2) : 0;

    // Users with allergies recorded
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as count
        FROM family_members
        WHERE known_allergies IS NOT NULL AND known_allergies != ''
    ");
    $stats['users_with_allergies'] = $stmt->fetch()['count'];

    // Total file storage size (approximation)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_records");
    $stats['total_files'] = $stmt->fetch()['count'];

    // Most active day of week
    $stmt = $pdo->query("
        SELECT DAYNAME(created_at) as day_name, COUNT(*) as count
        FROM medical_records
        GROUP BY day_name
        ORDER BY count DESC
        LIMIT 1
    ");
    $result = $stmt->fetch();
    $stats['most_active_day'] = $result ? $result['day_name'] : 'N/A';

    // Relation distribution
    $stmt = $pdo->query("
        SELECT relation, COUNT(*) as count
        FROM family_members
        WHERE relation IS NOT NULL AND relation != '' AND TRIM(relation) != ''
        GROUP BY relation
        ORDER BY count DESC
        LIMIT 20
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['relation_distribution'] = $result ?: [];

    $analytics['system_stats'] = $stats;

} catch (PDOException $e) {
    $error_message = "Error fetching analytics: " . $e->getMessage();
    error_log("Analytics PDO Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Ensure arrays exist even on error
    $analytics = array_merge([
        'user_growth' => [],
        'record_types' => [],
        'age_distribution' => [],
        'reminders_status' => [],
        'monthly_activity' => [],
        'top_users' => [],
        'record_trends' => [],
        'system_stats' => []
    ], $analytics);
} catch (Exception $e) {
    $error_message = "Unexpected error: " . $e->getMessage();
    error_log("Analytics Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Ensure arrays exist even on error
    $analytics = [
        'user_growth' => [],
        'record_types' => [],
        'age_distribution' => [],
        'reminders_status' => [],
        'monthly_activity' => [],
        'top_users' => [],
        'record_trends' => [],
        'system_stats' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Health Locker Admin</title>
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
                        <a href="analytics.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
        <main class="flex-1 p-8 overflow-y-auto" style="max-height: calc(100vh - 60px);">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Analytics Dashboard</h1>
                <p class="text-gray-600 mt-2">Comprehensive insights and statistics</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                    <a href="analytics.php" class="px-4 py-1.5 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-redo mr-1"></i>Reset
                    </a>
                </form>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-xs font-medium">Avg Family Members</p>
                            <p class="text-2xl font-bold mt-1"><?php echo $analytics['system_stats']['avg_family_members'] ?? '0'; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-xs font-medium">Avg Records</p>
                            <p class="text-2xl font-bold mt-1"><?php echo $analytics['system_stats']['avg_records_per_member'] ?? '0'; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-file-medical text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-xs font-medium">With Allergies</p>
                            <p class="text-2xl font-bold mt-1"><?php echo $analytics['system_stats']['users_with_allergies'] ?? '0'; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-allergies text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-xs font-medium">Most Active Day</p>
                            <p class="text-xl font-bold mt-1"><?php echo $analytics['system_stats']['most_active_day'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-calendar-day text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- User Growth Chart -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-line text-primary-600 mr-3"></i>
                        User Growth (Selected Period)
                    </h3>
                    <?php if (!empty($analytics['user_growth'])): ?>
                        <div style="height: 300px; max-height: 300px; position: relative;">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-chart-line text-6xl mb-4 opacity-30"></i>
                            <p class="font-medium">No user registrations in selected period</p>
                            <p class="text-sm mt-2">Try adjusting the date range filter</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Medical Record Types Chart -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-medical text-green-600 mr-3"></i>
                        Medical Record Types
                    </h3>
                    <?php if (!empty($analytics['record_types'])): ?>
                        <div style="height: 300px; max-height: 300px; position: relative;">
                            <canvas id="recordTypesChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-file-medical text-6xl mb-4 opacity-30"></i>
                            <p class="font-medium">No medical records uploaded yet</p>
                            <p class="text-sm mt-2">Records will appear here once users start uploading</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Age Distribution Chart -->
            <div class="mb-6">
                <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl mx-auto">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-birthday-cake text-purple-600 mr-3"></i>
                        Age Distribution
                    </h3>
                    <?php if (!empty($analytics['age_distribution'])): ?>
                        <div class="relative" style="height: 400px; max-height: 400px;">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-birthday-cake text-6xl mb-4 opacity-30"></i>
                            <p class="font-medium">No age data available</p>
                            <p class="text-sm mt-2">Family members need date of birth recorded</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reminder Status -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bell text-yellow-600 mr-2 text-sm"></i>
                        Reminders
                    </h3>
                    <?php if (!empty($analytics['reminders_status'])): ?>
                        <div style="height: 200px; max-height: 200px; position: relative;">
                            <canvas id="reminderStatusChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-bell text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">No reminders</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Monthly Activity -->
                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-alt text-blue-600 mr-2 text-sm"></i>
                        Monthly Activity (Last 12 Months)
                    </h3>
                    <?php if (!empty($analytics['monthly_activity'])): ?>
                        <div style="height: 200px;">
                            <canvas id="monthlyActivityChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-alt text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">No activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Relation Distribution -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-people-arrows text-indigo-600 mr-3"></i>
                    Family Relations
                </h3>
                <?php if (!empty($analytics['system_stats']['relation_distribution']) && is_array($analytics['system_stats']['relation_distribution'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Relation</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $total_relations = array_sum(array_column($analytics['system_stats']['relation_distribution'], 'count'));
                            foreach ($analytics['system_stats']['relation_distribution'] as $relation): 
                                if (!isset($relation['relation']) || !isset($relation['count'])) continue;
                                $percentage = $total_relations > 0 ? ($relation['count'] / $total_relations) * 100 : 0;
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($relation['relation']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        <?php echo intval($relation['count']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <div class="w-24 bg-gray-200 rounded-full h-1.5 mr-2">
                                                <div class="bg-primary-600 h-1.5 rounded-full" style="width: <?php echo min(100, max(0, $percentage)); ?>%"></div>
                                            </div>
                                            <span class="text-xs"><?php echo number_format($percentage, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-people-arrows text-3xl mb-2 opacity-30"></i>
                        <p class="text-sm">No family relations recorded</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Users Table -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-trophy text-yellow-500 mr-2 text-sm"></i>
                    Top 10 Users
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Members</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            if (!empty($analytics['top_users']) && is_array($analytics['top_users'])) {
                                $rank = 1;
                                foreach ($analytics['top_users'] as $user): 
                                    if (!isset($user['name']) || !isset($user['email'])) continue;
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-medium">
                                        <?php if ($rank <= 3): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs <?php 
                                                echo $rank === 1 ? 'bg-yellow-100 text-yellow-800' : 
                                                     ($rank === 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800'); 
                                            ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-600 text-xs"><?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">
                                            <?php echo intval($user['family_member_count'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                                            <?php echo intval($user['record_count'] ?? 0); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                                    $rank++;
                                endforeach;
                            }
                            ?>
                            <?php if (empty($analytics['top_users'])): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-1"></i>
                                        <p class="text-sm">No data available</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Options -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-download text-primary-600 mr-3"></i>
                    Export
                </h3>
                <div class="flex flex-wrap gap-2">
                    <button onclick="printReport()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                    <button onclick="window.location.href='export.php?type=analytics'" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Wrap everything in DOMContentLoaded to prevent premature execution
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js Configuration
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#6B7280';

            // User Growth Chart
            <?php if (!empty($analytics['user_growth']) && is_array($analytics['user_growth'])): ?>
            try {
                const userGrowthCtx = document.getElementById('userGrowthChart');
                if (userGrowthCtx) {
                    new Chart(userGrowthCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($analytics['user_growth'], 'date'), JSON_HEX_TAG | JSON_HEX_AMP); ?>,
                            datasets: [{
                                label: 'New Users',
                                data: <?php echo json_encode(array_map('intval', array_column($analytics['user_growth'], 'count'))); ?>,
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: true }
                            },
                            scales: {
                                y: { beginAtZero: true, ticks: { stepSize: 1 } }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error creating user growth chart:', error);
            }
        <?php endif; ?>

        // Record Types Chart
        <?php if (!empty($analytics['record_types']) && is_array($analytics['record_types'])): ?>
        try {
            const recordTypesCtx = document.getElementById('recordTypesChart');
            if (recordTypesCtx) {
                const recordLabels = <?php 
                    $labels = array_map(function($item) {
                        return mb_substr(trim($item['record_type'] ?? ''), 0, 100); // Limit to 100 chars
                    }, $analytics['record_types']);
                    echo json_encode(array_values($labels), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); 
                ?>;
                const recordCounts = <?php echo json_encode(array_map('intval', array_column($analytics['record_types'], 'count'))); ?>;
                
                if (recordLabels && recordCounts && recordLabels.length > 0 && recordCounts.length > 0) {
                    new Chart(recordTypesCtx.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: recordLabels,
                            datasets: [{
                                data: recordCounts,
                                backgroundColor: [
                                    '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                                    '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Error creating record types chart:', error);
            console.log('Record types data:', <?php echo json_encode($analytics['record_types']); ?>);
        }
        <?php endif; ?>

        // Age Distribution Chart
        <?php if (!empty($analytics['age_distribution']) && is_array($analytics['age_distribution'])): ?>
        try {
            const ageDistCtx = document.getElementById('ageDistributionChart');
            if (ageDistCtx) {
                new Chart(ageDistCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($analytics['age_distribution'], 'age_group'), JSON_HEX_TAG | JSON_HEX_AMP); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_map('intval', array_column($analytics['age_distribution'], 'count'))); ?>,
                            backgroundColor: ['#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#0ea5e9']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error creating age distribution chart:', error);
        }
        <?php endif; ?>

        // Reminder Status Chart
        <?php if (!empty($analytics['reminders_status']) && is_array($analytics['reminders_status'])): ?>
        try {
            const reminderStatusCtx = document.getElementById('reminderStatusChart');
            if (reminderStatusCtx) {
                new Chart(reminderStatusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($analytics['reminders_status'], 'status'), JSON_HEX_TAG | JSON_HEX_AMP); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_map('intval', array_column($analytics['reminders_status'], 'count'))); ?>,
                            backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error creating reminder status chart:', error);
        }
        <?php endif; ?>

        // Monthly Activity Chart
        <?php if (!empty($analytics['monthly_activity']) && is_array($analytics['monthly_activity'])): ?>
        try {
            const monthlyActivityCtx = document.getElementById('monthlyActivityChart');
            if (monthlyActivityCtx) {
                new Chart(monthlyActivityCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_column($analytics['monthly_activity'], 'month'), JSON_HEX_TAG | JSON_HEX_AMP); ?>,
                        datasets: [{
                            label: 'Records Uploaded',
                            data: <?php echo json_encode(array_map('intval', array_column($analytics['monthly_activity'], 'count'))); ?>,
                            backgroundColor: '#0ea5e9'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error creating monthly activity chart:', error);
        }
        <?php endif; ?>

        }); // End DOMContentLoaded

        // Print Report Function
        function printReport() {
            window.print();
        }
    </script>

    <style>
        @media print {
            aside, header button, .no-print {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 1rem !important;
            }
        }
    </style>
</body>
</html>
