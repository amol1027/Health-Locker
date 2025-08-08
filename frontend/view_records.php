<?php
session_start();
require_once '../config/config.php';

// Check if the user is logged in and if a member_id is provided in the URL
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    header('Location: dashboard.php'); // Redirect if no valid member_id
    exit;
}

$user_id = $_SESSION['user_id'];
$member_id = $_GET['member_id'];
$member_name = '';
$records = [];

try {
    // First, verify that this member belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM family_members WHERE id = ? AND user_id = ?");
    $stmt->execute([$member_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // If the member doesn't exist or doesn't belong to the user, redirect
        header('Location: dashboard.php');
        exit;
    }
    $member_name = $member['first_name'] . ' ' . $member['last_name'];

    // Then, fetch all medical records for this member, ordered chronologically
    $stmt = $pdo->prepare("SELECT id, record_type, record_date, doctor_name, hospital_name FROM medical_records WHERE member_id = ? ORDER BY record_date DESC");
    $stmt->execute([$member_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Health Locker</title>
    <link href="https://cdn.jsdelivr/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">Health Locker</h1>
        <div>
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700 font-medium mr-4">Dashboard</a>
            <a href="logout.php" class="text-red-500 hover:text-red-700 font-medium">Log Out</a>
        </div>
    </nav>
    <div class="container mx-auto mt-8 p-4 max-w-4xl">
        <?php if (isset($member_name)): ?>
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Medical Records for <?php echo htmlspecialchars($member_name); ?></h2>
        <?php else: ?>
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Medical Records</h2>
        <?php endif; ?>

        <a href="upload_record.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="mb-6 inline-block bg-green-500 text-white font-bold py-2 px-4 rounded hover:bg-green-600">Upload New Record</a>

        <div class="mt-6 space-y-4">
            <?php
                if (isset($records) && !empty($records)) {
                    foreach ($records as $record) {
                        echo '<div class="bg-white p-6 rounded-lg shadow-md flex items-start space-x-4">';
                        echo '    <div class="flex-shrink-0">';
                        echo '        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-500">';
                        echo '            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                        echo '                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-5 4h4a2 2 0 002-2V6a2 2 0 00-2-2H9a2 2 0 00-2 2v12a2 2 0 002 2z" />';
                        echo '            </svg>';
                        echo '        </div>';
                        echo '    </div>';
                        echo '    <div class="flex-grow">';
                        echo '        <div class="flex justify-between items-center">';
                        echo '            <div>';
                        echo '                <h4 class="text-lg font-semibold text-gray-900">' . htmlspecialchars($record['record_type']) . '</h4>';
                        echo '                <p class="text-sm text-gray-500">Date: ' . htmlspecialchars($record['record_date']) . '</p>';
                        echo '            </div>';
                        echo '            <a href="view_file.php?record_id=' . $record['id'] . '" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800">View File</a>';
                        echo '        </div>';
                        if (!empty($record['doctor_name'])) {
                            echo '<p class="text-sm text-gray-700 mt-1">Doctor: ' . htmlspecialchars($record['doctor_name']) . '</p>';
                        }
                        if (!empty($record['hospital_name'])) {
                            echo '<p class="text-sm text-gray-700">Hospital: ' . htmlspecialchars($record['hospital_name']) . '</p>';
                        }
                    }
                } else {
                    echo '<p class="text-gray-500">No medical records found for this family member. <a href="upload_record.php?member_id=' . htmlspecialchars($member_id) . '" class="text-blue-500 hover:text-blue-700">Upload one now</a>.</p>';
                }
            ?>
        </div>
    </div>
</body>
</html>