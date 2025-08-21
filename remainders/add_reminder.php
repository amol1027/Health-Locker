<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // 'success' or 'error'

$family_members = [];
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM family_members WHERE user_id = ? ORDER BY first_name ASC");
    $stmt->execute([$user_id]);
    $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $message_type = 'error';
}

$selected_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
$member_name = '';

if ($selected_member_id) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM family_members WHERE id = ? AND user_id = ?");
        $stmt->execute([$selected_member_id, $user_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($member) {
            $member_name = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
        } else {
            header('Location: ../frontend/dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $reminder_text = trim($_POST['reminder_text']);
    $reminder_datetime = $_POST['reminder_datetime'];

    if (empty($member_id) || empty($reminder_text) || empty($reminder_datetime)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reminders (user_id, member_id, reminder_text, reminder_datetime, is_sent) VALUES (?, ?, ?, ?, 0)");
            if ($stmt->execute([$user_id, $member_id, $reminder_text, $reminder_datetime])) {
                $message = 'Reminder set successfully!';
                $message_type = 'success';
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
    <title>Add Reminder - Health Locker</title>
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
                <a href="../frontend/dashboard.php" class="text-2xl font-bold text-primary-600">Health Locker</a>
                <div class="flex items-center space-x-4">
                    <a href="../frontend/dashboard.php" class="text-gray-600 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium flex items-center"><i class="fas fa-home mr-2"></i> Dashboard</a>
                    <a href="../user/logout.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 font-medium">Log Out</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container mx-auto mt-10 p-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center mb-8">
                <a href="../frontend/dashboard.php" class="text-gray-500 hover:text-primary-600 mr-4">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Set a New Reminder</h1>
                    <p class="text-gray-600 text-lg">Schedule reminders for medications or appointments.</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-lg">
                <form action="add_reminder.php<?php if ($selected_member_id) echo '?member_id=' . $selected_member_id; ?>" method="POST" class="space-y-6">
                    <?php if (!empty($message)): ?>
                        <div id="alert-message" class="p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                            <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label for="member_id" class="block text-sm font-medium text-gray-700 mb-1">Reminder For</label>
                        <?php if ($selected_member_id && $member_name): ?>
                            <div class="w-full py-2 px-3 text-gray-800 bg-gray-100 rounded-md border border-gray-200 flex items-center">
                                <i class="fas fa-user mr-3 text-gray-400"></i>
                                <?php echo $member_name; ?>
                            </div>
                            <input type="hidden" name="member_id" value="<?php echo $selected_member_id; ?>">
                        <?php else: ?>
                            <select id="member_id" name="member_id" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select a family member</option>
                                <?php foreach ($family_members as $member): ?>
                                    <option value="<?php echo htmlspecialchars($member['id']); ?>">
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="reminder_text" class="block text-sm font-medium text-gray-700 mb-1">Reminder Details</label>
                        <textarea id="reminder_text" name="reminder_text" rows="4" placeholder="e.g., Take Paracetamol after dinner" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>

                    <div>
                        <label for="reminder_datetime" class="block text-sm font-medium text-gray-700 mb-1">Date and Time</label>
                        <input type="datetime-local" id="reminder_datetime" name="reminder_datetime" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="../frontend/dashboard.php" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 flex items-center">
                            <i class="fas fa-bell mr-2"></i>
                            Set Reminder
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
