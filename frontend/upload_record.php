<?php
session_start();
require_once '../config/config.php';

// Check for login and member_id
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
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
$message_type = ''; // 'success' or 'error'

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
    $message = "Database Error: " . $e->getMessage();
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['record_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileExt, $allowed)) {
        $message = 'Error: Only PDF, JPG, and PNG files are allowed.';
        $message_type = 'error';
    } elseif ($fileError !== 0) {
        $message = 'There was an error uploading your file. Please try again.';
        $message_type = 'error';
    } elseif ($fileSize > 5242880) { // 5MB limit
        $message = 'Your file is too large (max 5MB).';
        $message_type = 'error';
    } else {
        $fileNameNew = uniqid('', true) . '.' . $fileExt;
        $fileDestination = '../uploads/health_records/' . $fileNameNew;
        
        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            $record_type = $_POST['record_type'];
            $record_date = $_POST['record_date'];
            $doctor_name = !empty($_POST['doctor_name']) ? $_POST['doctor_name'] : null;
            $hospital_name = !empty($_POST['hospital_name']) ? $_POST['hospital_name'] : null;

            try {
                $stmt = $pdo->prepare("INSERT INTO medical_records (member_id, record_type, record_date, doctor_name, hospital_name, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$member_id, $record_type, $record_date, $doctor_name, $hospital_name, $fileDestination, $fileExt])) {
                    $message = 'Record uploaded successfully! You will be redirected shortly.';
                    $message_type = 'success';
                    header('Refresh: 3; URL=view_records.php?member_id=' . $member_id);
                } else {
                    $message = 'Failed to save record metadata to the database.';
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'Error moving the uploaded file. Please check server permissions.';
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
    <title>Upload Record - Health Locker</title>
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
                <a href="view_records.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="text-gray-500 hover:text-primary-600 mr-4">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Upload New Record</h1>
                    <p class="text-gray-600 text-lg">For <?php echo htmlspecialchars($member_name); ?></p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-lg">
                <form action="upload_record.php?member_id=<?php echo htmlspecialchars($member_id); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php if (!empty($message)): ?>
                        <div id="alert-message" class="p-4 rounded-lg flex items-start <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3 mt-1"></i>
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                                <?php if ($message_type === 'success'): ?>
                                    <p class="text-sm">You will be redirected to the records page automatically.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member_id); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="record_type" class="block text-sm font-medium text-gray-700 mb-1">Record Type</label>
                            <select id="record_type" name="record_type" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="Prescription">Prescription</option>
                                <option value="Lab Report">Lab Report</option>
                                <option value="Scan">Scan</option>
                                <option value="Discharge Summary">Discharge Summary</option>
                                <option value="Vaccination">Vaccination</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="record_date" class="block text-sm font-medium text-gray-700 mb-1">Date of Record</label>
                            <input type="date" id="record_date" name="record_date" required class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="doctor_name" class="block text-sm font-medium text-gray-700 mb-1">Doctor's Name (Optional)</label>
                            <input type="text" id="doctor_name" name="doctor_name" placeholder="e.g., Dr. Smith" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-1">Hospital/Clinic (Optional)</label>
                            <input type="text" id="hospital_name" name="hospital_name" placeholder="e.g., City General Hospital" class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload File</label>
                        <div id="file-drop-area" class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary-500 transition-colors">
                            <input type="file" id="record_file" name="record_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="flex flex-col items-center justify-center space-y-4" id="file-drop-prompt">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                <p class="text-gray-600">
                                    <span class="font-semibold text-primary-600">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500">PDF, PNG, JPG, or JPEG (Max 5MB)</p>
                            </div>
                            <div id="file-name-display" class="hidden items-center justify-center text-gray-700">
                                <i class="fas fa-file-alt text-2xl mr-3 text-primary-500"></i>
                                <span id="chosen-file-name" class="font-medium"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="view_records.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 flex items-center">
                            <i class="fas fa-check mr-2"></i>
                            Submit Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileDropArea = document.getElementById('file-drop-area');
            const fileInput = document.getElementById('record_file');
            const fileDropPrompt = document.getElementById('file-drop-prompt');
            const fileNameDisplay = document.getElementById('file-name-display');
            const chosenFileName = document.getElementById('chosen-file-name');
            const alertMessage = document.getElementById('alert-message');

            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.transition = 'opacity 0.5s ease';
                    alertMessage.style.opacity = '0';
                    setTimeout(() => alertMessage.remove(), 500);
                }, 5000); // Hide after 5 seconds
            }

            fileDropArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    chosenFileName.textContent = fileInput.files[0].name;
                    fileDropPrompt.classList.add('hidden');
                    fileNameDisplay.classList.remove('hidden');
                    fileNameDisplay.classList.add('flex');
                }
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('border-primary-500', 'bg-primary-50'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('border-primary-500', 'bg-primary-50'), false);
            });

            fileDropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                let dt = e.dataTransfer;
                let files = dt.files;
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
