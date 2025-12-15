<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Getting API Token ===\n\n";

$user = User::first();

if ($user) {
    echo "Email: " . $user->email . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Role: " . $user->role . "\n\n";
    echo "API TOKEN (copy this):\n";
    echo "----------------------------------------\n";
    echo $user->api_token . "\n";
    echo "----------------------------------------\n";
} else {
    echo "No users found in database.\n";
}
