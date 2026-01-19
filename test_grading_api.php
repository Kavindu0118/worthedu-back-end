<?php
/**
 * Test Script for Instructor Grading API Endpoints
 * 
 * This script tests all instructor grading endpoints:
 * 1. Get assignment submissions
 * 2. Get single submission details
 * 3. Grade a submission
 * 4. Get module submissions overview
 */

// Configuration
$apiToken = '1|tK8yaJrCXMIWYTBv7bVc0VJmzLhGqN3YoU6P4sFSa45a3e09'; // Replace with your API token
$baseUrl = 'http://localhost/learning-lms/public/api/instructor';

// Test data
$testAssignmentId = 1;
$testSubmissionId = 1;
$testModuleId = 1;

echo "========================================\n";
echo "Instructor Grading API Test Suite\n";
echo "========================================\n\n";

// Helper function to make API calls
function makeApiCall($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response ? json_decode($response, true) : null,
        'error' => $error,
    ];
}

// Test 1: Get Assignment Submissions
echo "Test 1: Get Assignment Submissions\n";
echo "=====================================\n";
echo "Endpoint: GET /assignments/{$testAssignmentId}/submissions\n\n";

$result = makeApiCall(
    "$baseUrl/assignments/$testAssignmentId/submissions",
    'GET',
    null,
    $apiToken
);

echo "HTTP Status Code: " . $result['http_code'] . "\n";

if ($result['error']) {
    echo "cURL Error: " . $result['error'] . "\n";
}

if ($result['response']) {
    echo "Success: " . ($result['response']['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($result['response']['assignment'])) {
        echo "\nAssignment Details:\n";
        echo "  Title: " . ($result['response']['assignment']['title'] ?? 'N/A') . "\n";
        echo "  Max Points: " . ($result['response']['assignment']['max_points'] ?? 'N/A') . "\n";
        echo "  Due Date: " . ($result['response']['assignment']['due_date'] ?? 'N/A') . "\n";
    }
    
    if (isset($result['response']['statistics'])) {
        echo "\nStatistics:\n";
        echo "  Total Submissions: " . ($result['response']['statistics']['total_submissions'] ?? 0) . "\n";
        echo "  Graded: " . ($result['response']['statistics']['graded'] ?? 0) . "\n";
        echo "  Pending: " . ($result['response']['statistics']['pending'] ?? 0) . "\n";
        echo "  Average Marks: " . ($result['response']['statistics']['average_marks'] ?? 'N/A') . "\n";
    }
    
    if (isset($result['response']['submissions']) && is_array($result['response']['submissions'])) {
        echo "\nSubmissions Count: " . count($result['response']['submissions']) . "\n";
        
        if (count($result['response']['submissions']) > 0) {
            echo "\nFirst Submission:\n";
            $first = $result['response']['submissions'][0];
            echo "  ID: " . ($first['id'] ?? 'N/A') . "\n";
            echo "  Student: " . ($first['student_name'] ?? 'N/A') . "\n";
            echo "  Status: " . ($first['status'] ?? 'N/A') . "\n";
            echo "  Marks: " . ($first['marks_obtained'] ?? 'Not graded') . "/" . ($first['max_points'] ?? 'N/A') . "\n";
            echo "  Grade: " . ($first['grade'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "No response received\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test 2: Get Submission Details
echo "Test 2: Get Submission Details\n";
echo "================================\n";
echo "Endpoint: GET /submissions/{$testSubmissionId}\n\n";

$result = makeApiCall(
    "$baseUrl/submissions/$testSubmissionId",
    'GET',
    null,
    $apiToken
);

echo "HTTP Status Code: " . $result['http_code'] . "\n";

if ($result['error']) {
    echo "cURL Error: " . $result['error'] . "\n";
}

if ($result['response']) {
    echo "Success: " . ($result['response']['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($result['response']['submission'])) {
        $sub = $result['response']['submission'];
        echo "\nSubmission Details:\n";
        echo "  ID: " . ($sub['id'] ?? 'N/A') . "\n";
        echo "  Assignment: " . ($sub['assignment_title'] ?? 'N/A') . "\n";
        echo "  Student Name: " . ($sub['student']['name'] ?? 'N/A') . "\n";
        echo "  Student Email: " . ($sub['student']['email'] ?? 'N/A') . "\n";
        echo "  Submitted At: " . ($sub['submitted_at'] ?? 'N/A') . "\n";
        echo "  Status: " . ($sub['status'] ?? 'N/A') . "\n";
        echo "  Is Late: " . ($sub['is_late'] ? 'Yes' : 'No') . "\n";
        echo "  File Name: " . ($sub['file_name'] ?? 'No file') . "\n";
        echo "  Marks: " . ($sub['marks_obtained'] ?? 'Not graded') . "/" . ($sub['max_points'] ?? 'N/A') . "\n";
        echo "  Percentage: " . ($sub['percentage'] ?? 'N/A') . "%\n";
        echo "  Grade: " . ($sub['grade'] ?? 'N/A') . "\n";
        
        if (!empty($sub['feedback'])) {
            echo "  Feedback: " . substr($sub['feedback'], 0, 100) . (strlen($sub['feedback']) > 100 ? '...' : '') . "\n";
        }
    }
} else {
    echo "No response received\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test 3: Grade Submission
echo "Test 3: Grade Submission\n";
echo "=========================\n";
echo "Endpoint: PUT /submissions/{$testSubmissionId}/grade\n\n";

$gradeData = [
    'marks_obtained' => 88.5,
    'feedback' => 'Excellent work! Your code is well-structured and follows best practices. The documentation is clear and comprehensive. Keep up the great work!',
    'grade' => 'B+', // Optional: can be omitted for auto-calculation
];

echo "Grade Data:\n";
echo "  Marks: " . $gradeData['marks_obtained'] . "\n";
echo "  Grade: " . $gradeData['grade'] . "\n";
echo "  Feedback: " . substr($gradeData['feedback'], 0, 80) . "...\n\n";

$result = makeApiCall(
    "$baseUrl/submissions/$testSubmissionId/grade",
    'PUT',
    $gradeData,
    $apiToken
);

echo "HTTP Status Code: " . $result['http_code'] . "\n";

if ($result['error']) {
    echo "cURL Error: " . $result['error'] . "\n";
}

if ($result['response']) {
    echo "Success: " . ($result['response']['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($result['response']['message'])) {
        echo "Message: " . $result['response']['message'] . "\n";
    }
    
    if (isset($result['response']['submission'])) {
        $sub = $result['response']['submission'];
        echo "\nGraded Submission:\n";
        echo "  Student: " . ($sub['student_name'] ?? 'N/A') . "\n";
        echo "  Assignment: " . ($sub['assignment_title'] ?? 'N/A') . "\n";
        echo "  Marks: " . ($sub['marks_obtained'] ?? 'N/A') . "/" . ($sub['max_points'] ?? 'N/A') . "\n";
        echo "  Percentage: " . ($sub['percentage'] ?? 'N/A') . "%\n";
        echo "  Grade: " . ($sub['grade'] ?? 'N/A') . "\n";
        echo "  Status: " . ($sub['status'] ?? 'N/A') . "\n";
        echo "  Graded At: " . ($sub['graded_at'] ?? 'N/A') . "\n";
    }
    
    if (isset($result['response']['errors'])) {
        echo "\nValidation Errors:\n";
        print_r($result['response']['errors']);
    }
} else {
    echo "No response received\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test 4: Get Module Submissions Overview
echo "Test 4: Get Module Submissions Overview\n";
echo "=========================================\n";
echo "Endpoint: GET /modules/{$testModuleId}/submissions\n\n";

$result = makeApiCall(
    "$baseUrl/modules/$testModuleId/submissions",
    'GET',
    null,
    $apiToken
);

echo "HTTP Status Code: " . $result['http_code'] . "\n";

if ($result['error']) {
    echo "cURL Error: " . $result['error'] . "\n";
}

if ($result['response']) {
    echo "Success: " . ($result['response']['success'] ? 'Yes' : 'No') . "\n";
    echo "Module ID: " . ($result['response']['module_id'] ?? 'N/A') . "\n";
    
    if (isset($result['response']['assignments']) && is_array($result['response']['assignments'])) {
        echo "\nAssignments Count: " . count($result['response']['assignments']) . "\n";
        
        foreach ($result['response']['assignments'] as $index => $assignment) {
            echo "\nAssignment " . ($index + 1) . ":\n";
            echo "  Title: " . ($assignment['assignment_title'] ?? 'N/A') . "\n";
            echo "  Max Points: " . ($assignment['max_points'] ?? 'N/A') . "\n";
            echo "  Due Date: " . ($assignment['due_date'] ?? 'N/A') . "\n";
            echo "  Total Submissions: " . ($assignment['total_submissions'] ?? 0) . "\n";
            echo "  Graded: " . ($assignment['graded_submissions'] ?? 0) . "\n";
            echo "  Pending: " . ($assignment['pending_submissions'] ?? 0) . "\n";
            echo "  Average Marks: " . ($assignment['average_marks'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "No response received\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Summary
echo "========================================\n";
echo "Test Suite Completed\n";
echo "========================================\n";
echo "\nAll API endpoints have been tested.\n";
echo "Check the responses above for detailed results.\n\n";
echo "Next Steps:\n";
echo "1. Verify all HTTP status codes are 200 (success)\n";
echo "2. Check that data is returned correctly\n";
echo "3. Test with different assignment/submission IDs\n";
echo "4. Integrate endpoints into your frontend application\n";
echo "5. Refer to INSTRUCTOR_GRADING_API_GUIDE.md for detailed documentation\n";
