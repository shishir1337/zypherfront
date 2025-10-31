<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\User;

echo "=== Auditing Zero-Rate Orders ===\n\n";

// Find all zero-rate orders
$zeroRateOrders = Order::where('rate', 0)
    ->where('status', 1) // Completed
    ->with('user', 'pair', 'pair.coin', 'pair.market.currency')
    ->orderBy('created_at', 'desc')
    ->get();

if ($zeroRateOrders->isEmpty()) {
    echo "✅ No zero-rate orders found in the system.\n";
    exit;
}

echo "⚠️  Found {$zeroRateOrders->count()} zero-rate order(s):\n\n";

$totalLoss = 0;

foreach ($zeroRateOrders as $order) {
    $user = $order->user;
    $pair = $order->pair;
    
    echo "────────────────────────────────────────\n";
    echo "Order ID: {$order->id}\n";
    echo "User: {$user->username} (ID: {$user->id})\n";
    echo "Pair: {$pair->symbol}\n";
    echo "Amount: {$order->amount} {$pair->coin->symbol}\n";
    echo "Rate: {$order->rate} (ZERO!)\n";
    echo "Total: {$order->total}\n";
    echo "Created: {$order->created_at}\n";
    
    // Estimate the loss based on current market price
    if ($pair->marketData && $pair->marketData->price > 0) {
        $currentPrice = $pair->marketData->price;
        $shouldHavePaid = $order->amount * $currentPrice;
        $totalLoss += $shouldHavePaid;
        
        echo "Current Market Price: {$currentPrice}\n";
        echo "Should Have Paid: {$shouldHavePaid} {$pair->market->currency->symbol}\n";
        echo "Estimated Loss: ~\${$shouldHavePaid}\n";
    } else {
        echo "Market Price: N/A\n";
    }
    
    echo "\n";
}

echo "────────────────────────────────────────\n";
echo "SUMMARY:\n";
echo "Total Zero-Rate Orders: {$zeroRateOrders->count()}\n";
echo "Estimated Total Loss: ~\${$totalLoss}\n";
echo "\n";

echo "Affected Users:\n";
$affectedUsers = $zeroRateOrders->groupBy('user_id');
foreach ($affectedUsers as $userId => $orders) {
    $user = $orders->first()->user;
    echo "  - {$user->username} (ID: {$userId}): {$orders->count()} order(s)\n";
}

echo "\n";
echo "RECOMMENDATIONS:\n";
echo "1. Review each order individually\n";
echo "2. Run fix_zero_rate_order.php to correct balances\n";
echo "3. Consider notifying affected users\n";
echo "4. Monitor for any abuse patterns\n";

