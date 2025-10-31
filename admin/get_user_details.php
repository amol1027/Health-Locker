<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.created_at,
               COUNT(DISTINCT fm.id) as family_members_count,
               COUNT(DISTINCT mr.id) as medical_records_count,
               COUNT(DISTINCT r.id) as reminders_count
        FROM users u
        LEFT JOIN family_members fm ON u.id = fm.user_id
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
        LEFT JOIN reminders r ON u.id = r.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Fetch family members details
    $stmt = $pdo->prepare("
        SELECT fm.id, fm.first_name, fm.last_name, fm.relation, fm.blood_type, fm.date_of_birth,
               COUNT(mr.id) as records_count
        FROM family_members fm
        LEFT JOIN medical_records mr ON fm.id = mr.member_id
        WHERE fm.user_id = ?
        GROUP BY fm.id
        ORDER BY fm.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'family_members' => $family_members
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
