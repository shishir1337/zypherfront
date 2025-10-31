<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STARTING CRON RUNNER ===\n\n";

// Function to trigger cron jobs
function triggerCronJobs() {
    $url = 'http://127.0.0.1:8000/cron';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'http_code' => $httpCode];
}

echo "🔄 Starting cron runner (every 10 seconds)...\n";
echo "  This will run until you press Ctrl+C\n";
echo "  Cron URL: http://127.0.0.1:8000/cron\n\n";

$runCount = 0;

while (true) {
    $runCount++;
    $currentTime = now()->format('H:i:s');
    
    echo "[{$currentTime}] Run #{$runCount} - Triggering cron jobs...\n";
    
    $result = triggerCronJobs();
    
    if ($result['http_code'] == 200) {
        echo "  ✅ Cron jobs triggered successfully\n";
    } else {
        echo "  ❌ Error: HTTP {$result['http_code']}\n";
        echo "  Response: " . substr($result['response'], 0, 100) . "...\n";
    }
    
    // Check if prices updated
    $zph = \App\Models\Currency::where('symbol', 'ZPH')->first();
    $btc = \App\Models\Currency::where('symbol', 'BTC')->first();
    
    echo "  💰 Current prices: ZPH={$zph->rate} USDT, BTC={$btc->rate} USDT\n";
    echo "  Last updated: ZPH={$zph->updated_at}, BTC={$btc->updated_at}\n";
    echo "  ---\n";
    
    sleep(10); // Wait 10 seconds
}

echo "\n=== CRON RUNNER STOPPED ===\n";
