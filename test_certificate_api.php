<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\CourseModule;
use App\Models\Test;
use App\Models\TestSubmission;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==============================================\n";
echo "Testing Certificate API\n";
echo "==============================================\n\n";

// Get our test user
$user = DB::table('users')->where('email', 'test@example.com')->first();
if (!$user) {
    echo "❌ Test user not found\n";
    exit;
}

echo "✓ Test user found: {$user->email} (ID: {$user->id})\n\n";

// Get enrolled course
$enrollment = DB::table('enrollments')->where('learner_id', $user->id)->first();
if (!$enrollment) {
    echo "❌ No enrollment found\n";
    exit;
}

$course = DB::table('courses')->where('id', $enrollment->course_id)->first();
echo "✓ Enrolled in course: {$course->title} (ID: {$course->id})\n\n";

// Create a test certificate
echo "Creating test certificate...\n";

$certificateController = new \App\Http\Controllers\CertificateController();

// First, let's create some sample test data
$moduleIds = DB::table('course_modules')->where('course_id', $course->id)->pluck('id');
echo "  Modules in course: " . count($moduleIds) . "\n";

// Check tests
$tests = DB::table('tests')->whereIn('module_id', $moduleIds)->get();
echo "  Tests in course: " . count($tests) . "\n";

// Create a test submission if there are tests
if (count($tests) > 0) {
    $test = $tests[0];
    echo "  Test: {$test->test_title} (Total marks: {$test->total_marks})\n";
    
    $existingSubmission = DB::table('test_submissions')
        ->where('test_id', $test->id)
        ->where('student_id', $user->id)
        ->first();
    
    if (!$existingSubmission) {
        echo "  Creating test submission...\n";
        DB::table('test_submissions')->insert([
            'test_id' => $test->id,
            'student_id' => $user->id,
            'submission_status' => 'submitted',
            'attempt_number' => 1,
            'started_at' => now(),
            'submitted_at' => now(),
            'total_score' => round($test->total_marks * 0.85), // 85% score
            'grading_status' => 'graded',
            'graded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  ✓ Test submission created with 85% score\n";
    } else {
        echo "  ✓ Test submission already exists\n";
    }
    
    // Publish test results
    DB::table('tests')->where('id', $test->id)->update(['results_published' => true]);
    echo "  ✓ Test results published\n";
}

echo "\n";

// Generate certificate
echo "Generating certificate...\n";
$certificate = $certificateController->generateCertificate($course->id, $user->id);

if ($certificate) {
    echo "✓ Certificate generated!\n";
    echo "  Certificate Number: {$certificate->certificate_number}\n";
    echo "  Final Grade: {$certificate->final_grade}%\n";
    echo "  Letter Grade: {$certificate->letter_grade}\n";
    echo "  Status: {$certificate->status}\n";
    echo "  Can View: " . ($certificate->can_view ? 'Yes' : 'No') . "\n\n";
} else {
    echo "❌ Failed to generate certificate\n\n";
}

// Test API endpoints
$token = '92d131214dcb8f0df93a814acceeefecbcd41266e22013e2e24d3d350701bbcc';

echo "==============================================\n";
echo "Testing API Endpoints\n";
echo "==============================================\n\n";

// Test 1: Get all certificates
echo "1. GET /api/learner/certificates\n";
echo "-----------------------------------\n";
$ch = curl_init('http://127.0.0.1:8000/api/learner/certificates');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
$data = json_decode($response, true);
if ($data && isset($data['data'])) {
    echo "Certificates found: " . count($data['data']) . "\n";
    foreach ($data['data'] as $cert) {
        echo "  - {$cert['courseTitle']}: {$cert['finalGrade']}% ({$cert['letterGrade']}) - {$cert['status']}\n";
        echo "    Can view: " . ($cert['canView'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// Test 2: Get specific certificate
if ($certificate) {
    echo "2. GET /api/learner/certificates/{$certificate->id}\n";
    echo "-----------------------------------\n";
    $ch = curl_init("http://127.0.0.1:8000/api/learner/certificates/{$certificate->id}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        $certData = $data['data'];
        echo "Certificate: {$certData['certificateNumber']}\n";
        echo "Course: {$certData['courseTitle']}\n";
        echo "Student: {$certData['studentName']} ({$certData['studentEmail']})\n";
        echo "Instructor: {$certData['instructorName']}\n";
        echo "Issued: {$certData['issuedAt']}\n\n";
        
        echo "Grade Breakdown:\n";
        $breakdown = $certData['gradeBreakdown'];
        
        echo "  Quizzes:\n";
        echo "    Score: {$breakdown['quizzes']['totalScore']}/{$breakdown['quizzes']['maxScore']}\n";
        echo "    Percentage: {$breakdown['quizzes']['percentage']}%\n";
        echo "    Weight: " . ($breakdown['quizzes']['weight'] * 100) . "%\n";
        echo "    Weighted Score: {$breakdown['quizzes']['weightedScore']}%\n";
        echo "    Count: {$breakdown['quizzes']['count']}\n\n";
        
        echo "  Assignments:\n";
        echo "    Score: {$breakdown['assignments']['totalScore']}/{$breakdown['assignments']['maxScore']}\n";
        echo "    Percentage: {$breakdown['assignments']['percentage']}%\n";
        echo "    Weight: " . ($breakdown['assignments']['weight'] * 100) . "%\n";
        echo "    Weighted Score: {$breakdown['assignments']['weightedScore']}%\n";
        echo "    Count: {$breakdown['assignments']['count']}\n\n";
        
        echo "  Tests:\n";
        echo "    Score: {$breakdown['tests']['totalScore']}/{$breakdown['tests']['maxScore']}\n";
        echo "    Percentage: {$breakdown['tests']['percentage']}%\n";
        echo "    Weight: " . ($breakdown['tests']['weight'] * 100) . "%\n";
        echo "    Weighted Score: {$breakdown['tests']['weightedScore']}%\n";
        echo "    Count: {$breakdown['tests']['count']}\n\n";
        
        echo "  Final Grade: {$breakdown['finalGrade']}%\n";
        echo "  Letter Grade: {$breakdown['letterGrade']}\n";
        echo "  Status: {$breakdown['status']}\n";
    } else {
        echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        echo "Response: " . substr($response, 0, 300) . "\n";
    }
}

echo "\n";

// Test 3: Get certificate by course
echo "3. GET /api/learner/courses/{$course->id}/certificate\n";
echo "-----------------------------------\n";
$ch = curl_init("http://127.0.0.1:8000/api/learner/courses/{$course->id}/certificate");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
$data = json_decode($response, true);
if ($data && $httpCode === 200 && isset($data['data'])) {
    echo "✓ Certificate found for course\n";
    echo "  Certificate Number: {$data['data']['certificateNumber']}\n";
    echo "  Final Grade: {$data['data']['gradeBreakdown']['finalGrade']}%\n";
} else {
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}

echo "\n==============================================\n";
echo "Certificate API Test Complete!\n";
echo "==============================================\n";
