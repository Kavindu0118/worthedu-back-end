<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\AdminController();

echo "=== Testing Course Delete Endpoint ===\n\n";

// First, get all courses to see what we have
echo "1. Getting all courses...\n";
$request = new Illuminate\Http\Request();
$response = $controller->getAllCourses($request);
$data = json_decode($response->getContent(), true);

if ($data['success'] && !empty($data['courses'])) {
    echo "Found {$data['summary']['total_courses']} courses:\n";
    foreach ($data['courses'] as $course) {
        echo "  - ID: {$course['id']}, Title: {$course['title']}, Enrollments: {$course['enrollments_count']}\n";
    }
    echo "\n";
    
    // Find a course with 0 enrollments to safely delete
    $courseToDelete = null;
    foreach ($data['courses'] as $course) {
        if ($course['enrollments_count'] == 0) {
            $courseToDelete = $course;
            break;
        }
    }
    
    if ($courseToDelete) {
        echo "2. Testing delete on course with 0 enrollments...\n";
        echo "Attempting to delete: {$courseToDelete['title']} (ID: {$courseToDelete['id']})\n\n";
        
        $deleteResponse = $controller->deleteCourse($courseToDelete['id']);
        $deleteData = json_decode($deleteResponse->getContent(), true);
        
        echo "Status Code: {$deleteResponse->getStatusCode()}\n";
        echo "Response: " . json_encode($deleteData, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "No courses with 0 enrollments found. Testing with first course (may fail due to enrollments)...\n";
        $testCourse = $data['courses'][0];
        
        echo "Attempting to delete: {$testCourse['title']} (ID: {$testCourse['id']})\n";
        echo "Note: This has {$testCourse['enrollments_count']} enrollments\n\n";
        
        $deleteResponse = $controller->deleteCourse($testCourse['id']);
        $deleteData = json_decode($deleteResponse->getContent(), true);
        
        echo "Status Code: {$deleteResponse->getStatusCode()}\n";
        echo "Response: " . json_encode($deleteData, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Test with non-existent course
    echo "3. Testing delete with non-existent course ID (999)...\n";
    $notFoundResponse = $controller->deleteCourse(999);
    $notFoundData = json_decode($notFoundResponse->getContent(), true);
    
    echo "Status Code: {$notFoundResponse->getStatusCode()}\n";
    echo "Response: " . json_encode($notFoundData, JSON_PRETTY_PRINT) . "\n\n";
    
} else {
    echo "No courses found in database\n";
}

echo "=== Test Complete ===\n";
