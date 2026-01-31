<?php

$rawToken = 'bf9a5c71b366c318734a02a35f3f36fc484f8b9c1a6e5bb441f159c3ede7c06b';
$hashedToken = hash('sha256', $rawToken);

echo "Raw Token:    " . $rawToken . "\n";
echo "Hashed Token: " . $hashedToken . "\n\n";

// Now check database
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = DB::table('users')->where('api_token', $hashedToken)->first();

if ($user) {
    echo "✓ User found in database!\n";
    echo "  ID: " . $user->id . "\n";
    echo "  Email: " . $user->email . "\n";
    echo "  Role: " . $user->role . "\n";
} else {
    echo "✗ User NOT found with this hashed token\n\n";
    
    // Check what's actually in the database
    echo "Checking what token test@example.com has...\n";
    $testUser = DB::table('users')->where('email', 'test@example.com')->first();
    if ($testUser) {
        echo "  Stored token: " . $testUser->api_token . "\n";
        echo "  Expected:     " . $hashedToken . "\n";
        echo "  Match: " . ($testUser->api_token === $hashedToken ? 'YES' : 'NO') . "\n";
    }
}
