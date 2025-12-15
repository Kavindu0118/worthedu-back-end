<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$notes = \App\Models\ModuleNote::all();

echo "Total notes: " . $notes->count() . PHP_EOL;
echo PHP_EOL;

foreach ($notes as $note) {
    echo "Note ID: " . $note->id . PHP_EOL;
    echo "Title: " . $note->note_title . PHP_EOL;
    echo "Module ID: " . $note->module_id . PHP_EOL;
    echo "Attachment URL (raw): " . $note->getRawOriginal('attachment_url') . PHP_EOL;
    echo "---" . PHP_EOL;
}
