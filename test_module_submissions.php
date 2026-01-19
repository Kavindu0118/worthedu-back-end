<?php
/**
 * Quick test for Module Submissions endpoint
 */

$apiToken = '1|tK8yaJrCXMIWYTBv7bVc0VJmzLhGqN3YoU6P4sFSa45a3e09'; // Replace with your token
$moduleId = 5; // The module ID you're testing
$baseUrl = 'http://localhost/learning-lms/public/api/instructor';

echo "Testing Module Submissions Endpoint\n";
echo "====================================\n\n";

$url = "$baseUrl/modules/$moduleId/submissions";
echo "Testing URL: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
}

if ($response) {
    echo "\nResponse:\n";
    $data = json_decode($response, true);
    print_r($data);
} else {
    echo "No response received\n";
}

echo "\n\n";
echo "If you see HTTP 200 and success: true, the backend is working!\n";
echo "The problem is in your frontend code.\n";
