<?php
// This script should be executed via a cron job, not a web request
require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure to run 'composer require phpmailer/phpmailer' in your project root
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Select reminders that are due within the next 5 minutes and haven't been sent
    $stmt = $pdo->prepare("
        SELECT r.id, r.reminder_text, u.email, u.name
        FROM reminders r
        JOIN users u ON r.user_id = u.id
        WHERE r.reminder_datetime <= NOW() AND r.is_sent = FALSE
    ");
    $stmt->execute();
    $due_reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($due_reminders) > 0) {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Sender
        $mail->setFrom('notehub11@gmail.com', 'Health Locker');

        // Prepare the update statement once before the loop
        $updateStmt = $pdo->prepare("UPDATE reminders SET is_sent = TRUE WHERE id = ?");

        foreach ($due_reminders as $reminder) {
            try {
                // Recipient
                $mail->addAddress($reminder['email']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Health Locker Reminder';

                // Create the email body from the template
                ob_start();
                $reminder_text = $reminder['reminder_text'];
                $user_name = $reminder['name'];
                include 'email_template.php';
                $mail->Body = ob_get_clean();
                
                $mail->AltBody = "Hi, you have a reminder: " . $reminder['reminder_text'];

                $mail->send();

                // Execute the prepared statement to mark the reminder as sent
                $updateStmt->execute([$reminder['id']]);

            } catch (Exception $e) {
                // Log email sending errors for each failed email
                file_put_contents('reminder_error.log', date('Y-m-d H:i:s') . " - Mailer Error for reminder ID {$reminder['id']}: " . $mail->ErrorInfo . "\n", FILE_APPEND);
            }
            
            // Clear all addresses and attachments for the next iteration
            $mail->clearAddresses();
            $mail->clearAttachments();
        }
    }

} catch (PDOException $e) {
    // Log database errors
    file_put_contents('reminder_error.log', date('Y-m-d H:i:s') . " - PDO Error: " . $e->getMessage() . "\n", FILE_APPEND);
} catch (Exception $e) {
    // Log other errors (e.g., PHPMailer instantiation)
    file_put_contents('reminder_error.log', date('Y-m-d H:i:s') . " - General Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
