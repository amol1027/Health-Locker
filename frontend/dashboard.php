<?php
session_start();
require_once '../config/config.php';

// Check if the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$family_members = [];

try {
    // Fetch all family members for the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM family_members WHERE user_id = ? ORDER BY first_name ASC");
    $stmt->execute([$user_id]);
    $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database error
    // In a real application, you might log this error and show a user-friendly message
    // For now, we'll just show an error message
    echo "Error fetching family members: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health Locker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">Health Locker</h1>
        <a href="../user/logout.php" class="text-red-500 hover:text-red-700 font-medium">Log Out</a>
    </nav>
    <div class="container mx-auto mt-8 p-4">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Welcome to your Personal Health Locker</h2>
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h3 class="text-2xl font-semibold mb-4 text-gray-700">My Family</h3>
            <div id="family-members-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                    // This section will be populated by the PHP script
                    if (isset($family_members) && !empty($family_members)) {
                        foreach ($family_members as $member) {
                            echo '<div class="bg-blue-50 p-4 rounded-lg shadow-sm">';
                            echo '<h4 class="text-lg font-bold text-blue-800">' . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . '</h4>';
                            echo '<p class="text-sm text-gray-600">Relation: ' . htmlspecialchars($member['relation']) . '</p>';
                            echo '<p class="text-sm text-gray-600">D.O.B: ' . htmlspecialchars($member['date_of_birth']) . '</p>';
                            echo '<div class="mt-4">';
                            echo '<a href="view_records.php?member_id=' . $member['id'] . '" class="mr-2 inline-block bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">View Records</a>';
                            echo '<a href="../remainders/add_reminder.php?member_id=' . $member['id'] . '" class="inline-block bg-green-500 text-white text-sm px-4 py-2 rounded hover:bg-green-600">Set Reminder</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-gray-500">You haven\'t added any family members yet.</p>';
                    }
                ?>
            </div>
            <a href="add_member.php" class="mt-6 inline-block bg-green-500 text-white font-bold py-2 px-4 rounded hover:bg-green-600">Add New Family Member</a>
        </div>
    </div>
</body>
</html>
