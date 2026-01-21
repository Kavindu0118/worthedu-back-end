<?php
/**
 * Test Student Test API endpoints
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== Testing Student Test API ===\n\n";

// Get a learner with token
$learner = User::where('email', 'isuru@gmail.com')->whereNotNull('api_token')->first();
if (!$learner) {
    echo "No learner found with api_token, searching for any learner...\n";
    $learner = User::where('role', 'learner')->whereNotNull('api_token')->first();
}

if (!$learner || !$learner->api_token) {
    echo "No learner with api_token found!\n";
    exit(1);
}

$tokenValue = $learner->api_token;
echo "Learner: {$learner->name} ({$learner->email})\n";
echo "Using token: " . substr($tokenValue, 0, 20) . "...\n";

// Test GET /api/student/tests/1
$baseUrl = 'http://localhost/learning-lms/public';
$testId = 1;

echo "\n--- Testing GET /api/student/tests/{$testId} ---\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/api/student/tests/{$testId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenValue,
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";

$data = json_decode($response, true);
if ($data) {
    echo "Response:\n";
    print_r($data);
} else {
    echo "Raw response: " . substr($response, 0, 500) . "\n";
}

echo "\n=== Test Complete ===\n";
