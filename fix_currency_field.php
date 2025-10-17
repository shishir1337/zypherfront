<?php
// Fix currency field in gateway_currencies

$host = 'localhost';
$dbname = 'vinance_db';
$username = 'root';
$password = '';

echo "🔧 Fixing currency field...\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update currency fields to proper string values
    $updates = [
        1001 => 'USDT',
        1002 => 'BTC',
        1003 => 'ETH'
    ];
    
    foreach ($updates as $code => $currency) {
        $stmt = $pdo->prepare("UPDATE gateway_currencies SET currency = ? WHERE method_code = ?");
        $stmt->execute([$currency, $code]);
        echo "✅ Updated code $code to currency: $currency\n";
    }
    
    echo "\n✅ All currency fields fixed!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

