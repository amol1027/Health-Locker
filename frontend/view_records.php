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
 // In your original PHP code, modify the query to include file_type:
    $stmt = $pdo->prepare("SELECT id, record_type, record_date, doctor_name, hospital_name, file_type FROM medical_records WHERE member_id = ? ORDER BY record_date DESC");
    $stmt->execute([$member_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
// After the existing code that verifies the member belongs to the user
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$record_type_filter = isset($_GET['record_type_filter']) ? $_GET['record_type_filter'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build the query with filters
$sql = "SELECT id, record_type, record_date, doctor_name, hospital_name, file_type 
        FROM medical_records 
        WHERE member_id = ?";
$params = [$member_id];

// Add search query filter
if (!empty($search_query)) {
    $sql .= " AND (doctor_name LIKE ? OR hospital_name LIKE ? OR record_type LIKE ?)";
    $search_term = "%$search_query%";
    array_push($params, $search_term, $search_term, $search_term);
}

// Add record type filter
if (!empty($record_type_filter)) {
    $sql .= " AND record_type = ?";
    array_push($params, $record_type_filter);
}

// Add date range filter
if (!empty($start_date)) {
    $sql .= " AND record_date >= ?";
    array_push($params, $start_date);
}
if (!empty($end_date)) {
    $sql .= " AND record_date <= ?";
    array_push($params, $end_date);
}

// Add sorting
$sql .= " ORDER BY record_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Health Locker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    
    <!-- Filter Modal -->
    <div id="filterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center p-5 border-b">
                <h3 class="text-xl font-semibold text-gray-800">Search & Filter Records</h3>
                <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
           <form action="view_records.php" method="GET" class="p-5">
    <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member_id); ?>">
    <div class="mb-4">
        <label for="search_query" class="block text-gray-700 text-sm font-medium mb-2">Search</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="search_query" name="search_query" 
                   value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>" 
                   placeholder="Doctor, hospital, or keywords..." 
                   class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
    </div>
    <div class="mb-4">
        <label for="record_type_filter" class="block text-gray-700 text-sm font-medium mb-2">Record Type</label>
        <select id="record_type_filter" name="record_type_filter" 
                class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">All Record Types</option>
            <option value="Prescription" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Prescription' ? 'selected' : ''); ?>>Prescription</option>
            <option value="Lab Report" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Lab Report' ? 'selected' : ''); ?>>Lab Report</option>
            <option value="Scan" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Scan' ? 'selected' : ''); ?>>Scan</option>
            <option value="Discharge Summary" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Discharge Summary' ? 'selected' : ''); ?>>Discharge Summary</option>
            <option value="Vaccination" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Vaccination' ? 'selected' : ''); ?>>Vaccination</option>
            <option value="Other" <?php echo (isset($_GET['record_type_filter']) && $_GET['record_type_filter'] === 'Other' ? 'selected' : ''); ?>>Other</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-medium mb-2">Date Range</label>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="start_date" class="block text-xs text-gray-500 mb-1">From</label>
                <input type="date" id="start_date" name="start_date" 
                       value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>" 
                       class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label for="end_date" class="block text-xs text-gray-500 mb-1">To</label>
                <input type="date" id="end_date" name="end_date" 
                       value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" 
                       class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
        </div>
    </div>
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" id="resetFiltersBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        Reset
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation Bar -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex justify-between items-center py-4">
                <div class="text-2xl font-bold text-primary-600">Health Locker</div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="../user/logout.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 font-medium">Log Out</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto mt-10 p-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
            
            <div class="flex items-center mb-4 md:mb-0">
                <button onclick="history.back()" class="text-gray-500 hover:text-primary-600 mr-4 flex items-center">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </button>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Medical Records</h1>
                    <p class="text-gray-600 text-lg">For <?php echo htmlspecialchars($member_name); ?></p>
                </div>
            </div>
            
           <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            
    <?php if (isset($_GET['search_query']) || isset($_GET['record_type_filter']) || isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
        <a href="view_records.php?member_id=<?php echo htmlspecialchars($member_id); ?>" 
           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 flex items-center justify-center">
            <i class="fas fa-times mr-2"></i> Clear Filters
        </a>
    <?php endif; ?>
    <button id="openModalBtn" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 flex items-center justify-center">
        <i class="fas fa-filter mr-2"></i> Filter Records
    </button>
    <a href="upload_record.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="bg-primary-600 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i> Upload Record
    </a>
    <a href="../remainders/add_reminder.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 flex items-center justify-center">
        <i class="fas fa-bell mr-2"></i> Set Reminder
    </a>
    <div class="relative inline-block text-left">
        <select id="globalLanguageSelector" class="block w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="en">English</option>
            <option value="mr">Marathi</option>
            <option value="hi">Hindi</option>
        </select>
    </div>
</div>
        </div>
<!-- After the heading section -->
<?php if (isset($_GET['search_query']) || isset($_GET['record_type_filter']) || isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
    <div class="mb-6 p-4 bg-primary-50 text-primary-800 rounded-lg">
        <p class="font-semibold text-lg mb-2">Active Filters:</p>
        <ul class="list-disc list-inside space-y-1">
            <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
                <li>Search: "<?php echo htmlspecialchars($_GET['search_query']); ?>"</li>
            <?php endif; ?>
            <?php if (isset($_GET['record_type_filter']) && !empty($_GET['record_type_filter'])): ?>
                <li>Record Type: <?php echo htmlspecialchars($_GET['record_type_filter']); ?></li>
            <?php endif; ?>
            <?php if (isset($_GET['start_date']) && !empty($_GET['start_date'])): ?>
                <li>From: <?php echo htmlspecialchars(date('M d, Y', strtotime($_GET['start_date']))); ?></li>
            <?php endif; ?>
            <?php if (isset($_GET['end_date']) && !empty($_GET['end_date'])): ?>
                <li>To: <?php echo htmlspecialchars(date('M d, Y', strtotime($_GET['end_date']))); ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
        <!-- Records List -->
        <div class="bg-white shadow-lg overflow-hidden sm:rounded-xl">
            <?php if (isset($records) && !empty($records)): ?>
                <ul class="divide-y divide-gray-200">
                        <?php foreach ($records as $record): ?>
                            <li class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                <div class="px-6 py-5 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <?php
                                        $icon = 'fa-file-medical';
                                        $iconClass = 'text-gray-500';
                                        switch ($record['record_type']) {
                                            case 'Prescription': $icon = 'fa-prescription-bottle-alt'; $iconClass = 'text-green-500'; break;
                                            case 'Lab Report': $icon = 'fa-flask'; $iconClass = 'text-purple-500'; break;
                                            case 'Scan': $icon = 'fa-x-ray'; $iconClass = 'text-yellow-500'; break;
                                            case 'Discharge Summary': $icon = 'fa-file-signature'; $iconClass = 'text-red-500'; break;
                                            case 'Vaccination': $icon = 'fa-syringe'; $iconClass = 'text-indigo-500'; break;
                                        }
                                        ?>
                                        <div class="flex-shrink-0 mr-5">
                                            <i class="fas <?php echo $icon; ?> text-3xl <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-primary-700"><?php echo htmlspecialchars($record['record_type']); ?></div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <span class="inline-flex items-center mr-4">
                                                    <i class="far fa-calendar-alt mr-2 text-gray-400"></i> <?php echo date("M d, Y", strtotime($record['record_date'])); ?>
                                                </span>
                                                <?php if (!empty($record['doctor_name'])): ?>
                                                    <span class="inline-flex items-center mr-4">
                                                        <i class="fas fa-user-md mr-2 text-gray-400"></i> <?php echo htmlspecialchars($record['doctor_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($record['hospital_name'])): ?>
                                                    <span class="inline-flex items-center">
                                                        <i class="fas fa-hospital mr-2 text-gray-400"></i> <?php echo htmlspecialchars($record['hospital_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex space-x-3 items-center">
                                        <a href="view_file.php?record_id=<?php echo $record['id']; ?>&preview=1" target="_blank" class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm font-medium hover:bg-primary-200">View File</a>
                                        
                                        <div class="relative inline-block text-left">
                                        <div class="relative inline-block text-left">
                                            <div class="relative">
                                                <button id="simplifyBtn_<?php echo $record['id']; ?>" class="px-3 py-1 bg-primary-600 text-white rounded-full text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50 flex items-center justify-center" onclick="toggleLanguageDropdown(<?php echo $record['id']; ?>, event)">
                                                    Simplify <i class="fas fa-caret-down ml-1"></i>
                                                </button>
                                                <div id="languageDropdown_<?php echo $record['id']; ?>" class="origin-top-right absolute left-0 mt-2 w-32 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50 hidden" role="menu" aria-orientation="vertical" aria-labelledby="simplifyBtn_<?php echo $record['id']; ?>">
                                                <div class="py-1" role="none">
                                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-100 hover:text-primary-900 transition-colors duration-150 ease-in-out" role="menuitem" data-lang="en" onclick="selectLanguageAndSimplify(<?php echo $record['id']; ?>, 'en', event)">English</a>
                                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-100 hover:text-primary-900 transition-colors duration-150 ease-in-out" role="menuitem" data-lang="mr" onclick="selectLanguageAndSimplify(<?php echo $record['id']; ?>, 'mr', event)">Marathi</a>
                                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-100 hover:text-primary-900 transition-colors duration-150 ease-in-out" role="menuitem" data-lang="hi" onclick="selectLanguageAndSimplify(<?php echo $record['id']; ?>, 'hi', event)">Hindi</a>
                                                </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['record_type']); ?>')" class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium hover:bg-red-200">Delete</button>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center py-20 px-6">
                        <i class="fas fa-folder-open text-6xl text-gray-300 mb-5"></i>
                        <h3 class="text-xl font-semibold text-gray-800">No Records Found</h3>
                        <p class="text-gray-500 mt-2 max-w-md mx-auto">No medical records have been uploaded for this family member yet. Click the "Upload Record" button to add one.</p>
                    </div>
                <?php endif; ?>
            </div>
    </div>
    <!-- View Record Modal -->
    <div id="viewRecordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-xl font-semibold" id="modalRecordTitle">Record Details</h3>
                <button id="closeViewModalBtn" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <!-- Modal content goes here -->
            </div>
            <div class="flex justify-end p-4 border-t space-x-3">
                <button id="downloadRecordBtn" class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700"><i class="fas fa-download mr-2"></i> Download</button>
                <button id="closeModalFooterBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>
<!-- Gemini Modal -->
<div id="geminiModal" class="fixed inset-0 bg-gray-800 bg-opacity-0 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4" style="pointer-events: none;">
    <div id="geminiModalContent" class="relative w-full max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex justify-between items-center px-6 py-4 bg-primary-600 text-white">
                <h3 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-file-medical-alt mr-3"></i>
                    <span id="modalTitleText">Simplified Medical Report</span>
                </h3>
                <button id="closeGeminiModal" class="text-2xl leading-none hover:text-primary-200 transition-colors duration-200">&times;</button>
            </div>
            
            <div class="p-8 bg-gray-50" style="max-height: 70vh; overflow-y: auto;">
                <!-- Loading state -->
                <div id="loadingState" class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-4xl text-primary-500"></i>
                    <p id="loadingText" class="mt-4 text-lg text-gray-600">Simplifying your report... Please wait.</p>
                </div>

                <!-- Error state -->
                <div id="errorState" class="hidden text-center py-10">
                     <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                     <p id="errorMessage" class="mt-4 text-lg text-red-700 bg-red-50 p-4 rounded-md"></p>
                </div>

                <!-- Content state -->
                <div id="contentState" class="hidden space-y-8">
                    <!-- Summary Section -->
                    <div class="content-item bg-white p-6 rounded-lg shadow-md border-l-4 border-primary-500">
                        <h4 class="text-xl font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-clipboard-list mr-3 text-primary-500"></i>
                            <span id="summaryLabel">Summary</span>
                        </h4>
                        <p id="summaryContent" class="text-gray-700 leading-relaxed"></p>
                    </div>

                    <!-- Key Points Section -->
                    <div id="keyPointsSection" class="content-item bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
                        <h4 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-check-circle mr-3 text-green-500"></i>
                            <span id="keyPointsLabel">Key Points</span>
                        </h4>
                        <ul id="keyPointsList" class="space-y-3 list-inside">
                        </ul>
                    </div>

                    <!-- Terms Explained Section -->
                    <div id="termsExplainedSection" class="content-item bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500">
                        <h4 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-book-medical mr-3 text-yellow-500"></i>
                            <span id="termsExplainedLabel">Medical Terms Explained</span>
                        </h4>
                        <dl id="termsList" class="space-y-4">
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewModal = document.getElementById('viewRecordModal');
    const filterModal = document.getElementById('filterModal');
    const geminiModal = document.getElementById('geminiModal');

    // Generic function to open a modal
    window.openModal = (modal) => modal.classList.remove('hidden');
    // Generic function to close all modals
    const closeAllModals = () => {
        document.querySelectorAll('.fixed.inset-0').forEach(modal => modal.classList.add('hidden'));
        geminiModal.style.display = 'none'; // Since its visibility is controlled by display property
    };

    // Setup close buttons for all modals
    document.querySelectorAll('#closeViewModalBtn, #closeModalFooterBtn, #closeModalBtn, #closeGeminiModal').forEach(btn => {
        if(btn) btn.addEventListener('click', closeAllModals);
    });

    // Close modals on outside click
    window.addEventListener('click', (event) => {
        if (event.target === viewModal || event.target === filterModal || event.target === geminiModal) {
            closeAllModals();
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAllModals();
    });

    // Filter Modal specific logic
    const openFilterBtn = document.getElementById('openModalBtn');
    if(openFilterBtn) openFilterBtn.addEventListener('click', () => filterModal.classList.remove('hidden'));
    const resetFilterBtn = document.getElementById('resetFiltersBtn');
    if(resetFilterBtn) resetFilterBtn.addEventListener('click', () => {
        document.getElementById('search_query').value = '';
        document.getElementById('record_type_filter').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
    });

    // Language dropdown logic
    window.toggleLanguageDropdown = function(recordId, event) {
        event.stopPropagation(); // Prevent the document click listener from closing it immediately
        const dropdown = document.getElementById(`languageDropdown_${recordId}`);
        dropdown.classList.toggle('hidden');
        // Close other dropdowns
        document.querySelectorAll('[id^="languageDropdown_"]').forEach(otherDropdown => {
            if (otherDropdown.id !== dropdown.id) {
                otherDropdown.classList.add('hidden');
            }
        });
    };

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('[id^="languageDropdown_"]').forEach(dropdown => {
            if (!dropdown.contains(event.target) && !event.target.matches('[id^="simplifyBtn_"]')) {
                dropdown.classList.add('hidden');
            }
        });
    });

    // Translations for UI labels
    const translations = {
        en: {
            modalTitle: 'Simplified Medical Report',
            loading: 'Simplifying your report... Please wait.',
            error: 'Error',
            summary: 'Summary',
            keyPoints: 'Key Points',
            termsExplained: 'Medical Terms Explained'
        },
        mr: {
            modalTitle: 'सरलीकृत वैद्यकीय अहवाल',
            loading: 'तुमचा अहवाल सरल करत आहे... कृपया प्रतीक्षा करा.',
            error: 'त्रुटी',
            summary: 'सारांश',
            keyPoints: 'मुख्य मुद्दे',
            termsExplained: 'वैद्यकीय संज्ञा स्पष्टीकरण'
        },
        hi: {
            modalTitle: 'सरलीकृत चिकित्सा रिपोर्ट',
            loading: 'आपकी रिपोर्ट को सरल बना रहे हैं... कृपया प्रतीक्षा करें।',
            error: 'त्रुटि',
            summary: 'सारांश',
            keyPoints: 'मुख्य बिंदु',
            termsExplained: 'चिकित्सा शब्दों की व्याख्या'
        }
    };

    // Track ongoing requests to prevent duplicates
    const ongoingRequests = new Set();

    // New function to handle language selection and then simplify
    window.selectLanguageAndSimplify = function(recordId, language, event) {
        event.preventDefault(); // Prevent default link behavior
        event.stopPropagation(); // Stop propagation to prevent immediate closing by document click

        // Disable the button to prevent multiple clicks
        const simplifyBtn = document.getElementById(`simplifyBtn_${recordId}`);
        if (simplifyBtn) {
            simplifyBtn.disabled = true;
            simplifyBtn.classList.add('opacity-50', 'cursor-not-allowed');
            simplifyBtn.dataset.originalText = simplifyBtn.innerHTML;
            simplifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';
        }

        // Close the dropdown immediately
        const dropdown = document.getElementById(`languageDropdown_${recordId}`);
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
        
        // Then call the main simplification function
        getSimplifiedReport(recordId, language, simplifyBtn);
    };

    // Gemini Modal logic
    window.getSimplifiedReport = async function(recordId, languageFromDropdown, simplifyBtn) {
        // Create a unique key for this request
        const requestKey = `${recordId}_${languageFromDropdown || 'default'}`;
        
        // Check if this request is already in progress
        if (ongoingRequests.has(requestKey)) {
            console.log('Request already in progress, ignoring duplicate');
            return;
        }
        
        // Mark this request as ongoing
        ongoingRequests.add(requestKey);
        const geminiModal = document.getElementById('geminiModal');
        const modalContent = document.getElementById('geminiModalContent');
        geminiModal.classList.remove('hidden');
        geminiModal.style.pointerEvents = 'auto'; // Enable pointer events for the modal background

        // Determine the language: prioritize dropdown selection, then global selector, then default to English
        const globalLanguageSelector = document.getElementById('globalLanguageSelector');
        const selectedLanguage = languageFromDropdown || (globalLanguageSelector ? globalLanguageSelector.value : 'en');
        
        const language = selectedLanguage;

        // Update UI labels based on selected language
        const labels = translations[language] || translations['en'];
        document.getElementById('modalTitleText').textContent = labels.modalTitle;
        document.getElementById('loadingText').textContent = labels.loading;
        document.getElementById('summaryLabel').textContent = labels.summary;
        document.getElementById('keyPointsLabel').textContent = labels.keyPoints;
        document.getElementById('termsExplainedLabel').textContent = labels.termsExplained;

        // Get all the state divs
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const contentState = document.getElementById('contentState');
        const errorMessage = document.getElementById('errorMessage');

        // Reset to loading state
        loadingState.classList.remove('hidden');
        errorState.classList.add('hidden');
        contentState.classList.add('hidden');

        // Animate modal in
        anime({
            targets: geminiModal,
            backgroundColor: 'rgba(31, 41, 55, 0.6)',
            duration: 300,
            easing: 'easeOutQuad'
        });
        anime({
            targets: modalContent,
            translateY: ['-30px', '0px'],
            opacity: [0, 1],
            duration: 400,
            easing: 'easeOutCubic'
        });

        try {
            const response = await fetch(`simplify_report.php?record_id=${recordId}&language=${language}`);
            const data = await response.json();

            if (data.status === 'success' && data.simplified_data) {
                const { summary, key_points, terms_explained } = data.simplified_data;

                // Populate Summary
                document.getElementById('summaryContent').textContent = summary;

                // Populate Key Points
                const keyPointsList = document.getElementById('keyPointsList');
                keyPointsList.innerHTML = '';
                if (key_points && key_points.length > 0) {
                    key_points.forEach(point => {
                        const li = document.createElement('li');
                        li.className = 'flex items-start';
                        li.innerHTML = `<i class="fas fa-angle-right text-green-500 mt-1 mr-3"></i><span class="text-gray-600">${point}</span>`;
                        keyPointsList.appendChild(li);
                    });
                    document.getElementById('keyPointsSection').classList.remove('hidden');
                } else {
                    document.getElementById('keyPointsSection').classList.add('hidden');
                }

                // Populate Terms Explained
                const termsList = document.getElementById('termsList');
                termsList.innerHTML = '';
                if (terms_explained && Object.keys(terms_explained).length > 0) {
                    for (const term in terms_explained) {
                        const div = document.createElement('div');
                        div.innerHTML = `
                            <dt class="font-semibold text-gray-700">${term}</dt>
                            <dd class="text-gray-600 ml-4">${terms_explained[term]}</dd>
                        `;
                        termsList.appendChild(div);
                    }
                    document.getElementById('termsExplainedSection').classList.remove('hidden');
                } else {
                    document.getElementById('termsExplainedSection').classList.add('hidden');
                }
                loadingState.classList.add('hidden');
                contentState.classList.remove('hidden');
                anime({
                    targets: '.content-item',
                    translateY: ['20px', '0px'],
                    opacity: [0, 1],
                    delay: anime.stagger(100),
                    easing: 'easeOutCubic'
                });
            } else {
                errorMessage.textContent = data.message || 'An unknown error occurred.';
                loadingState.classList.add('hidden');
                errorState.classList.remove('hidden');
            }
        } catch (error) {
            errorMessage.textContent = 'A network or server error occurred. Please try again later.';
            loadingState.classList.add('hidden');
            errorState.classList.remove('hidden');
        } finally {
            ongoingRequests.delete(requestKey);
            // Re-enable the button after request completes
            if (simplifyBtn) {
                simplifyBtn.disabled = false;
                simplifyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                if (simplifyBtn.dataset.originalText) {
                    simplifyBtn.innerHTML = simplifyBtn.dataset.originalText;
                }
            }
        }
    };

    // Override close function to add animation and reset modal/button state
    const originalCloseAllModals = window.closeAllModals;
    window.closeAllModals = () => {
        const geminiModal = document.getElementById('geminiModal');
        const modalContent = document.getElementById('geminiModalContent');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');

        // Reset modal content state
        document.getElementById('summaryContent').textContent = '';
        document.getElementById('keyPointsList').innerHTML = '';
        document.getElementById('termsList').innerHTML = '';
        document.getElementById('keyPointsSection').classList.add('hidden');
        document.getElementById('termsExplainedSection').classList.add('hidden');
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('contentState').classList.add('hidden');

        // Re-enable all simplify buttons
        document.querySelectorAll('[id^="simplifyBtn_"]').forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        });

        // Close Gemini modal with animation
        if (!geminiModal.classList.contains('hidden')) {
            anime({
                targets: modalContent,
                translateY: '30px',
                opacity: 0,
                duration: 300,
                easing: 'easeInCubic',
                complete: () => {
                    geminiModal.classList.add('hidden');
                    geminiModal.style.pointerEvents = 'none';
                }
            });
            anime({
                targets: geminiModal,
                backgroundColor: 'rgba(31, 41, 55, 0)',
                duration: 300,
                easing: 'easeInQuad'
            });
        }

        // Close other modals directly
        document.querySelectorAll('.fixed.inset-0').forEach(modal => {
            if (modal !== geminiModal) {
                modal.classList.add('hidden');
            }
        });
    };

    // Delete Confirmation Logic
    window.confirmDelete = function(recordId, recordType) {
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const deleteRecordIdInput = document.getElementById('deleteRecordId');
        const deleteRecordTypeSpan = document.getElementById('deleteRecordType');
        const memberIdInput = document.getElementById('deleteMemberId');

        deleteRecordIdInput.value = recordId;
        deleteRecordTypeSpan.textContent = recordType;
        memberIdInput.value = <?php echo json_encode($member_id); ?>; // Pass member_id to the modal

        deleteConfirmModal.classList.remove('hidden');
    };

    const deleteRecordForm = document.getElementById('deleteRecordForm');
    if (deleteRecordForm) {
        deleteRecordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const recordId = formData.get('record_id');
            const memberId = formData.get('member_id');

            try {
                const response = await fetch('delete_record.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    alert(data.message);
                    window.location.href = data.redirect_url; // Redirect to refresh the list
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('An error occurred while trying to delete the record.');
                console.error('Delete error:', error);
            } finally {
                closeAllModals();
            }
        });
    }
});
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm">
        <div class="flex justify-between items-center p-5 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Confirm Deletion</h3>
            <button onclick="closeAllModals()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            <p class="text-gray-700 mb-4">Are you sure you want to delete the record "<span id="deleteRecordType" class="font-semibold"></span>"? This action cannot be undone.</p>
            <form id="deleteRecordForm" method="POST" action="delete_record.php">
                <input type="hidden" name="record_id" id="deleteRecordId">
                <input type="hidden" name="member_id" id="deleteMemberId">
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="closeAllModals()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
