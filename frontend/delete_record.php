<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $member_id = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT); // Assuming member_id is also passed for redirection
    $user_id = $_SESSION['user_id'];

    if (!$record_id || !$member_id) {
        $response['message'] = 'Invalid record ID or member ID.';
        echo json_encode($response);
        exit;
    }

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // 1. Get the file path associated with the record
        $stmt = $pdo->prepare("SELECT file_path FROM medical_records WHERE id = ? AND member_id IN (SELECT id FROM family_members WHERE user_id = ?)");
        $stmt->execute([$record_id, $user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            $response['message'] = 'Record not found or you do not have permission to delete it.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        $file_path = $record['file_path'];

        // 2. Delete the record from the database
        $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ? AND member_id IN (SELECT id FROM family_members WHERE user_id = ?)");
        $stmt->execute([$record_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            // 3. Delete the actual file from the server
            if (!empty($file_path) && file_exists($file_path)) {
                if (unlink($file_path)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Record and associated file deleted successfully.';
                    $response['redirect_url'] = 'view_records.php?member_id=' . $member_id;
                    $pdo->commit();
                } else {
                    $response['message'] = 'Record deleted from database, but failed to delete the file from storage.';
                    $response['redirect_url'] = 'view_records.php?member_id=' . $member_id; // Still redirect even if file deletion fails
                    $pdo->commit(); // Commit DB changes even if file deletion fails
                }
            } else {
                $response['status'] = 'success';
                $response['message'] = 'Record deleted successfully (no associated file or file not found).';
                $response['redirect_url'] = 'view_records.php?member_id=' . $member_id;
                $pdo->commit();
            }
        } else {
            $response['message'] = 'Failed to delete record or record not found.';
            $pdo->rollBack();
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'Server error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
