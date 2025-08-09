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
    $stmt = $pdo->prepare("SELECT mr.file_path FROM medical_records mr 
                          JOIN family_members fm ON mr.member_id = fm.id
                          WHERE mr.id = ? AND fm.user_id = ?");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record || !file_exists($record['file_path'])) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $file_path = $record['file_path'];
    $file_name = basename($file_path);
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$file_name.'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}