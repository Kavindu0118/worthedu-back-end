<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;

echo "=== Checking and Generating API Tokens ===\n\n";

$users = User::all();

if ($users->isEmpty()) {
    echo "No users found in database.\n";
    exit;
}

$generatedTokens = [];

foreach ($users as $user) {
    echo "User: " . $user->email . " (" . $user->role . ")\n";
    
    // Always generate a new token for testing
    echo "  🔄 Generating new token...\n";
    $plainToken = Str::random(60);
    $hashedToken = hash('sha256', $plainToken);
    
    $user->api_token = $hashedToken;
    $user->save();
    
    echo "  ✅ Token generated and hashed!\n";
    echo "  Plain Token (use this): " . $plainToken . "\n";
    echo "  Hashed Token (in DB): " . $hashedToken . "\n\n";
    
    $generatedTokens[$user->email] = $plainToken;
}

echo "========================================\n";
echo "TOKENS TO USE (Plain text - NOT hashed):\n";
echo "========================================\n";
foreach ($generatedTokens as $email => $token) {
    echo $email . ":\n" . $token . "\n\n";
}
echo "========================================\n";
echo "COPY THIS TOKEN FOR TESTING:\n";
echo $generatedTokens[User::first()->email] . "\n";
echo "========================================\n";
