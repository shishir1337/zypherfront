<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Frontend;
use Illuminate\Support\Facades\DB;

echo "Searching for 'Vinance' in frontends table...\n\n";

$results = Frontend::whereRaw("data_values LIKE '%Vinance%'")->get();

if ($results->isEmpty()) {
    echo "No hardcoded Vinance found in frontends table.\n";
} else {
    foreach ($results as $result) {
        echo "ID: {$result->id}\n";
        echo "Data Keys: {$result->data_keys}\n";
        echo "Template: {$result->tempname}\n";
        $dataValues = is_string($result->data_values) ? $result->data_values : json_encode($result->data_values);
        echo "Data Values: " . substr($dataValues, 0, 300) . "...\n";
        echo "---\n\n";
    }
    echo "\nTotal found: " . count($results) . "\n";
}

echo "\n\nSearching for 'Vinance' in email_templates table...\n\n";

$emailResults = DB::table('email_templates')->where('body', 'like', '%Vinance%')->orWhere('subject', 'like', '%Vinance%')->get();

if ($emailResults->isEmpty()) {
    echo "No hardcoded Vinance found in email_templates table.\n";
} else {
    foreach ($emailResults as $result) {
        echo "Template: {$result->name}\n";
        echo "Subject: {$result->subject}\n";
        echo "Body: " . substr($result->body, 0, 300) . "...\n";
        echo "---\n\n";
    }
    echo "\nTotal found: " . count($emailResults) . "\n";
}
