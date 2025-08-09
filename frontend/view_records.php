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
    $stmt = $pdo->prepare("SELECT id, record_type, record_date, doctor_name, hospital_name, fileExt FROM medical_records WHERE member_id = ? ORDER BY record_date DESC");
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
$sql = "SELECT id, record_type, record_date, doctor_name, hospital_name, fileExt 
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                   class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>
    <div class="mb-4">
        <label for="record_type_filter" class="block text-gray-700 text-sm font-medium mb-2">Record Type</label>
        <select id="record_type_filter" name="record_type_filter" 
                class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                       class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-xs text-gray-500 mb-1">To</label>
                <input type="date" id="end_date" name="end_date" 
                       value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" 
                       class="w-full py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" id="resetFiltersBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Reset
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-blue-600">HealthLocker</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="../user/logout.php" class="text-gray-600 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
            
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900">Medical Records</h1>
                <p class="text-gray-600">For <?php echo htmlspecialchars($member_name); ?></p>
            </div>
            
           <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            
    <?php if (isset($_GET['search_query']) || isset($_GET['record_type_filter']) || isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
        <a href="view_records.php?member_id=<?php echo htmlspecialchars($member_id); ?>" 
           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md shadow-sm text-sm font-medium hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center">
            <i class="fas fa-times mr-2"></i> Clear Filters
        </a>
    <?php endif; ?>
    <button id="openModalBtn" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center">
        <i class="fas fa-filter mr-2"></i> Filter Records
    </button>
    <a href="upload_record.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-sm text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i> Upload Record
    </a>
    <a href="../remainders/add_reminder.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="bg-green-600 text-white px-4 py-2 rounded-md shadow-sm text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 flex items-center justify-center">
        <i class="fas fa-bell mr-2"></i> Set Reminder
    </a>
</div>
        </div>
<!-- After the heading section -->
<?php if (isset($_GET['search_query']) || isset($_GET['record_type_filter']) || isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
    <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded-md">
        <p class="font-medium">Active Filters:</p>
        <ul class="list-disc list-inside">
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
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <?php if (isset($records) && !empty($records)): ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($records as $record): ?>
                        <li class="hover:bg-gray-50 transition duration-150 ease-in-out">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <?php 
                                            $iconClass = 'text-blue-500';
                                            $icon = 'fa-file-medical'; // Default icon
                                            
                                            switch($record['record_type']) {
                                                case 'Prescription':
                                                    $icon = 'fa-prescription-bottle-alt';
                                                    $iconClass = 'text-green-500';
                                                    break;
                                                case 'Lab Report':
                                                    $icon = 'fa-flask';
                                                    $iconClass = 'text-purple-500';
                                                    break;
                                                case 'Scan':
                                                    $icon = 'fa-x-ray';
                                                    $iconClass = 'text-yellow-500';
                                                    break;
                                                case 'Discharge Summary':
                                                    $icon = 'fa-file-signature';
                                                    $iconClass = 'text-red-500';
                                                    break;
                                                case 'Vaccination':
                                                    $icon = 'fa-syringe';
                                                    $iconClass = 'text-indigo-500';
                                                    break;
                                            }
                                        ?>
                                        <div class="flex-shrink-0 mr-4">
                                            <i class="fas <?php echo $icon; ?> text-2xl <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-blue-600"><?php echo htmlspecialchars($record['record_type']); ?></div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <span class="inline-flex items-center mr-3">
                                                    <i class="far fa-calendar-alt mr-1"></i> <?php echo date("M d, Y", strtotime($record['record_date'])); ?>
                                                </span>
                                                <?php if (!empty($record['doctor_name'])): ?>
                                                    <span class="inline-flex items-center mr-3">
                                                        <i class="fas fa-user-md mr-1"></i> <?php echo htmlspecialchars($record['doctor_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($record['hospital_name'])): ?>
                                                    <span class="inline-flex items-center">
                                                        <i class="fas fa-hospital mr-1"></i> <?php echo htmlspecialchars($record['hospital_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <a href="#" onclick="openViewRecordModal(<?php echo $record['id']; ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <i class="fas fa-eye mr-1"></i> View
</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-file-medical text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">No medical records found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by uploading a new medical record.</p>
                    <div class="mt-6">
                        <a href="upload_record.php?member_id=<?php echo htmlspecialchars($member_id); ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Upload Record
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Record Modal -->
<div id="viewRecordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-xl font-semibold" id="modalRecordTitle">Record Details</h3>
            <button id="closeViewModalBtn" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900">Record Information</h4>
                    <div class="mt-4 space-y-3">
                        <p><span class="text-gray-600">Type:</span> <span id="modalRecordType"></span></p>
                        <p><span class="text-gray-600">Date:</span> <span id="modalRecordDate"></span></p>
                        <p><span class="text-gray-600">Doctor:</span> <span id="modalRecordDoctor"></span></p>
                        <p><span class="text-gray-600">Hospital:</span> <span id="modalRecordHospital"></span></p>
                    </div>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Document Preview</h4>
                    <div class="mt-4 border-2 border-dashed border-gray-300 rounded-lg p-4 flex items-center justify-center min-h-40">
                        <iframe id="recordPreview" class="w-full h-64 hidden"></iframe>
                        <div id="noPreview" class="text-center text-gray-500">
                            <i class="fas fa-file-alt text-4xl mb-2"></i>
                            <p>Preview not available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end p-4 border-t space-x-3">
            <button id="downloadRecordBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-download mr-2"></i> Download
            </button>
            <button id="closeModalFooterBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
    Close
</button>
        </div>
    </div>
</div>
<script>
// View Record Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const viewModal = document.getElementById('viewRecordModal');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    const downloadRecordBtn = document.getElementById('downloadRecordBtn');
    const filterModal = document.getElementById('filterModal');
    
    // Function to open view modal with record data
    window.openViewRecordModal = function(recordId) {
        console.log('Attempting to open record:', recordId);
        
        // Show loading state
        document.getElementById('modalRecordTitle').textContent = 'Loading...';
        document.getElementById('modalRecordType').textContent = '';
        document.getElementById('modalRecordDate').textContent = '';
        document.getElementById('modalRecordDoctor').textContent = '';
        document.getElementById('modalRecordHospital').textContent = '';
        document.getElementById('recordPreview').classList.add('hidden');
        document.getElementById('noPreview').classList.remove('hidden');
        
        // Show modal immediately while loading data
        viewModal.classList.remove('hidden');
        
        // Fetch record details via AJAX
        fetch(`get_record_details.php?record_id=${recordId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Record data received:', data);
                
                if (!data || data.error) {
                    throw new Error(data?.error || 'Invalid data received');
                }
                
                // Populate modal with data
                document.getElementById('modalRecordTitle').textContent = data.record_type || 'Record Details';
                document.getElementById('modalRecordType').textContent = data.record_type || 'Not specified';
                document.getElementById('modalRecordDate').textContent = data.record_date ? 
                    new Date(data.record_date).toLocaleDateString() : 'Unknown date';
                document.getElementById('modalRecordDoctor').textContent = data.doctor_name || 'N/A';
                document.getElementById('modalRecordHospital').textContent = data.hospital_name || 'N/A';
                
                // Set download link
                if (downloadRecordBtn) {
                    downloadRecordBtn.onclick = () => {
                        window.location.href = `download_record.php?record_id=${recordId}`;
                    };
                }
                
                // Show preview if available
                const preview = document.getElementById('recordPreview');
                const noPreview = document.getElementById('noPreview');
                
// Replace the preview handling code with this:
if (data.file_type && data.file_type.toLowerCase() === 'pdf') {
    // For PDFs, use an iframe with proper URL parameters
    preview.src = `view_file.php?record_id=${recordId}&preview=1#toolbar=0&navpanes=0&scrollbar=0`;
    preview.classList.remove('hidden');
    noPreview.classList.add('hidden');

} else if (data.file_type && ['jpg', 'jpeg', 'png'].includes(data.file_type.toLowerCase())) {
    // For images, create an img element dynamically
    const img = document.createElement('img');
    img.src = `view_file.php?record_id=${recordId}&preview=1`;
    img.className = 'max-h-64 max-w-full object-contain';
    
    // Handle image load errors
    img.onerror = function () {
        this.parentElement.innerHTML = `
            <i class="fas fa-file-alt text-4xl mb-2"></i>
            <p>Could not load image preview</p>
        `;
    };

    noPreview.innerHTML = `
        <div class="flex flex-col items-center"></div>
        <p class="mt-2 text-sm">Image Preview</p>
    `;

    // Insert image into the first div
    noPreview.querySelector('div').appendChild(img);

    noPreview.classList.remove('hidden');
    preview.classList.add('hidden');

} else {
    // For unsupported types
    preview.classList.add('hidden');
    noPreview.classList.remove('hidden');
    noPreview.innerHTML = `
        <i class="fas fa-file-alt text-4xl mb-2"></i>
        <p>Preview not available for ${data.file_type || 'this file type'}</p>
    `;
}

            })
            .catch(error => {
                console.error('Error loading record:', error);
                document.getElementById('modalRecordTitle').textContent = 'Error Loading Record';
                alert('Failed to load record details. Please try again.');
            });
    };

    // Close view modal
    if (closeViewModalBtn) {
        closeViewModalBtn.addEventListener('click', () => {
            viewModal.classList.add('hidden');
        });
    }
const closeModalFooterBtn = document.getElementById('closeModalFooterBtn');
if (closeModalFooterBtn) {
    closeModalFooterBtn.addEventListener('click', () => {
        viewModal.classList.add('hidden');
    });
}
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === viewModal) {
            viewModal.classList.add('hidden');
        }
        if (event.target === filterModal) {
            filterModal.classList.add('hidden');
        }
    });

    // Filter Modal Functionality
    const openFilterBtn = document.getElementById('openModalBtn');
    const closeFilterBtn = document.getElementById('closeModalBtn');
    const resetFilterBtn = document.getElementById('resetFiltersBtn');

    if (openFilterBtn) {
        openFilterBtn.addEventListener('click', () => {
            filterModal.classList.remove('hidden');
        });
    }

    if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', () => {
            filterModal.classList.add('hidden');
        });
    }

    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            document.getElementById('search_query').value = '';
            document.getElementById('record_type_filter').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
        });
    }

    // Escape key to close modals
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            viewModal.classList.add('hidden');
            filterModal.classList.add('hidden');
        }
    });
});
</script>
</body>
</html>
