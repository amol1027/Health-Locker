<?php
session_start();
require_once '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $relation = trim($_POST['relation']);
    $date_of_birth = $_POST['date_of_birth'];
    $blood_type = trim($_POST['blood_type']);
    $known_allergies = trim($_POST['known_allergies']);

    if (empty($first_name) || empty($last_name) || empty($relation)) {
        $message = 'First Name, Last Name, and Relation are required fields.';
    } else {
        try {
            // Insert new family member into the database
            $stmt = $pdo->prepare("
                INSERT INTO family_members (user_id, first_name, last_name, date_of_birth, relation, blood_type, known_allergies)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $first_name, $last_name, $date_of_birth, $relation, $blood_type, $known_allergies])) {
                $message = 'Family member added successfully! Redirecting to dashboard...';
                header('Refresh: 2; URL=dashboard.php');
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
    <title>Add Family Member - Health Locker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">Health Locker</h1>
        <div>
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700 font-medium mr-4">Dashboard</a>
            <a href="logout.php" class="text-red-500 hover:text-red-700 font-medium">Log Out</a>
        </div>
    </nav>
    <div class="container mx-auto mt-8 p-4 max-w-2xl">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Add a New Family Member</h2>
        <form action="add_member.php" method="POST" class="bg-white p-6 rounded-lg shadow-md">
            <?php if (isset($message)): ?>
                <div class="mb-4 p-3 rounded <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">First Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="first_name" name="first_name" type="text" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">Last Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="last_name" name="last_name" type="text" required>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="relation">Relation</label>
                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="relation" name="relation">
                    <option value="Self">Self</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Son">Son</option>
                    <option value="Daughter">Daughter</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date_of_birth">Date of Birth</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="date_of_birth" name="date_of_birth" type="date">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="blood_type">Blood Type</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="blood_type" name="blood_type" type="text" placeholder="e.g., O+">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="known_allergies">Known Allergies</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="known_allergies" name="known_allergies" rows="3" placeholder="List any known allergies"></textarea>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Add Member
                </button>
                <a href="dashboard.php" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>