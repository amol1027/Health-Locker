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

// Handle reminder deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reminder'])) {
    $reminder_id = $_POST['reminder_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = ?");
        if ($stmt->execute([$reminder_id])) {
            $message = 'Reminder deleted successfully!';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting reminder: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch all reminders
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, sent, pending
$search = isset($_GET['search']) ? $_GET['search'] : '';
$reminders = [];

try {
    $sql = "
        SELECT r.id, r.reminder_text, r.reminder_datetime, r.is_sent, r.created_at,
               fm.first_name, fm.last_name,
               u.name as user_name, u.email as user_email
        FROM reminders r
        JOIN family_members fm ON r.member_id = fm.id
        JOIN users u ON r.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($filter === 'sent') {
        $sql .= " AND r.is_sent = 1";
    } elseif ($filter === 'pending') {
        $sql .= " AND r.is_sent = 0";
    }
    
    if (!empty($search)) {
        $sql .= " AND (fm.first_name LIKE ? OR fm.last_name LIKE ? OR u.name LIKE ? OR r.reminder_text LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $sql .= " ORDER BY r.reminder_datetime DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching reminders: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders - Admin Dashboard</title>
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
                        <a href="reminders.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-800">Reminders Management</h1>
                <p class="text-gray-600 mt-2">View and manage all scheduled reminders</p>
            </div>

            <?php if (!empty($message)): ?>
                <div id="alert-message" class="mb-6 p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="reminders.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by patient, user, or reminder text..." 
                                class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                        </div>
                    </div>
                    <div>
                        <select name="filter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Reminders</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="sent" <?php echo $filter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex space-x-3">
                        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <?php if (!empty($search) || $filter !== 'all'): ?>
                            <a href="reminders.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-times mr-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Reminders Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php if (!empty($reminders)): ?>
                    <?php foreach ($reminders as $reminder): ?>
                        <?php
                        $isPast = strtotime($reminder['reminder_datetime']) < time();
                        $statusClass = $reminder['is_sent'] ? 'bg-green-100 text-green-800' : ($isPast ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                        $statusText = $reminder['is_sent'] ? 'Sent' : ($isPast ? 'Overdue' : 'Pending');
                        ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="bg-yellow-100 rounded-full p-3">
                                    <i class="fas fa-bell text-2xl text-yellow-600"></i>
                                </div>
                                <span class="px-3 py-1 <?php echo $statusClass; ?> rounded-full text-xs font-semibold">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-800 mb-3">
                                <?php echo htmlspecialchars($reminder['reminder_text']); ?>
                            </h3>
                            
                            <div class="space-y-2 mb-4">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-user fa-fw mr-2 text-gray-400"></i>
                                    <strong>Patient:</strong> <?php echo htmlspecialchars($reminder['first_name'] . ' ' . $reminder['last_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-user-circle fa-fw mr-2 text-gray-400"></i>
                                    <strong>User:</strong> <?php echo htmlspecialchars($reminder['user_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock fa-fw mr-2 text-gray-400"></i>
                                    <strong>Scheduled:</strong> <?php echo date('M d, Y h:i A', strtotime($reminder['reminder_datetime'])); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-calendar-plus fa-fw mr-2 text-gray-400"></i>
                                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($reminder['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div class="flex space-x-2 pt-4 border-t">
                                <button onclick="confirmDelete(<?php echo $reminder['id']; ?>)" class="flex-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-20">
                        <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl text-gray-500">No reminders found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete this reminder? This action cannot be undone.</p>
                <form method="POST" action="reminders.php">
                    <input type="hidden" name="reminder_id" id="reminderId">
                    <input type="hidden" name="delete_reminder" value="1">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete Reminder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(reminderId) {
            document.getElementById('reminderId').value = reminderId;
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
