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

// Get language preference, default to English
$language = isset($_GET['language']) ? strtolower($_GET['language']) : 'en';

// Define language names for the prompt
$languageNames = [
    'en' => 'English',
    'mr' => 'Marathi',
    'hi' => 'Hindi',
];

$targetLanguage = $languageNames[$language] ?? $languageNames['en']; // Fallback to English if language not supported

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

    // Check if we have a cached simplified version for this language
    $cacheStmt = $pdo->prepare("
        SELECT simplified_data, simplified_at 
        FROM medical_records 
        WHERE id = ? AND simplified_language = ?
    ");
    $cacheStmt->execute([$record_id, $language]);
    $cachedRow = $cacheStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cachedRow && $cachedRow['simplified_data']) {
        // Return cached data (cache is permanent unless explicitly regenerated)
        $jsonResponse = json_decode($cachedRow['simplified_data'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode([
                'status' => 'success', 
                'simplified_data' => $jsonResponse, 
                'cached' => true,
                'cached_at' => $cachedRow['simplified_at']
            ]);
            exit;
        }
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
        // Use Tesseract for images with multi-language support
        // Determine Tesseract language parameter based on target language
        $tesseractLang = 'eng'; // Default to English
        if ($language === 'mr') {
            $tesseractLang = 'eng+mar'; // Marathi + English for better recognition
        } elseif ($language === 'hi') {
            $tesseractLang = 'eng+hin'; // Hindi + English for better recognition
        }
        
        // Use Tesseract for images. Redirect stderr to stdout to capture errors.
        $command = "tesseract " . escapeshellarg($filePath) . " stdout -l " . $tesseractLang . " 2>&1";
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

    // Add language-specific instructions
    $languageInstruction = "";
    if ($language === 'mr') {
        $languageInstruction = " Use proper Devanagari script for Marathi. Ensure medical terms are explained in simple, everyday Marathi language that is easy to understand.";
    } elseif ($language === 'hi') {
        $languageInstruction = " Use proper Devanagari script for Hindi. Ensure medical terms are explained in simple, everyday Hindi language that is easy to understand.";
    }

    $prompt = "You are a highly skilled medical assistant. Your task is to interpret a medical report and structure the information into a clear, easy-to-understand JSON format. The report text is provided below. The output should be in " . $targetLanguage . "." . $languageInstruction . "

    **Instructions:**
    1.  **Analyze the Report:** Carefully read the provided medical report text.
    2.  **Extract Key Information:** Identify the main summary, key findings or data points, and any complex medical terms.
    3.  **Format as JSON:** Create a JSON object with the following keys:
        *   `summary`: A concise, easy-to-understand paragraph summarizing the report's overall findings in " . $targetLanguage . ". Use simple language that a non-medical person can understand.
        *   `key_points`: An array of strings, where each string is a crucial point or finding from the report, translated into " . $targetLanguage . " (e.g., 'Blood pressure: 120/80 mmHg', 'Cholesterol levels are within the normal range.'). Keep technical values but explain their significance.
        *   `terms_explained`: An object where each key is a medical term found in the report (in " . $targetLanguage . ") and its value is a simple, clear explanation of that term in " . $targetLanguage . ". Explain terms as if talking to someone without medical knowledge.

    **Medical Report Text:**
    ```
    " . $extractedText . "
    ```

    **IMPORTANT:** Ensure the output is ONLY the raw JSON object, without any surrounding text, explanations, or markdown formatting like ```json. The JSON should be well-formed and ready for parsing. All text content inside the JSON must be in " . $targetLanguage . ".";

    // Use the exact same API format as the working curl command
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";
    
    $postData = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ]);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $gemini_api_key
    ]);
    
    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($apiResponse, true);
        throw new Exception($errorData['error']['message'] ?? 'API request failed with code ' . $httpCode);
    }
    
    $responseData = json_decode($apiResponse, true);
    $simplifiedText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // 4. Clean, validate, and return the response
    // Attempt to remove markdown formatting (```json ... ```)
    $cleanedJson = preg_replace('/^```json\s*|\s*```$/', '', $simplifiedText);
    
    $jsonResponse = json_decode($cleanedJson, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['summary'])) {
        // It's valid JSON and contains the expected keys
        // Store in cache
        $updateStmt = $pdo->prepare("
            UPDATE medical_records 
            SET simplified_data = ?, simplified_language = ?, simplified_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([json_encode($jsonResponse), $language, $record_id]);
        
        echo json_encode(['status' => 'success', 'simplified_data' => $jsonResponse, 'cached' => false]);
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
    $errorMessage = $e->getMessage();
    
    // Check if it's a quota/rate limit error
    if (stripos($errorMessage, 'quota') !== false || 
        stripos($errorMessage, 'rate limit') !== false ||
        stripos($errorMessage, 'RESOURCE_EXHAUSTED') !== false ||
        stripos($errorMessage, '429') !== false) {
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'AI service quota exceeded. This report will be simplified when the quota resets. Please try again in a few minutes, or use a cached version if available.',
            'error_type' => 'quota_exceeded',
            'retry_after' => 60, // Suggest retry after 60 seconds
            'suggestion' => 'The system caches simplified reports. If you\'ve viewed this before, the cached version will be used automatically.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'An error occurred while processing your request: ' . $errorMessage,
            'error_type' => 'processing_error'
        ]);
    }
}
