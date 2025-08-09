<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if (!isset($_GET['record_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$record_id = $_GET['record_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify the record belongs to the user
    $stmt = $pdo->prepare("SELECT mr.file_path, mr.fileExt FROM medical_records mr 
                          JOIN family_members fm ON mr.member_id = fm.id
                          WHERE mr.id = ? AND fm.user_id = ?");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $file_path = $record['file_path'];
    $file_ext = strtolower($record['fileExt']);

    // Verify file exists and is readable
    if (!file_exists($file_path) || !is_readable($file_path)) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    // Clean the file extension
    $file_ext = preg_replace('/[^a-z0-9]/', '', $file_ext);

    // Set appropriate headers based on file type
    switch($file_ext) {
        case 'pdf':
            header('Content-Type: application/pdf');
            if (isset($_GET['preview'])) {
                header('Content-Disposition: inline; filename="'.basename($file_path).'"');
            } else {
                header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            }
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            header('Content-Disposition: inline; filename="'.basename($file_path).'"');
            break;
        case 'png':
            header('Content-Type: image/png');
            header('Content-Disposition: inline; filename="'.basename($file_path).'"');
            break;
        default:
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
    }

    // Security headers
    header('X-Content-Type-Options: nosniff');
    
    // Output the file
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}