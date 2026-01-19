<?php
// Test module submissions endpoint
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a request to the module submissions endpoint
$request = Request::create('/api/instructor/modules/5/submissions', 'GET');
$request->headers->set('Accept', 'application/json');

// Get an instructor token for testing
$user = \App\Models\User::where('user_type', 'instructor')->first();
if ($user) {
    $token = $user->createToken('test-token')->plainTextToken;
    $request->headers->set('Authorization', 'Bearer ' . $token);
    echo "Using instructor: {$user->name} (ID: {$user->id})\n";
} else {
    echo "No instructor found. Running without auth.\n";
}

echo "Testing GET /api/instructor/modules/5/submissions\n";
echo str_repeat('-', 50) . "\n";

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Response:\n";
    echo $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);
