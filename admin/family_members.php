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

// Handle family member deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_member'])) {
    $member_id = $_POST['member_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM family_members WHERE id = ?");
        if ($stmt->execute([$member_id])) {
            $message = 'Family member deleted successfully!';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting family member: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch all family members with user info
$search = isset($_GET['search']) ? $_GET['search'] : '';
$members = [];

try {
    $sql = "
        SELECT fm.id, fm.first_name, fm.last_name, fm.date_of_birth, fm.relation, 
               fm.blood_type, fm.known_allergies, fm.created_at,
               u.name as user_name, u.email as user_email,
               COUNT(mr.id) as medical_records_count
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
    ";
    
    if (!empty($search)) {
        $sql .= " WHERE fm.first_name LIKE ? OR fm.last_name LIKE ? OR u.name LIKE ?";
    }
    
    $sql .= " GROUP BY fm.id ORDER BY fm.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt->execute();
    }
    
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching family members: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Members - Admin Dashboard</title>
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
                        <a href="family_members.php" class="flex items-center px-4 py-3 text-white bg-primary-600 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-800">Family Members Management</h1>
                <p class="text-gray-600 mt-2">View and manage all family member profiles</p>
            </div>

            <?php if (!empty($message)): ?>
                <div id="alert-message" class="mb-6 p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="family_members.php" class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by member name or user name..." 
                                class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                        </div>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="family_members.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Members Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($members)): ?>
                    <?php foreach ($members as $member): ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="bg-green-100 rounded-full p-3">
                                    <i class="fas fa-user-friends text-2xl text-green-600"></i>
                                </div>
                                <span class="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($member['relation']); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                            </h3>
                            
                            <div class="space-y-2 mb-4">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-user fa-fw mr-2 text-gray-400"></i>
                                    <strong>Owner:</strong> <?php echo htmlspecialchars($member['user_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-birthday-cake fa-fw mr-2 text-gray-400"></i>
                                    <strong>DOB:</strong> <?php echo date('M d, Y', strtotime($member['date_of_birth'])); ?>
                                </p>
                                <?php if (!empty($member['blood_type'])): ?>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-tint fa-fw mr-2 text-gray-400"></i>
                                        <strong>Blood:</strong> <?php echo htmlspecialchars($member['blood_type']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-file-medical fa-fw mr-2 text-gray-400"></i>
                                    <strong>Records:</strong> <?php echo $member['medical_records_count']; ?>
                                </p>
                            </div>
                            
                            <?php if (!empty($member['known_allergies'])): ?>
                                <div class="mb-4 p-3 bg-yellow-50 rounded-lg">
                                    <p class="text-xs text-yellow-800">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <strong>Allergies:</strong> <?php echo htmlspecialchars($member['known_allergies']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex space-x-2 pt-4 border-t">
                                <button onclick="viewMember(<?php echo $member['id']; ?>)" class="flex-1 px-3 py-2 bg-primary-100 text-primary-700 rounded-lg hover:bg-primary-200 text-sm font-medium">
                                    <i class="fas fa-eye mr-1"></i>View
                                </button>
                                <button onclick="confirmDelete(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')" class="flex-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-20">
                        <i class="fas fa-user-friends text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl text-gray-500">No family members found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Member Details Modal -->
    <div id="memberDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">Family Member Details</h3>
                <button onclick="closeMemberDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="memberDetailsContent" class="p-6">
                <div class="flex items-center justify-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-green-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="memberName" class="font-semibold"></span>? This will also delete all their medical records. This action cannot be undone.</p>
                <form method="POST" action="family_members.php">
                    <input type="hidden" name="member_id" id="memberId">
                    <input type="hidden" name="delete_member" value="1">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(memberId, memberName) {
            document.getElementById('memberId').value = memberId;
            document.getElementById('memberName').textContent = memberName;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        async function viewMember(memberId) {
            try {
                const response = await fetch(`get_member_details.php?id=${memberId}`);
                const data = await response.json();
                
                if (data.success) {
                    const member = data.member;
                    const age = member.date_of_birth ? calculateAge(member.date_of_birth) : 'N/A';
                    
                    document.getElementById('memberDetailsContent').innerHTML = `
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4 pb-4 border-b">
                                <div class="bg-green-100 rounded-full p-4">
                                    <i class="fas fa-user text-3xl text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-800">${member.first_name} ${member.last_name}</h3>
                                    <p class="text-gray-600"><i class="fas fa-envelope mr-2"></i>${member.user_email}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600"><i class="fas fa-heart mr-2"></i>Relation</p>
                                    <p class="text-xl font-bold text-blue-600">${member.relation || 'N/A'}</p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600"><i class="fas fa-tint mr-2"></i>Blood Type</p>
                                    <p class="text-xl font-bold text-red-600">${member.blood_type || 'N/A'}</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600"><i class="fas fa-birthday-cake mr-2"></i>Age</p>
                                    <p class="text-xl font-bold text-purple-600">${age} years</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600"><i class="fas fa-file-medical mr-2"></i>Records</p>
                                    <p class="text-xl font-bold text-green-600">${member.records_count}</p>
                                </div>
                            </div>
                            
                            ${member.known_allergies ? `
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                    <div class="flex">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                                        <div>
                                            <p class="font-semibold text-yellow-800">Known Allergies:</p>
                                            <p class="text-yellow-700">${member.known_allergies}</p>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${data.recent_records.length > 0 ? `
                                <div class="mt-4">
                                    <h4 class="font-semibold text-gray-800 mb-2"><i class="fas fa-file-medical-alt mr-2"></i>Recent Medical Records:</h4>
                                    <div class="space-y-2">
                                        ${data.recent_records.map(record => `
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-800">${record.record_type}</p>
                                                    <p class="text-sm text-gray-600">${record.doctor_name || 'N/A'} • ${record.hospital_name || 'N/A'}</p>
                                                </div>
                                                <span class="text-sm text-gray-500">${new Date(record.record_date).toLocaleDateString()}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : '<p class="text-gray-500 text-center py-4">No medical records found</p>'}
                            
                            <div class="text-xs text-gray-500 text-center pt-4 border-t">
                                Member since: ${new Date(member.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    `;
                    document.getElementById('memberDetailsModal').classList.remove('hidden');
                } else {
                    alert('Error loading member details: ' + data.message);
                }
            } catch (error) {
                alert('Error loading member details');
                console.error(error);
            }
        }
        
        function closeMemberDetailsModal() {
            document.getElementById('memberDetailsModal').classList.add('hidden');
        }
        
        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
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
