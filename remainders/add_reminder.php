<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch family members to populate the dropdown
$family_members = [];
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM family_members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// Check if a specific member is pre-selected from the URL
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
            // Member not found or doesn't belong to user, redirect or show error
            header('Location: ../frontend/dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        // Handle error
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $reminder_text = trim($_POST['reminder_text']);
    $reminder_datetime = $_POST['reminder_datetime'];

    if (empty($member_id) || empty($reminder_text) || empty($reminder_datetime)) {
        $message = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reminders (user_id, member_id, reminder_text, reminder_datetime) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $member_id, $reminder_text, $reminder_datetime])) {
                $message = 'Reminder set successfully!';
            } else {
                $message = 'Something went wrong. Please try again.';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">Health Locker</h1>
        <div>
            <a href="../frontend/dashboard.php" class="text-blue-500 hover:text-blue-700 font-medium mr-4">Dashboard</a>
            <a href="../user/logout.php" class="text-red-500 hover:text-red-700 font-medium">Log Out</a>
        </div>
    </nav>
    <div class="container mx-auto mt-8 p-4 max-w-2xl">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Add a New Reminder</h2>
        <form action="add_reminder.php" method="POST" class="bg-white p-6 rounded-lg shadow-md">
            <?php if (isset($message)): ?>
                <div class="mb-4 p-3 rounded <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="member_id">Reminder for</label>
                <?php if ($selected_member_id && $member_name): ?>
                    <div class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200 leading-tight">
                        <?php echo $member_name; ?>
                    </div>
                    <input type="hidden" name="member_id" value="<?php echo $selected_member_id; ?>">
                <?php else: ?>
                    <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="member_id" name="member_id" required>
                        <option value="">Select a family member</option>
                        <?php foreach ($family_members as $member): ?>
                            <option value="<?php echo htmlspecialchars($member['id']); ?>">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="reminder_text">Reminder Text</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="reminder_text" name="reminder_text" rows="3" placeholder="e.g., Take medication at 8 AM" required></textarea>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="reminder_datetime">Date and Time</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="reminder_datetime" name="reminder_datetime" type="datetime-local" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Set Reminder
                </button>
            </div>
        </form>
    </div>
</body>
</html>
