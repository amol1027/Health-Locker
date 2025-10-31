<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($member_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
    exit;
}

try {
    // Fetch member details
    $stmt = $pdo->prepare("
        SELECT fm.*, u.name as user_name, u.email as user_email,
               COUNT(mr.id) as records_count
        FROM family_members fm
        JOIN users u ON fm.user_id = u.id
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
        WHERE fm.id = ?
        GROUP BY fm.id
    ");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Member not found']);
        exit;
    }
    
    // Fetch recent medical records (last 5)
    $stmt = $pdo->prepare("
        SELECT id, record_type, record_date, doctor_name, hospital_name, created_at
        FROM medical_records
        WHERE member_id = ?
        ORDER BY record_date DESC, created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$member_id]);
    $recent_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'member' => $member,
        'recent_records' => $recent_records
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
