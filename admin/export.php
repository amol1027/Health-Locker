<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Function to beautify column names
function beautifyColumnName($name) {
    // Convert snake_case to Title Case
    $name = str_replace('_', ' ', $name);
    $name = ucwords($name);
    
    // Special cases
    $replacements = [
        'Id' => 'ID',
        'Fm' => 'Family Member',
        'Mr' => 'Medical Record',
        'Datetime' => 'Date/Time',
        'Createdat' => 'Created At',
        'Updatedat' => 'Updated At',
        'Al' => 'Activity Log'
    ];
    
    foreach ($replacements as $search => $replace) {
        $name = str_replace($search, $replace, $name);
    }
    
    return $name;
}

// Function to export CSV with better formatting
function exportCSV($data, $filename, $title = '') {
    // Set headers for Excel compatibility with proper encoding
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel to recognize encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($data)) {
        // Add title row if provided
        if (!empty($title)) {
            fputcsv($output, [$title]);
            fputcsv($output, ['Generated on: ' . date('F d, Y \a\t h:i A')]);
            fputcsv($output, []); // Empty row for spacing
        }
        
        // Get headers from first row
        $headers = array_keys($data[0]);
        
        // Check if headers are already beautified (contain spaces)
        $needsBeautify = true;
        foreach ($headers as $header) {
            if (strpos($header, ' ') !== false) {
                $needsBeautify = false;
                break;
            }
        }
        
        // Beautify headers only if needed
        $beautifiedHeaders = $needsBeautify ? array_map('beautifyColumnName', $headers) : $headers;
        fputcsv($output, $beautifiedHeaders);
        
        // Write data rows
        foreach ($data as $row) {
            // Format specific columns
            foreach ($row as $key => $value) {
                // Ensure string values are properly formatted
                if (is_string($value)) {
                    $value = trim($value);
                }
                
                // Format dates that aren't already formatted
                if ((stripos($key, 'At') !== false || stripos($key, 'Date') !== false || stripos($key, 'Time') !== false) 
                    && !empty($value) 
                    && $value !== '0000-00-00 00:00:00'
                    && strlen($value) > 10) {
                    // Check if it's already in datetime format (YYYY-MM-DD HH:MM:SS)
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                        $row[$key] = date('M d, Y h:i A', strtotime($value));
                    } else {
                        $row[$key] = $value;
                    }
                }
                // Format boolean/status values
                else if (stripos($key, 'Status') !== false || stripos($key, 'is_') === 0) {
                    if ($value == 1 || strtolower($value) === 'yes') {
                        $row[$key] = 'Yes';
                    } else if ($value == 0 || strtolower($value) === 'no') {
                        $row[$key] = 'No';
                    } else {
                        $row[$key] = $value;
                    }
                }
                // Clean null values
                else if (is_null($value) || $value === '') {
                    $row[$key] = '-';
                }
                else {
                    $row[$key] = $value;
                }
            }
            fputcsv($output, $row);
        }
        
        // Add summary footer
        fputcsv($output, []); // Empty row
        fputcsv($output, ['Total Records: ' . count($data)]);
    } else {
        fputcsv($output, ['No data available for export']);
    }
    
    fclose($output);
    exit;
}

try {
    switch ($type) {
        case 'users':
            $stmt = $pdo->query("
                SELECT u.id as 'ID', 
                       TRIM(u.name) as 'Name', 
                       TRIM(u.email) as 'Email', 
                       DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
                       COUNT(DISTINCT fm.id) as 'Family Members',
                       COUNT(DISTINCT mr.id) as 'Medical Records'
                FROM users u
                LEFT JOIN family_members fm ON u.id = fm.user_id
                LEFT JOIN medical_records mr ON fm.id = mr.member_id
                GROUP BY u.id, u.name, u.email, u.created_at
                ORDER BY u.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            exportCSV($data, 'Health_Locker_Users_' . date('Y-m-d') . '.csv', 'HEALTH LOCKER - USERS REPORT');
            break;
            
        case 'family_members':
            $stmt = $pdo->query("
                SELECT fm.id as 'ID', 
                       TRIM(fm.first_name) as 'First Name', 
                       TRIM(fm.last_name) as 'Last Name', 
                       fm.date_of_birth as 'Date of Birth', 
                       TRIM(fm.relation) as 'Relation',
                       TRIM(fm.blood_type) as 'Blood Type', 
                       TRIM(fm.known_allergies) as 'Known Allergies', 
                       DATE_FORMAT(fm.created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
                       TRIM(u.name) as 'User Name', 
                       TRIM(u.email) as 'User Email'
                FROM family_members fm
                JOIN users u ON fm.user_id = u.id
                ORDER BY fm.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            exportCSV($data, 'Health_Locker_Family_Members_' . date('Y-m-d') . '.csv', 'HEALTH LOCKER - FAMILY MEMBERS REPORT');
            break;
            
        case 'medical_records':
            $stmt = $pdo->query("
                SELECT mr.id as 'ID', 
                       TRIM(mr.record_type) as 'Record Type', 
                       mr.record_date as 'Record Date', 
                       TRIM(mr.doctor_name) as 'Doctor Name', 
                       TRIM(mr.hospital_name) as 'Hospital Name',
                       TRIM(mr.file_type) as 'File Type', 
                       DATE_FORMAT(mr.created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
                       TRIM(CONCAT(fm.first_name, ' ', fm.last_name)) as 'Patient Name', 
                       TRIM(u.name) as 'User Name'
                FROM medical_records mr
                JOIN family_members fm ON mr.member_id = fm.id
                JOIN users u ON fm.user_id = u.id
                ORDER BY mr.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            exportCSV($data, 'Health_Locker_Medical_Records_' . date('Y-m-d') . '.csv', 'HEALTH LOCKER - MEDICAL RECORDS REPORT');
            break;
            
        case 'reminders':
            $stmt = $pdo->query("
                SELECT r.id as 'ID', 
                       TRIM(r.reminder_text) as 'Reminder Text', 
                       DATE_FORMAT(r.reminder_datetime, '%Y-%m-%d %H:%i:%s') as 'Reminder Date/Time', 
                       r.is_sent as 'Sent Status', 
                       DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
                       TRIM(CONCAT(fm.first_name, ' ', fm.last_name)) as 'Patient Name', 
                       TRIM(u.name) as 'User Name'
                FROM reminders r
                JOIN family_members fm ON r.member_id = fm.id
                JOIN users u ON r.user_id = u.id
                ORDER BY r.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            exportCSV($data, 'Health_Locker_Reminders_' . date('Y-m-d') . '.csv', 'HEALTH LOCKER - REMINDERS REPORT');
            break;
            
        case 'activity_logs':
            $stmt = $pdo->query("
                SELECT al.id as 'ID', 
                       TRIM(al.action) as 'Action', 
                       TRIM(al.table_name) as 'Table Name', 
                       al.record_id as 'Record ID', 
                       al.old_values as 'Old Values', 
                       al.new_values as 'New Values', 
                       DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
                       TRIM(a.username) as 'Admin Username', 
                       TRIM(a.full_name) as 'Admin Full Name'
                FROM activity_logs al
                JOIN admins a ON al.admin_id = a.id
                ORDER BY al.created_at DESC
                LIMIT 1000
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            exportCSV($data, 'Health_Locker_Activity_Logs_' . date('Y-m-d') . '.csv', 'HEALTH LOCKER - ACTIVITY LOGS REPORT (Last 1000 Records)');
            break;
            
        case 'analytics':
            // Special handling for analytics - create structured report
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Health_Locker_Analytics_Report_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            // Title
            fputcsv($output, ['HEALTH LOCKER - COMPREHENSIVE ANALYTICS REPORT']);
            fputcsv($output, ['Generated on: ' . date('F d, Y \a\t h:i A')]);
            fputcsv($output, []);
            
            // 1. System Overview
            fputcsv($output, ['=== SYSTEM OVERVIEW ===']);
            fputcsv($output, ['Metric', 'Value']);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            fputcsv($output, ['Total Users', $stmt->fetch()['count']]);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM family_members");
            fputcsv($output, ['Total Family Members', $stmt->fetch()['count']]);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_records");
            fputcsv($output, ['Total Medical Records', $stmt->fetch()['count']]);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reminders");
            fputcsv($output, ['Total Reminders', $stmt->fetch()['count']]);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reminders WHERE is_sent = 1");
            fputcsv($output, ['Reminders Sent', $stmt->fetch()['count']]);
            
            $stmt = $pdo->query("SELECT AVG(member_count) as avg FROM (SELECT user_id, COUNT(*) as member_count FROM family_members GROUP BY user_id) temp");
            $result = $stmt->fetch();
            fputcsv($output, ['Avg Family Members per User', round($result['avg'] ?? 0, 2)]);
            
            $stmt = $pdo->query("SELECT AVG(record_count) as avg FROM (SELECT member_id, COUNT(*) as record_count FROM medical_records GROUP BY member_id) temp");
            $result = $stmt->fetch();
            fputcsv($output, ['Avg Records per Member', round($result['avg'] ?? 0, 2)]);
            
            fputcsv($output, []);
            
            // 2. User Growth (Last 30 Days)
            fputcsv($output, ['=== USER GROWTH (LAST 30 DAYS) ===']);
            fputcsv($output, ['Date', 'New Users']);
            $stmt = $pdo->query("
                SELECT DATE(created_at) as date, COUNT(*) as count
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $totalNew = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                fputcsv($output, [date('M d, Y', strtotime($row['date'])), $row['count']]);
                $totalNew += $row['count'];
            }
            fputcsv($output, ['TOTAL NEW USERS (30 days)', $totalNew]);
            fputcsv($output, []);
            
            // 3. Record Types Distribution
            fputcsv($output, ['=== MEDICAL RECORD TYPES ===']);
            fputcsv($output, ['Record Type', 'Count', 'Percentage']);
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM medical_records WHERE record_type IS NOT NULL AND record_type != ''");
            $totalRecords = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("
                SELECT record_type, COUNT(*) as count
                FROM medical_records
                WHERE record_type IS NOT NULL AND record_type != ''
                GROUP BY record_type
                ORDER BY count DESC
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $percentage = $totalRecords > 0 ? round(($row['count'] / $totalRecords) * 100, 2) : 0;
                fputcsv($output, [$row['record_type'], $row['count'], $percentage . '%']);
            }
            fputcsv($output, []);
            
            // 4. Age Distribution
            fputcsv($output, ['=== AGE DISTRIBUTION ===']);
            fputcsv($output, ['Age Group', 'Count', 'Percentage']);
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM family_members WHERE date_of_birth IS NOT NULL");
            $totalMembers = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("
                SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN '0-17 years'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30 years'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50 years'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN '51-70 years'
                        ELSE '71+ years'
                    END as age_group,
                    COUNT(*) as count
                FROM family_members
                WHERE date_of_birth IS NOT NULL AND date_of_birth <= CURDATE()
                GROUP BY age_group
                ORDER BY 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 1
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN 2
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN 3
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN 4
                        ELSE 5
                    END
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $percentage = $totalMembers > 0 ? round(($row['count'] / $totalMembers) * 100, 2) : 0;
                fputcsv($output, [$row['age_group'], $row['count'], $percentage . '%']);
            }
            fputcsv($output, []);
            
            // 5. Top 10 Active Users
            fputcsv($output, ['=== TOP 10 MOST ACTIVE USERS ===']);
            fputcsv($output, ['Rank', 'User Name', 'Email', 'Family Members', 'Medical Records', 'Total Activity']);
            $stmt = $pdo->query("
                SELECT u.name, u.email,
                       COUNT(DISTINCT fm.id) as family_count,
                       COUNT(mr.id) as record_count
                FROM users u
                LEFT JOIN family_members fm ON u.id = fm.user_id
                LEFT JOIN medical_records mr ON fm.id = mr.member_id
                GROUP BY u.id
                HAVING record_count > 0
                ORDER BY record_count DESC
                LIMIT 10
            ");
            $rank = 1;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $totalActivity = $row['family_count'] + $row['record_count'];
                fputcsv($output, [$rank++, $row['name'], $row['email'], $row['family_count'], $row['record_count'], $totalActivity]);
            }
            fputcsv($output, []);
            
            // 6. Relation Distribution
            fputcsv($output, ['=== FAMILY RELATION DISTRIBUTION ===']);
            fputcsv($output, ['Relation', 'Count']);
            $stmt = $pdo->query("
                SELECT relation, COUNT(*) as count
                FROM family_members
                WHERE relation IS NOT NULL AND relation != '' AND TRIM(relation) != ''
                GROUP BY relation
                ORDER BY count DESC
                LIMIT 15
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                fputcsv($output, [$row['relation'], $row['count']]);
            }
            
            fputcsv($output, []);
            fputcsv($output, ['--- END OF REPORT ---']);
            
            fclose($output);
            exit;
            break;
            
        default:
            die('Invalid export type');
    }
} catch (PDOException $e) {
    die('Export error: ' . $e->getMessage());
}
?>
