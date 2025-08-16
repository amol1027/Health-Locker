<?php
session_start();
require_once '../config/config.php';

// Check for login and member_id
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['member_id']) && !isset($_POST['member_id'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : $_POST['member_id'];
$member_name = '';
$message = '';

try {
    // Verify that the member belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM family_members WHERE id = ? AND user_id = ?");
    $stmt->execute([$member_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        header('Location: dashboard.php');
        exit;
    }
    $member_name = $member['first_name'] . ' ' . $member['last_name'];

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // File upload processing
    $file = $_FILES['record_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileExt, $allowed)) {
        $message = 'Error: Only PDF, JPG, and PNG files are allowed.';
    } elseif ($fileError !== 0) {
        $message = 'There was an error uploading your file.';
    } elseif ($fileSize > 5000000) { // 5MB limit
        $message = 'Your file is too large (max 5MB).';
    } else {
        // Secure file upload
        $fileNameNew = uniqid('', true) . $fileExt;
        $fileDestination = '../uploads/health_records/' . $fileNameNew; // This directory MUST be outside the web root
        
        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            // Insert record metadata into the database
            $record_type = $_POST['record_type'];
            $record_date = $_POST['record_date'];
            $doctor_name = $_POST['doctor_name'];
            $hospital_name = $_POST['hospital_name'];

            try {
                $stmt = $pdo->prepare("INSERT INTO medical_records (member_id, record_type, record_date, doctor_name, hospital_name, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$member_id, $record_type, $record_date, $doctor_name, $hospital_name, $fileDestination, $fileExt])) {
                    $message = 'Record uploaded successfully! Redirecting...';
                    header('Refresh: 2; URL=view_records.php?member_id=' . $member_id);
                } else {
                    $message = 'Failed to save record metadata.';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
            }
        } else {
            $message = 'Error moving uploaded file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Record - Health Locker</title>
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
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Upload a New Medical Record</h2>
        <form action="upload_record.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
            <?php if (isset($message)): ?>
                <div class="mb-4 p-3 rounded <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member_id); ?>">

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="member_select">Record for</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200 leading-tight focus:outline-none" id="member_select" type="text" value="<?php echo htmlspecialchars($member_name); ?>" readonly>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="record_type">Record Type</label>
                    <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="record_type" name="record_type">
                        <option value="Prescription">Prescription</option>
                        <option value="Lab Report">Lab Report</option>
                        <option value="Scan">Scan</option>
                        <option value="Discharge Summary">Discharge Summary</option>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="record_date">Date of Record</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="record_date" name="record_date" type="date" required>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="doctor_name">Doctor's Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="doctor_name" name="doctor_name" type="text">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="hospital_name">Hospital/Clinic Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="hospital_name" name="hospital_name" type="text">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="record_file">Upload File (PDF, JPG, PNG)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="record_file" name="record_file" type="file" required>
            </div>
            
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Upload Record
                </button>
                <a href="view_records.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>
