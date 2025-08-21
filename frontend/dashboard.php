<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User'; // Fallback to 'User' if name is not set
$family_members = [];

try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, relation, date_of_birth FROM family_members WHERE user_id = ? ORDER BY first_name ASC");
    $stmt->execute([$user_id]);
    $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real app, log this error and show a user-friendly message.
    die("Error fetching family members: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
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
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-800">Welcome Back, <?php echo $user_name?>!</h1>
                <p class="text-gray-600 text-lg">Manage your family's health records in one place.</p>
            </div>
            <a href="add_member.php" class="bg-primary-600 text-white px-5 py-2.5 rounded-lg hover:bg-primary-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Family Member
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
            <h3 class="text-2xl font-semibold text-gray-800 border-b pb-4 mb-6">My Family Members</h3>
            <div id="family-members-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($family_members)): ?>
                    <?php foreach ($family_members as $member): ?>
                        <div class="family-member-card bg-white p-6 rounded-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100 flex flex-col">
                            <div class="flex-grow">
                                <h4 class="text-xl font-bold text-primary-700 mb-2"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                                <p class="text-gray-600 mb-1"><i class="fas fa-user-friends fa-fw mr-2 text-gray-400"></i><span class="font-medium">Relation:</span> <?= htmlspecialchars($member['relation']) ?></p>
                                <p class="text-gray-600 mb-4"><i class="fas fa-birthday-cake fa-fw mr-2 text-gray-400"></i><span class="font-medium">D.O.B:</span> <?= htmlspecialchars(date("M d, Y", strtotime($member['date_of_birth']))) ?></p>
                            </div>
                            <div class="flex space-x-3 mt-4">
                                <a href="view_records.php?member_id=<?= $member['id'] ?>" class="flex-1 text-center px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors duration-200 text-sm font-medium flex items-center justify-center">
                                    <i class="fas fa-file-medical-alt mr-2"></i> View Records
                                </a>
                                <a href="../remainders/add_reminder.php?member_id=<?= $member['id'] ?>" class="flex-1 text-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm font-medium flex items-center justify-center">
                                    <i class="fas fa-bell mr-2"></i> Set Reminder
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-16 px-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-users text-6xl text-gray-300 mb-5"></i>
                        <h3 class="text-xl font-semibold text-gray-800">No Family Members Yet</h3>
                        <p class="text-gray-500 mt-2 max-w-md mx-auto">Click the "Add Family Member" button to get started and keep track of your loved ones' health records.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            anime({
                targets: '.family-member-card',
                translateY: [20, 0],
                opacity: [0, 1],
                delay: anime.stagger(100),
                easing: 'easeOutCubic',
                duration: 600
            });
        });
    </script>
</body>
</html>
