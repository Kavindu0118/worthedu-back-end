<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== module_assignments Table Structure ===\n\n";

$columns = DB::select('DESCRIBE module_assignments');

printf("%-30s | %-20s | %-10s | %-20s\n", "Field", "Type", "Null", "Default");
echo str_repeat("-", 90) . "\n";

foreach($columns as $col) {
    printf("%-30s | %-20s | %-10s | %-20s\n", 
        $col->Field, 
        $col->Type, 
        $col->Null, 
        $col->Default ?? 'NULL'
    );
}

echo "\n✓ Total columns: " . count($columns) . "\n";
