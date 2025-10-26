<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Frontend;
use Illuminate\Support\Facades\DB;

echo "======================================\n";
echo "Replacing 'Vinance' with 'BigBuller'\n";
echo "======================================\n\n";

$results = Frontend::whereRaw("data_values LIKE '%Vinance%'")->get();

$updated = 0;
foreach ($results as $result) {
    $oldData = $result->data_values;
    
    // Handle both string and JSON object data_values
    if (is_object($oldData)) {
        $dataString = json_encode($oldData);
    } else {
        $dataString = $oldData;
    }
    
    // Replace all occurrences of Vinance with BigBuller (case variations)
    $newData = str_ireplace('Vinance', 'BigBuller', $dataString);
    $newData = str_ireplace('vinance', 'BigBuller', $newData);
    
    // Only update if there was a change
    if ($newData !== $dataString) {
        DB::table('frontends')->where('id', $result->id)->update([
            'data_values' => $newData
        ]);
        
        echo "✓ Updated ID {$result->id} - {$result->data_keys}\n";
        $updated++;
    }
}

echo "\n======================================\n";
echo "Total Updated: $updated\n";
echo "======================================\n";
