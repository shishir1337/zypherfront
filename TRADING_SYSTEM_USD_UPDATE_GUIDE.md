# Trading System USD Update Guide

## Current Trading System

The current system uses **crypto-to-crypto** trading:
- User has BTC wallet, ETH wallet, USDT wallet, etc.
- When buying BTC/USDT pair: Deducts USDT, adds BTC
- When selling BTC/USDT pair: Deducts BTC, adds USDT

## New USD-Based Trading System

In the new system, users trade with their **USD balance**:
- User has single USD balance
- All trades deduct/add USD
- No crypto wallets involved in trading

##How It Should Work

### Example 1: Buy BTC

**Current Price**: BTC = $115,000 USD

**User Action**: Buy 0.001 BTC

**Process**:
1. Calculate cost: 0.001 × $115,000 = **$115 USD**
2. Add trading fee (e.g., 0.5%): $115 × 1.005 = **$115.58 USD total**
3. Check if user has $115.58 in `usd_balance`
4. If yes:
   - Deduct $115.58 from `user.usd_balance`
   - User now "owns" 0.001 BTC worth of USD value
   - Store this in a portfolio/position table
5. Create transaction showing the trade

**Result**: User's USD balance decreased by $115.58

### Example 2: Sell BTC

**Current Price**: BTC = $120,000 USD (price went up!)

**User Action**: Sell 0.001 BTC

**Process**:
1. Calculate value: 0.001 × $120,000 = **$120 USD**
2. Subtract trading fee (e.g., 0.5%): $120 × 0.995 = **$119.40 USD received**
3. Add $119.40 to `user.usd_balance`
4. Remove BTC position from portfolio

**Result**: User's USD balance increased by $119.40 (profit of $3.82!)

## Implementation Options

### Option A: Pure USD Trading (Simplest)

**Concept**: Users don't actually "hold" crypto, they trade USD against crypto prices.

**Structure**:
- No crypto wallets
- Only USD balance
- Trading is just USD in/out based on crypto prices
- Portfolio shows "positions" in crypto but stored as USD values

**Pros**:
- Simplest implementation
- No wallet management
- Fast execution

**Cons**:
- Users don't actually "own" crypto
- Can't withdraw crypto they "bought"
- More like CFD trading

### Option B: USD + Virtual Portfolio (Recommended)

**Concept**: Use USD for trading but track crypto holdings virtually.

**Structure**:
```
users table:
- usd_balance (available USD)
- usd_balance_in_order (USD locked in orders)

user_portfolio table (NEW):
- user_id
- currency_id
- amount (how much crypto they "own")
- average_buy_price (for P&L calculation)
- total_invested_usd
```

**Trading Flow**:
1. **Buy BTC**: 
   - Deduct USD from balance
   - Add BTC amount to portfolio
   - Track average purchase price

2. **Sell BTC**:
   - Remove BTC from portfolio
   - Add USD to balance
   - Calculate profit/loss

**Pros**:
- Clear ownership tracking
- Can calculate P&L easily
- Can allow crypto withdrawals
- Better UX (users see their "holdings")

**Cons**:
- More complex to implement
- Need portfolio management

### Option C: Hybrid System (Most Flexible)

Keep both systems:
- USD balance for fiat users
- Crypto wallets for crypto users
- User chooses which to use

**Not recommended** - too complex!

## Recommended Approach: Option B

Here's what needs to be created:

### 1. Create Portfolio Table

```sql
CREATE TABLE `user_portfolios` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `amount` decimal(28,8) DEFAULT 0.00000000,
  `average_buy_price` decimal(28,8) DEFAULT 0.00000000,
  `total_invested_usd` decimal(28,8) DEFAULT 0.00000000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_currency` (`user_id`, `currency_id`),
  KEY `user_id` (`user_id`),
  KEY `currency_id` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Create UserPortfolio Model

```php
// core/app/Models/UserPortfolio.php
namespace App\Models;

class UserPortfolio extends Model
{
    protected $fillable = [
        'user_id',
        'currency_id',
        'amount',
        'average_buy_price',
        'total_invested_usd'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function currency() {
        return $this->belongsTo(Currency::class);
    }

    // Get current USD value
    public function getCurrentValueAttribute() {
        return $this->amount * $this->currency->rate;
    }

    // Get profit/loss
    public function getProfitLossAttribute() {
        return $this->current_value - $this->total_invested_usd;
    }

    // Get profit/loss percentage
    public function getProfitLossPercentageAttribute() {
        if ($this->total_invested_usd == 0) return 0;
        return (($this->current_value - $this->total_invested_usd) / $this->total_invested_usd) * 100;
    }
}
```

### 3. Update OrderController - Buy Logic

```php
// In OrderController::save() method - BUY side

if ($request->order_side == Status::BUY_SIDE_ORDER) {
    // Calculate USD cost
    $usdCost = $amount * $rate; // amount of coin × price per coin
    $charge = ($usdCost / 100) * $pair->percent_charge_for_buy;
    $totalUsdCost = $usdCost + $charge;

    // Check USD balance
    if ($user->usd_balance < $totalUsdCost) {
        return $this->response('Insufficient USD balance. Need $' . number_format($totalUsdCost, 2));
    }

    // Deduct USD
    $user->usd_balance -= $totalUsdCost;
    $user->save();

    // Add to portfolio
    $portfolio = UserPortfolio::firstOrCreate(
        ['user_id' => $user->id, 'currency_id' => $coin->id],
        ['amount' => 0, 'average_buy_price' => 0, 'total_invested_usd' => 0]
    );

    // Update average buy price
    $oldValue = $portfolio->total_invested_usd;
    $newValue = $oldValue + $usdCost;
    $portfolio->total_invested_usd = $newValue;
    $portfolio->amount += $amount;
    $portfolio->average_buy_price = $newValue / $portfolio->amount;
    $portfolio->save();

    // Create transaction
    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->wallet_id = 0;
    $transaction->amount = $totalUsdCost;
    $transaction->post_balance = $user->usd_balance;
    $transaction->charge = $charge;
    $transaction->trx_type = '-';
    $transaction->details = "Buy {$amount} {$coin->symbol} at \${$rate} each (Total: \${$usdCost})";
    $transaction->trx = getTrx();
    $transaction->remark = 'trade_buy';
    $transaction->save();

    // Create trade record
    // ... existing trade record code ...
}
```

### 4. Update OrderController - Sell Logic

```php
// In OrderController::save() method - SELL side

if ($request->order_side == Status::SELL_SIDE_ORDER) {
    // Check portfolio
    $portfolio = UserPortfolio::where('user_id', $user->id)
        ->where('currency_id', $coin->id)
        ->first();

    if (!$portfolio || $portfolio->amount < $amount) {
        return $this->response("You don't have {$amount} {$coin->symbol} to sell");
    }

    // Calculate USD received
    $usdReceived = $amount * $rate;
    $charge = ($usdReceived / 100) * $pair->percent_charge_for_sell;
    $netUsdReceived = $usdReceived - $charge;

    // Add USD to balance
    $user->usd_balance += $netUsdReceived;
    $user->save();

    // Remove from portfolio
    $portfolio->amount -= $amount;
    
    // Update total invested (proportional)
    $soldPercentage = $amount / ($portfolio->amount + $amount);
    $investmentSold = $portfolio->total_invested_usd * $soldPercentage;
    $portfolio->total_invested_usd -= $investmentSold;
    
    if ($portfolio->amount > 0) {
        $portfolio->average_buy_price = $portfolio->total_invested_usd / $portfolio->amount;
    } else {
        $portfolio->average_buy_price = 0;
        $portfolio->total_invested_usd = 0;
    }
    $portfolio->save();

    // Calculate profit/loss
    $profitLoss = $netUsdReceived - $investmentSold;
    $profitLossText = $profitLoss >= 0 ? "profit \${$profitLoss}" : "loss \${$profitLoss}";

    // Create transaction
    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->wallet_id = 0;
    $transaction->amount = $netUsdReceived;
    $transaction->post_balance = $user->usd_balance;
    $transaction->charge = $charge;
    $transaction->trx_type = '+';
    $transaction->details = "Sell {$amount} {$coin->symbol} at \${$rate} each (Total: \${$usdReceived}, {$profitLossText})";
    $transaction->trx = getTrx();
    $transaction->remark = 'trade_sell';
    $transaction->save();

    // Create trade record
    // ... existing trade record code ...
}
```

### 5. Update Dashboard to Show Portfolio

```php
// In UserController::home()

// Get user's portfolio
$portfolio = UserPortfolio::where('user_id', $user->id)
    ->with('currency')
    ->where('amount', '>', 0)
    ->get();

// Calculate total portfolio value in USD
$portfolioValue = $portfolio->sum('current_value');

return view('Template::user.dashboard', compact(
    'pageTitle', 'user', 'pairs', 'currencies', 'widget',
    'recentOrders', 'recentTransactions',
    'usdBalance', 'usdBalanceInOrder', 'totalUsdBalance',
    'portfolio', 'portfolioValue',
    'gateways', 'withdrawMethods'
));
```

### 6. Dashboard View - Portfolio Display

```blade
<!-- Add to dashboard.blade.php -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>@lang('Your Portfolio')</h5>
                <p class="text-muted">@lang('Crypto holdings valued in USD')</p>
            </div>
            <div class="card-body">
                @if($portfolio && $portfolio->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('Asset')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Avg Buy Price')</th>
                                    <th>@lang('Current Price')</th>
                                    <th>@lang('Current Value')</th>
                                    <th>@lang('Invested')</th>
                                    <th>@lang('P&L')</th>
                                    <th>@lang('P&L %')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($portfolio as $holding)
                                <tr>
                                    <td>
                                        <img src="{{ $holding->currency->image_url }}" width="24" height="24">
                                        {{ $holding->currency->symbol }}
                                    </td>
                                    <td>{{ showAmount($holding->amount, currencyFormat: false) }}</td>
                                    <td>${{ showAmount($holding->average_buy_price, currencyFormat: false) }}</td>
                                    <td>${{ showAmount($holding->currency->rate, currencyFormat: false) }}</td>
                                    <td>${{ showAmount($holding->current_value, currencyFormat: false) }}</td>
                                    <td>${{ showAmount($holding->total_invested_usd, currencyFormat: false) }}</td>
                                    <td class="{{ $holding->profit_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ showAmount($holding->profit_loss, currencyFormat: false) }}
                                    </td>
                                    <td class="{{ $holding->profit_loss_percentage >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ showAmount($holding->profit_loss_percentage, currencyFormat: false) }}%
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">@lang('Total Portfolio Value')</th>
                                    <th>${{ showAmount($portfolioValue, currencyFormat: false) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <p>@lang('You have no crypto holdings yet. Start trading to build your portfolio!')</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
```

## Files to Modify

1. **core/database/migrations/[new]_create_user_portfolios_table.php**
2. **core/app/Models/UserPortfolio.php** (new)
3. **core/app/Http/Controllers/User/OrderController.php**
4. **core/app/Http/Controllers/User/UserController.php**
5. **core/resources/views/templates/basic/user/dashboard.blade.php**

## Testing Checklist

- [ ] Buy crypto with USD balance
- [ ] Sell crypto and receive USD
- [ ] Check portfolio shows correct holdings
- [ ] Verify P&L calculations are accurate
- [ ] Test with multiple buys at different prices (average price calculation)
- [ ] Test selling partial amounts
- [ ] Check insufficient balance errors
- [ ] Verify transaction history is correct
- [ ] Test trading fees are calculated properly

## Next Steps

1. Create `user_portfolios` table
2. Create `UserPortfolio` model
3. Update `OrderController` buy logic
4. Update `OrderController` sell logic
5. Update dashboard to show portfolio
6. Test thoroughly!

---

**Note**: This is the recommended approach for USD-based trading. The portfolio system gives users clear visibility into their holdings while maintaining the simplicity of a single USD balance.

