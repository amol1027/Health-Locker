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

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = 'User deleted successfully!';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting user: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch all users with their statistics
$search = isset($_GET['search']) ? $_GET['search'] : '';
$users = [];

try {
    $sql = "
        SELECT u.id, u.name, u.email, u.created_at,
               COUNT(DISTINCT fm.id) as family_members_count,
               COUNT(DISTINCT mr.id) as medical_records_count
        FROM users u
        LEFT JOIN family_members fm ON u.id = fm.user_id
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
    ";
    
    if (!empty($search)) {
        $sql .= " WHERE u.name LIKE ? OR u.email LIKE ?";
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm]);
    } else {
        $stmt->execute();
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
                        <a href="users.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600 mt-2">Manage all registered users in the system</p>
            </div>

            <?php if (!empty($message)): ?>
                <div id="alert-message" class="mb-6 p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Search & Filter</h3>
                    <a href="export.php?type=users&format=csv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-download mr-2"></i>Export to CSV
                    </a>
                </div>
                <form method="GET" action="users.php" class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by name or email..." 
                                class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                        </div>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Family Members</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medical Records</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="bg-primary-100 rounded-full p-2 mr-3">
                                                    <i class="fas fa-user text-primary-600"></i>
                                                </div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                                <?php echo $user['family_members_count']; ?> members
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full">
                                                <?php echo $user['medical_records_count']; ?> records
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="viewUser(<?php echo $user['id']; ?>)" class="text-primary-600 hover:text-primary-900">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg">No users found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">User Details</h3>
                <button onclick="closeUserDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="userDetailsContent" class="p-6">
                <div class="flex items-center justify-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete user <span id="userName" class="font-semibold"></span>? This will also delete all their family members and medical records. This action cannot be undone.</p>
                <form method="POST" action="users.php">
                    <input type="hidden" name="user_id" id="userId">
                    <input type="hidden" name="delete_user" value="1">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(userId, userName) {
            document.getElementById('userId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        async function viewUser(userId) {
            // Fetch user details
            try {
                const response = await fetch(`get_user_details.php?id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4 pb-4 border-b">
                                <div class="bg-primary-100 rounded-full p-4">
                                    <i class="fas fa-user text-3xl text-primary-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-800">${data.user.name}</h3>
                                    <p class="text-gray-600">${data.user.email}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Family Members</p>
                                    <p class="text-2xl font-bold text-blue-600">${data.user.family_members_count}</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Medical Records</p>
                                    <p class="text-2xl font-bold text-purple-600">${data.user.medical_records_count}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Reminders</p>
                                    <p class="text-2xl font-bold text-green-600">${data.user.reminders_count || 0}</p>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Joined Date</p>
                                    <p class="text-lg font-bold text-yellow-600">${new Date(data.user.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                            
                            ${data.family_members.length > 0 ? `
                                <div class="mt-4">
                                    <h4 class="font-semibold text-gray-800 mb-2">Family Members:</h4>
                                    <div class="space-y-2">
                                        ${data.family_members.map(member => `
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-800">${member.first_name} ${member.last_name}</p>
                                                    <p class="text-sm text-gray-600">${member.relation || 'N/A'} ${member.blood_type ? '• ' + member.blood_type : ''}</p>
                                                </div>
                                                <span class="text-sm text-gray-500">${member.records_count} records</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : '<p class="text-gray-500 text-center py-4">No family members added yet</p>'}
                        </div>
                    `;
                    document.getElementById('userDetailsModal').classList.remove('hidden');
                } else {
                    alert('Error loading user details: ' + data.message);
                }
            } catch (error) {
                alert('Error loading user details');
                console.error(error);
            }
        }
        
        function closeUserDetailsModal() {
            document.getElementById('userDetailsModal').classList.add('hidden');
        }

        // Auto-hide alert messages
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
