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

// Handle record deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
    $record_id = $_POST['record_id'];
    try {
        // Get file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM medical_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Delete the file if it exists
            if (file_exists($record['file_path'])) {
                unlink($record['file_path']);
            }
            
            // Delete the record
            $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ?");
            if ($stmt->execute([$record_id])) {
                $message = 'Medical record deleted successfully!';
                $message_type = 'success';
            }
        }
    } catch (PDOException $e) {
        $message = 'Error deleting record: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch all medical records
$search = isset($_GET['search']) ? $_GET['search'] : '';
$record_type = isset($_GET['record_type']) ? $_GET['record_type'] : '';
$records = [];

try {
    $sql = "
        SELECT mr.id, mr.record_type, mr.record_date, mr.doctor_name, mr.hospital_name, 
               mr.file_type, mr.created_at,
               fm.first_name, fm.last_name,
               u.name as user_name, u.email as user_email
        FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        JOIN users u ON fm.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (fm.first_name LIKE ? OR fm.last_name LIKE ? OR u.name LIKE ? OR mr.doctor_name LIKE ? OR mr.hospital_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($record_type)) {
        $sql .= " AND mr.record_type = ?";
        $params[] = $record_type;
    }
    
    $sql .= " ORDER BY mr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching medical records: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Admin Dashboard</title>
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
                        <a href="medical_records.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-800">Medical Records Management</h1>
                <p class="text-gray-600 mt-2">View and manage all medical records in the system</p>
            </div>

            <?php if (!empty($message)): ?>
                <div id="alert-message" class="mb-6 p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="medical_records.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by patient, user, doctor, or hospital..." 
                                class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                        </div>
                    </div>
                    <div>
                        <select name="record_type" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Record Types</option>
                            <option value="Prescription" <?php echo $record_type === 'Prescription' ? 'selected' : ''; ?>>Prescription</option>
                            <option value="Lab Report" <?php echo $record_type === 'Lab Report' ? 'selected' : ''; ?>>Lab Report</option>
                            <option value="Scan" <?php echo $record_type === 'Scan' ? 'selected' : ''; ?>>Scan</option>
                            <option value="Discharge Summary" <?php echo $record_type === 'Discharge Summary' ? 'selected' : ''; ?>>Discharge Summary</option>
                            <option value="Vaccination" <?php echo $record_type === 'Vaccination' ? 'selected' : ''; ?>>Vaccination</option>
                            <option value="Other" <?php echo $record_type === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex space-x-3">
                        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <?php if (!empty($search) || !empty($record_type)): ?>
                            <a href="medical_records.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-times mr-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Records Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User/Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor/Hospital</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($records)): ?>
                                <?php foreach ($records as $record): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $iconClass = 'text-gray-500';
                                            switch ($record['record_type']) {
                                                case 'Prescription': $iconClass = 'text-green-500'; break;
                                                case 'Lab Report': $iconClass = 'text-purple-500'; break;
                                                case 'Scan': $iconClass = 'text-yellow-500'; break;
                                                case 'Vaccination': $iconClass = 'text-indigo-500'; break;
                                            }
                                            ?>
                                            <div class="flex items-center">
                                                <i class="fas fa-file-medical mr-2 <?php echo $iconClass; ?>"></i>
                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($record['record_type']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['user_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php if (!empty($record['doctor_name'])): ?>
                                                <div><?php echo htmlspecialchars($record['doctor_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($record['hospital_name'])): ?>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($record['hospital_name']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($record['record_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs uppercase">
                                                <?php echo htmlspecialchars($record['file_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['record_type']); ?>')" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-file-medical text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg">No medical records found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete the <span id="recordType" class="font-semibold"></span> record? This action cannot be undone.</p>
                <form method="POST" action="medical_records.php">
                    <input type="hidden" name="record_id" id="recordId">
                    <input type="hidden" name="delete_record" value="1">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(recordId, recordType) {
            document.getElementById('recordId').value = recordId;
            document.getElementById('recordType').textContent = recordType;
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
