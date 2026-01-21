<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📋 Course Access Information\n";
echo "============================\n\n";

$course = DB::table('courses')->where('id', 3)->first();

if (!$course) {
    echo "❌ Course 3 not found\n";
    exit(1);
}

$instructor = DB::table('users')->where('id', $course->instructor_id)->first();

echo "Course: {$course->title} (ID: 3)\n";
echo "Owner: {$instructor->name}\n";
echo "Email: {$instructor->email}\n\n";

echo "✅ TO ACCESS THIS COURSE:\n";
echo "  1. Log out from your current account\n";
echo "  2. Log in as: {$instructor->email}\n";
echo "  3. Then you can create tests for this course\n\n";

echo "OR\n\n";

echo "If you want to use your current account:\n";
echo "  1. Create a NEW course as your current user\n";
echo "  2. Then create tests for that new course\n\n";

// Show login credentials if available
echo "💡 Quick Login Script:\n";
echo "Run this in terminal to get Nethmi's token:\n";
echo "php -r \"require 'vendor/autoload.php'; \\$app = require 'bootstrap/app.php'; \\$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap(); \\$user = \\Illuminate\\Support\\Facades\\DB::table('users')->where('email', '{$instructor->email}')->first(); echo 'Token: ' . \\$user->api_token . PHP_EOL;\"\n\n";
