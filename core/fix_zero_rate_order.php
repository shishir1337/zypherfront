<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;

echo "=== Fixing Zero Rate Order Issue ===\n\n";

// Find the problematic order
$problematicOrder = Order::where('rate', 0)
    ->where('status', 1) // Completed
    ->orderBy('id', 'desc')
    ->first();

if (!$problematicOrder) {
    echo "No zero-rate orders found.\n";
    exit;
}

echo "Found problematic order:\n";
echo "  Order ID: {$problematicOrder->id}\n";
echo "  User ID: {$problematicOrder->user_id}\n";
echo "  Amount: {$problematicOrder->amount}\n";
echo "  Rate: {$problematicOrder->rate}\n";
echo "  Total: {$problematicOrder->total}\n";
echo "  Created: {$problematicOrder->created_at}\n\n";

$user = User::find($problematicOrder->user_id);
if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User: {$user->username}\n\n";

// Get the pair to calculate what should have been charged
$pair = $problematicOrder->pair;
if (!$pair) {
    echo "Pair not found!\n";
    exit;
}

echo "Pair: {$pair->symbol}\n";

// Get current market price
$currentMarketPrice = $pair->marketData ? $pair->marketData->price : 0;
echo "Current Market Price: {$currentMarketPrice}\n\n";

// Calculate what should have been charged at the time
// We'll use the current market price as an approximation
$actualCost = $problematicOrder->amount * $currentMarketPrice;
$charge = ($actualCost / 100) * $pair->percent_charge_for_buy;

echo "What should have been charged:\n";
echo "  Amount in USDT: {$actualCost}\n";
echo "  Charge: {$charge}\n";
echo "  Total: " . ($actualCost + $charge) . "\n\n";

// Get user's USDT wallet
$marketCurrency = $pair->market->currency;
$usdtWallet = Wallet::where('user_id', $user->id)
    ->where('currency_id', $marketCurrency->id)
    ->where('wallet_type', 1) // SPOT
    ->first();

if (!$usdtWallet) {
    echo "USDT wallet not found!\n";
    exit;
}

echo "Current USDT Balance: {$usdtWallet->balance}\n";
echo "Balance after correction: " . ($usdtWallet->balance - $actualCost - $charge) . "\n\n";

// Ask for confirmation
echo "Do you want to fix this? This will:\n";
echo "1. Deduct {$actualCost} USDT (+ {$charge} charge) from user's balance\n";
echo "2. Update the order rate to {$currentMarketPrice}\n";
echo "3. Update the order total to {$actualCost}\n";
echo "4. Create a correcting transaction\n\n";

echo "Type 'YES' to proceed or anything else to cancel: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if ($line !== 'YES') {
    echo "Operation cancelled.\n";
    exit;
}

// Start transaction
\DB::beginTransaction();

try {
    // Update the order
    $problematicOrder->rate = $currentMarketPrice;
    $problematicOrder->total = $actualCost;
    $problematicOrder->charge = $charge;
    $problematicOrder->save();
    echo "✓ Order updated\n";
    
    // Deduct from wallet
    $usdtWallet->balance -= ($actualCost + $charge);
    $usdtWallet->save();
    echo "✓ USDT balance corrected\n";
    
    // Create correcting transaction
    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->wallet_id = $usdtWallet->id;
    $transaction->amount = $actualCost;
    $transaction->post_balance = $usdtWallet->balance;
    $transaction->charge = $charge;
    $transaction->trx_type = '-';
    $transaction->details = "Balance correction for order #{$problematicOrder->id} that was executed at zero rate";
    $transaction->trx = getTrx();
    $transaction->remark = 'balance_correction';
    $transaction->save();
    echo "✓ Correction transaction created (ID: {$transaction->id})\n";
    
    \DB::commit();
    echo "\n✅ Fix applied successfully!\n\n";
    
    echo "New balances:\n";
    echo "  USDT: {$usdtWallet->balance}\n";
    
    $bnbWallet = Wallet::where('user_id', $user->id)
        ->where('currency_id', $pair->coin->id)
        ->where('wallet_type', 1)
        ->first();
    if ($bnbWallet) {
        echo "  BNB: {$bnbWallet->balance}\n";
    }
    
} catch (\Exception $e) {
    \DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

