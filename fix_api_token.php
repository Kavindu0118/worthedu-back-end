<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Fixing API Token for test@example.com\n";
echo "======================================\n\n";

$user = DB::table('users')->where('email', 'test@example.com')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

// Generate a new token
$plainToken = bin2hex(random_bytes(32)); // 64 character hex string
$hashedToken = hash('sha256', $plainToken);

// Update database
DB::table('users')
    ->where('id', $user->id)
    ->update(['api_token' => $hashedToken]);

echo "Token updated!\n\n";
echo "Use this token for API requests:\n";
echo "========================================\n";
echo $plainToken . "\n";
echo "========================================\n\n";
echo "This token has been hashed and stored in the database.\n";
