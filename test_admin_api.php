<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\AdminController();
$request = new Illuminate\Http\Request();

// Test all three endpoints
echo "=== Testing getAllStudents ===\n";
try {
    $response = $controller->getAllStudents($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "Total Students: " . ($data['total_students'] ?? 'N/A') . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== Testing getAllInstructors ===\n";
try {
    $response = $controller->getAllInstructors($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "Total Instructors: " . ($data['total_instructors'] ?? 'N/A') . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== Testing getAllCourses ===\n";
try {
    $response = $controller->getAllCourses($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "Total Courses: " . ($data['summary']['total_courses'] ?? 'N/A') . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

