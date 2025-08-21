<?php
session_start();

require_once '../vendor/autoload.php';
require_once '../config/config.php';

use Gemini\Data\Content;
use Smalot\PdfParser\Parser;



header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if (!isset($_GET['record_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No record ID provided.']);
    exit;
}

$gemini_api_key = GEMINI_API_KEY;



$user_id = $_SESSION['user_id'];
$record_id = $_GET['record_id'];

try {
    // 1. Fetch the file path from the database
    $stmt = $pdo->prepare("
        SELECT mr.file_path, mr.file_type
        FROM medical_records mr
        JOIN family_members fm ON mr.member_id = fm.id
        WHERE mr.id = ? AND fm.user_id = ?
    ");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo json_encode(['status' => 'error', 'message' => 'Record not found or access denied.']);
        exit;
    }

    // The file path from the DB is like '../uploads/health_records/...' which is relative to the frontend dir.
    $filePath = realpath(__DIR__ . '/' . $record['file_path']);

    if ($filePath === false || !file_exists($filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'File not found on the server. Path: ' . htmlspecialchars($record['file_path'])]);
        exit;
    }

    $fileExt = strtolower($record['file_type']);
    $extractedText = '';

    // 2. Extract text based on file type
    if ($fileExt === 'pdf') {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $extractedText = $pdf->getText();
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error processing PDF file: ' . $e->getMessage()]);
            exit;
        }
    } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
        // Use Tesseract for images. Redirect stderr to stdout to capture errors.
        $command = "tesseract " . escapeshellarg($filePath) . " stdout 2>&1";
        $commandOutput = shell_exec($command);

        // Check for Tesseract command execution failure or empty output.
        if ($commandOutput === null || trim($commandOutput) === '' || (stripos($commandOutput, 'error') !== false && strlen($commandOutput) < 250)) {
            $errorMessage = 'Could not extract text from the image. The file might be empty or corrupted.';
            if ($commandOutput) {
                $errorMessage .= ' Server response: ' . htmlspecialchars(trim($commandOutput));
            }
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
            exit;
        }
        $extractedText = $commandOutput;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unsupported file type for simplification.']);
        exit;
    }

    if (trim($extractedText) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Extracted text is empty. The file might not contain any text.']);
        exit;
    }

    // 3. Craft the prompt and send to Gemini API using the PHP client
    $client = Gemini::client($gemini_api_key);

    $prompt = "You are a highly skilled medical assistant. Your task is to interpret a medical report and structure the information into a clear, easy-to-understand JSON format. The report text is provided below.

    **Instructions:**
    1.  **Analyze the Report:** Carefully read the provided medical report text.
    2.  **Extract Key Information:** Identify the main summary, key findings or data points, and any complex medical terms.
    3.  **Format as JSON:** Create a JSON object with the following keys:
        *   `summary`: A concise, easy-to-understand paragraph summarizing the report's overall findings.
        *   `key_points`: An array of strings, where each string is a crucial point or finding from the report (e.g., 'Blood pressure: 120/80 mmHg', 'Cholesterol levels are within the normal range.').
        *   `terms_explained`: An object where each key is a medical term found in the report and its value is a simple, clear explanation of that term.

    **Medical Report Text:**
    ```
    " . $extractedText . "
    ```

    **IMPORTANT:** Ensure the output is ONLY the raw JSON object, without any surrounding text, explanations, or markdown formatting like ```json. The JSON should be well-formed and ready for parsing.";

    $response = $client->generativeModel('gemini-1.5-flash')
        ->generateContent(Content::parse($prompt));

    $simplifiedText = $response->text();

    // 4. Clean, validate, and return the response
    // Attempt to remove markdown formatting (```json ... ```)
    $cleanedJson = preg_replace('/^```json\s*|\s*```$/', '', $simplifiedText);
    
    $jsonResponse = json_decode($cleanedJson, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['summary'])) {
        // It's valid JSON and contains the expected keys
        echo json_encode(['status' => 'success', 'simplified_data' => $jsonResponse]);
    } else {
        // It's not valid JSON, return it as plain text in a structured way
        $fallbackData = [
            'summary' => 'The report was simplified, but the structured data could not be generated. Here is the raw summary:',
            'key_points' => [$simplifiedText],
            'terms_explained' => new stdClass() // Empty object
        ];
        echo json_encode(['status' => 'success', 'simplified_data' => $fallbackData]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    // Catch exceptions from the Gemini client or other errors
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
