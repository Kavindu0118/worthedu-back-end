<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\AdminController();

echo "=== Testing Instructor Status Update ===\n\n";

// First, get all instructors to find one to test with
echo "1. Getting all instructors...\n";
$request = new Illuminate\Http\Request();
$response = $controller->getAllInstructors($request);
$data = json_decode($response->getContent(), true);

if ($data['success'] && !empty($data['instructors'])) {
    $testInstructor = $data['instructors'][0];
    echo "Found instructor: {$testInstructor['name']} (ID: {$testInstructor['instructor_id']}, Current Status: {$testInstructor['status']})\n\n";
    
    // Test updating status
    echo "2. Testing status update to 'approved'...\n";
    $updateRequest = new Illuminate\Http\Request();
    $updateRequest->merge([
        'status' => 'approved',
        'note' => 'Test approval from admin API test'
    ]);
    
    $updateResponse = $controller->updateInstructorStatus($updateRequest, $testInstructor['instructor_id']);
    $updateData = json_decode($updateResponse->getContent(), true);
    
    if ($updateData['success']) {
        echo "✓ Status updated successfully!\n";
        echo "New status: {$updateData['instructor']['status']}\n";
        echo "Note: {$updateData['instructor']['note']}\n\n";
    } else {
        echo "✗ Error: {$updateData['message']}\n\n";
    }
    
    // Test getting instructor details
    echo "3. Testing get instructor details...\n";
    $detailsResponse = $controller->getInstructorDetails($testInstructor['instructor_id']);
    $detailsData = json_decode($detailsResponse->getContent(), true);
    
    if ($detailsData['success']) {
        echo "✓ Details retrieved successfully!\n";
        echo "Name: {$detailsData['instructor']['name']}\n";
        echo "Status: {$detailsData['instructor']['status']}\n";
        echo "Has CV: " . ($detailsData['instructor']['has_cv'] ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "✗ Error: {$detailsData['message']}\n\n";
    }
    
} else {
    echo "No instructors found in database\n";
}

echo "=== Test Complete ===\n";
