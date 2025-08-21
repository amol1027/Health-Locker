<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$message = '';
$message_type = ''; // 'success' or 'error'
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $relation = trim($_POST['relation']);
    $date_of_birth = $_POST['date_of_birth'];
    $blood_type = !empty($_POST['blood_type']) ? trim($_POST['blood_type']) : null;
    $known_allergies = !empty($_POST['known_allergies']) ? trim($_POST['known_allergies']) : null;

    if (empty($first_name) || empty($last_name) || empty($relation) || empty($date_of_birth)) {
        $message = 'Please fill in all required fields (First Name, Last Name, Relation, and D.O.B).';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO family_members (user_id, first_name, last_name, date_of_birth, relation, blood_type, known_allergies) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$user_id, $first_name, $last_name, $date_of_birth, $relation, $blood_type, $known_allergies])) {
                $message = 'Family member added successfully! Redirecting to dashboard...';
                $message_type = 'success';
                header('Refresh: 3; URL=dashboard.php');
            } else {
                $message = 'Something went wrong. Please try again.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Family Member - Health Locker</title>
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
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex justify-between items-center py-4">
                <a href="dashboard.php" class="text-2xl font-bold text-primary-600">Health Locker</a>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium flex items-center"><i class="fas fa-home mr-2"></i> Dashboard</a>
                    <a href="../user/logout.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 font-medium">Log Out</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container mx-auto mt-10 p-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center mb-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-primary-600 mr-4">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Family Member</h1>
                    <p class="text-gray-600 text-lg">Enter the details of your new family member.</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-lg">
                <form action="add_member.php" method="POST" class="space-y-6">
                    <?php if (!empty($message)): ?>
                        <div id="alert-message" class="p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                            <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input type="text" id="first_name" name="first_name" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" id="last_name" name="last_name" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="relation" class="block text-sm font-medium text-gray-700 mb-1">Relation <span class="text-red-500">*</span></label>
                            <select id="relation" name="relation" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="Self">Self</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    <div>
                        <label for="blood_type" class="block text-sm font-medium text-gray-700 mb-1">Blood Type (Optional)</label>
                        <input type="text" id="blood_type" name="blood_type" placeholder="e.g., O+" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label for="known_allergies" class="block text-sm font-medium text-gray-700 mb-1">Known Allergies (Optional)</label>
                        <textarea id="known_allergies" name="known_allergies" rows="4" placeholder="List any known allergies, e.g., Peanuts, Penicillin" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="dashboard.php" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alert-message');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(() => alertMessage.remove(), 500);
                }, 5000); // Hide after 5 seconds
            }
        });
    </script>
</body>
</html>
