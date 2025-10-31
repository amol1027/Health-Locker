<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'])) {
    $member_id = $_POST['member_id'];

    try {
        // First, delete all records associated with this family member
        $stmt_delete_records = $pdo->prepare("DELETE FROM medical_records WHERE member_id = ?");
        $stmt_delete_records->execute([$member_id]);

        // Then, delete all reminders associated with this family member
        $stmt_delete_reminders = $pdo->prepare("DELETE FROM reminders WHERE member_id = ?");
        $stmt_delete_reminders->execute([$member_id]);

        // Finally, delete the family member
        $stmt_delete_member = $pdo->prepare("DELETE FROM family_members WHERE id = ? AND user_id = ?");
        
        if ($stmt_delete_member->execute([$member_id, $user_id])) {
            $message = 'Family member and all associated data deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete family member. Please try again.';
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = 'Invalid request.';
    $message_type = 'error';
}

// Redirect back to dashboard with a message
$_SESSION['delete_message'] = $message;
$_SESSION['delete_message_type'] = $message_type;
header('Location: dashboard.php');
exit;
?>
