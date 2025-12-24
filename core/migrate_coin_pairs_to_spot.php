<?php

/**
 * Migration Script: Convert all coin pairs to SPOT_TRADE type
 * 
 * This script updates all existing coin pairs to use SPOT_TRADE (type = 1)
 * Run this once after removing binary trading functionality
 * 
 * Usage: php migrate_coin_pairs_to_spot.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Constants\Status;
use App\Models\CoinPair;

echo "Starting coin pair migration to SPOT_TRADE...\n\n";

// Get all coin pairs
$pairs = CoinPair::all();
$total = $pairs->count();
$updated = 0;
$skipped = 0;

echo "Found {$total} coin pair(s) to check...\n\n";

foreach ($pairs as $pair) {
    $oldType = $pair->type;
    
    // Skip if already SPOT_TRADE
    if ($pair->type == Status::SPOT_TRADE) {
        $skipped++;
        echo "✓ Pair #{$pair->id} ({$pair->symbol}) already SPOT_TRADE - skipped\n";
        continue;
    }
    
    // Update to SPOT_TRADE
    $pair->type = Status::SPOT_TRADE;
    $pair->save();
    $updated++;
    
    $typeLabel = $oldType === null ? 'NULL' : ($oldType == 2 ? 'BINARY_TRADE' : ($oldType == 3 ? 'BOTH_TRADE' : $oldType));
    echo "✓ Pair #{$pair->id} ({$pair->symbol}) updated from type {$typeLabel} to SPOT_TRADE\n";
}

echo "\n";
echo "Migration complete!\n";
echo "Total pairs: {$total}\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "\nAll coin pairs are now set to SPOT_TRADE type.\n";

