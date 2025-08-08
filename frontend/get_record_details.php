<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['record_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$record_id = $_GET['record_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify the record belongs to the user
    $stmt = $pdo->prepare("SELECT mr.id, mr.record_type, mr.record_date, mr.doctor_name, mr.hospital_name, mr.fileExt AS file_type FROM medical_records mr 
                          JOIN family_members fm ON mr.member_id = fm.id
                          WHERE mr.id = ? AND fm.user_id = ?");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        header('Content-Type: application/json');
        echo json_encode($record);
    } else {
        header('HTTP/1.1 404 Not Found');
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
