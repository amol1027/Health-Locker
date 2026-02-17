<?php
require_once 'config/config.php';

echo "Testing Gemini API with Direct REST Call...\n\n";
echo "API Key: " . substr(GEMINI_API_KEY, 0, 10) . "...\n\n";
echo "Waiting 40 seconds for rate limit to reset...\n";
sleep(40);
echo "Testing now...\n\n";

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";

$postData = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say "Hello, API is working!"']
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
    'x-goog-api-key: ' . GEMINI_API_KEY
]);

$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

if ($curlError) {
    echo "✗ cURL Error: $curlError\n";
    exit;
}

if ($httpCode === 200) {
    $responseData = json_decode($apiResponse, true);
    $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No text in response';
    
    echo "✓ SUCCESS!\n";
    echo "API Response: $text\n\n";
    echo "===================================\n";
    echo "Your API is working correctly!\n";
    echo "===================================\n";
} else {
    echo "✗ ERROR!\n";
    echo "Response: $apiResponse\n";
    
    $errorData = json_decode($apiResponse, true);
    if (isset($errorData['error']['message'])) {
        echo "\nError Message: " . $errorData['error']['message'] . "\n";
    }
}
?>
