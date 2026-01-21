<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::find(6);
echo "Token length: " . strlen($user->api_token) . "\n";
echo "Token (first 50 chars): " . substr($user->api_token, 0, 50) . "\n";
echo "Is 64 chars (SHA256)? " . (strlen($user->api_token) == 64 ? 'Yes' : 'No') . "\n";

// The middleware expects to receive a plain token and hash it to compare
// Check if the stored token looks like a SHA256 hash
if (strlen($user->api_token) == 64 && ctype_xdigit($user->api_token)) {
    echo "Token appears to be a SHA256 hash stored in DB\n";
    echo "This is CORRECT - middleware will hash the incoming token to compare\n";
} else {
    echo "Token does NOT appear to be hashed\n";
}
