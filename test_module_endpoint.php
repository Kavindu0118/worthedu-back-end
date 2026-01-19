<?php

// Test the module submissions endpoint
$moduleId = 5; // Change this to a real module ID from your database
$token = "YOUR_TOKEN_HERE"; // Replace with a real API token

$url = "http://localhost/learning-lms/public/api/instructor/modules/{$moduleId}/submissions";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Testing URL: {$url}\n";
echo "HTTP Status Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "\n";

if ($httpCode === 200) {
    echo "\n✅ SUCCESS! Endpoint is working correctly.\n";
} elseif ($httpCode === 401) {
    echo "\n⚠️  Unauthorized. You need a valid API token.\n";
} elseif ($httpCode === 404) {
    echo "\n⚠️  Module not found or has no assignments.\n";
} else {
    echo "\n❌ ERROR: Unexpected response.\n";
}
