# Withdrawal System USD Update Guide

## Current Withdrawal System

- User selects crypto wallet (e.g., BTC wallet)
- Withdraws directly from BTC balance
- System sends BTC to external address

## New USD-Based Withdrawal System

- User has USD balance only
- User requests withdrawal in crypto (e.g., "Withdraw 0.001 BTC")
- System converts USD to crypto at current rate
- Sends crypto to user's address

## How It Should Work

### Example Withdrawal Flow

**User's USD Balance**: $500.00

**User Action**: Withdraw 0.001 BTC

**Current BTC Rate**: $120,000 per BTC

**Process**:

1. **Calculate USD Equivalent**:
   - 0.001 BTC × $120,000 = $120 USD

2. **Add Withdrawal Fees**:
   - Fixed fee: $5
   - Percentage fee: 1% of $120 = $1.20
   - **Total fee**: $6.20
   - **Total deduction**: $120 + $6.20 = **$126.20**

3. **Check Balance**:
   - User has $500, needs $126.20 ✅
   - Proceed with withdrawal

4. **Process Withdrawal**:
   - Deduct $126.20 from `user.usd_balance`
   - Convert $120 to 0.001 BTC at current rate
   - Send 0.001 BTC to user's crypto address
   - Record conversion in `currency_conversions` table

5. **Create Records**:
   - Withdrawal record (status: pending → approved → success)
   - Transaction showing USD deduction
   - Currency conversion record

**Result**: User receives 0.001 BTC, balance reduced by $126.20

## Important Considerations

### Price Volatility Risk

**Problem**: Crypto prices change constantly

**Scenario**:
1. User deposits 0.001 BTC when price is $115,000 = $115 USD
2. Price drops to $110,000
3. User withdraws 0.001 BTC = costs $110 USD
4. User "profits" $5 from price drop

OR

1. User deposits 0.001 BTC when price is $115,000 = $115 USD
2. Price rises to $120,000  
3. User withdraws 0.001 BTC = costs $120 USD
4. User gets less BTC than deposited

**Solution**: 
- Clearly communicate this to users
- Show withdrawal preview with current rate
- Lock rate for X minutes after user confirms
- Consider minimum/maximum withdrawal amounts

### Rate Locking

Implement a rate lock system:

```php
// When user initiates withdrawal
$withdrawal->locked_rate = $currency->rate;
$withdrawal->rate_locked_until = now()->addMinutes(10);
$withdrawal->save();

// When processing withdrawal
if (now() > $withdrawal->rate_locked_until) {
    // Rate expired, recalculate
    $newRate = Currency::find($withdrawal->currency_id)->rate;
    $withdrawal->locked_rate = $newRate;
    // Inform user of rate change
}
```

## Implementation

### 1. Update Withdrawal Request

File: `core/app/Http/Controllers/User/WithdrawController.php`

```php
public function withdrawStore(Request $request)
{
    $request->validate([
        'method_code'    => 'required',
        'amount'         => 'required|numeric',
        'wallet_address' => 'required' // For crypto withdrawals
    ]);

    $method = WithdrawMethod::where('id', $request->method_code)->where('status', Status::ENABLE)->firstOrFail();
    $user = auth()->user();

    // Get the currency for this withdrawal method
    $currency = Currency::where('symbol', $method->currency)->active()->first();
    
    if (!$currency) {
        return returnBack('Currency not found or inactive');
    }

    // User wants to withdraw X crypto
    $cryptoAmount = $request->amount;
    
    // Get current rate
    $conversionRate = $currency->rate;
    
    if ($conversionRate <= 0) {
        return returnBack("Invalid conversion rate for {$currency->symbol}");
    }

    // Calculate USD equivalent
    $usdEquivalent = $cryptoAmount * $conversionRate;

    // Check minimum/maximum limits (convert to USD)
    $minWithdrawal = $method->min_limit * $conversionRate;
    $maxWithdrawal = $method->max_limit * $conversionRate;

    if ($usdEquivalent < $minWithdrawal) {
        return returnBack("Minimum withdrawal is " . showAmount($method->min_limit) . " {$currency->symbol}");
    }

    if ($usdEquivalent > $maxWithdrawal) {
        return returnBack("Maximum withdrawal is " . showAmount($method->max_limit) . " {$currency->symbol}");
    }

    // Calculate fees (in USD)
    $fixedCharge = $method->fixed_charge;
    $percentCharge = ($usdEquivalent * $method->percent_charge) / 100;
    $totalCharge = $fixedCharge + $percentCharge;
    $totalUsdDeduction = $usdEquivalent + $totalCharge;

    // Check user's USD balance
    if ($user->usd_balance < $totalUsdDeduction) {
        return returnBack("Insufficient USD balance. You need \${$totalUsdDeduction} but have \${$user->usd_balance}");
    }

    // Create withdrawal record
    $withdrawal = new Withdrawal();
    $withdrawal->user_id = $user->id;
    $withdrawal->method_id = $method->id;
    $withdrawal->currency_id = $currency->id;
    $withdrawal->crypto_amount = $cryptoAmount;
    $withdrawal->usd_amount = $usdEquivalent;
    $withdrawal->conversion_rate = $conversionRate;
    $withdrawal->rate_locked_until = now()->addMinutes(10); // Lock rate for 10 mins
    $withdrawal->charge = $totalCharge;
    $withdrawal->final_amount = $cryptoAmount; // Crypto to send
    $withdrawal->amount = $usdEquivalent; // USD being withdrawn
    $withdrawal->wallet_address = $request->wallet_address;
    $withdrawal->trx = getTrx();
    $withdrawal->status = Status::PAYMENT_PENDING;
    $withdrawal->save();

    // Don't deduct balance yet - wait for admin approval
    session()->put('wtrx', $withdrawal->trx);
    
    return to_route('user.withdraw.preview');
}
```

### 2. Withdrawal Approval (Admin Side)

File: `core/app/Http/Controllers/Admin/WithdrawController.php`

```php
public function approve(Request $request)
{
    $request->validate([
        'id' => 'required|integer'
    ]);

    $withdraw = Withdrawal::where('id', $request->id)->where('status', Status::PAYMENT_PENDING)->firstOrFail();
    $user = User::find($withdraw->user_id);
    $currency = $withdraw->currency;

    // Check if rate lock expired
    if (now() > $withdraw->rate_locked_until) {
        $notify[] = ['warning', 'Rate lock expired. Recalculating with current rate.'];
        
        // Recalculate with current rate
        $newRate = $currency->rate;
        $newUsdAmount = $withdraw->crypto_amount * $newRate;
        $newCharge = ($newUsdAmount * $withdraw->method->percent_charge / 100) + $withdraw->method->fixed_charge;
        $newTotalUsd = $newUsdAmount + $newCharge;

        $withdraw->conversion_rate = $newRate;
        $withdraw->usd_amount = $newUsdAmount;
        $withdraw->charge = $newCharge;
        $withdraw->amount = $newUsdAmount;
        $withdraw->save();
    }

    $totalUsdDeduction = $withdraw->usd_amount + $withdraw->charge;

    // Check balance again
    if ($user->usd_balance < $totalUsdDeduction) {
        $notify[] = ['error', 'User has insufficient USD balance'];
        return back()->withNotify($notify);
    }

    // Deduct USD from user's balance
    $user->usd_balance -= $totalUsdDeduction;
    $user->save();

    // Update withdrawal status
    $withdraw->status = Status::PAYMENT_SUCCESS;
    $withdraw->admin_feedback = $request->details ?? 'Withdrawal approved';
    $withdraw->save();

    // Record the conversion
    \App\Services\CurrencyConversionService::recordConversion(
        $user,
        $currency,
        $withdraw->crypto_amount,
        $withdraw->usd_amount,
        'withdrawal',
        $withdraw->trx,
        "Withdrawal: ${$withdraw->usd_amount} USD converted to {$withdraw->crypto_amount} {$currency->symbol}"
    );

    // Create transaction
    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->wallet_id = 0;
    $transaction->amount = $totalUsdDeduction;
    $transaction->post_balance = $user->usd_balance;
    $transaction->charge = $withdraw->charge;
    $transaction->trx_type = '-';
    $transaction->details = "Withdrawal: {$withdraw->crypto_amount} {$currency->symbol} (\${$withdraw->usd_amount} USD at rate \${$withdraw->conversion_rate})";
    $transaction->trx = $withdraw->trx;
    $transaction->remark = 'withdrawal';
    $transaction->save();

    // Notify user
    notify($user, 'WITHDRAW_APPROVE', [
        'method_name' => $withdraw->method->name,
        'method_currency' => $currency->symbol,
        'method_amount' => showAmount($withdraw->crypto_amount),
        'amount' => showAmount($withdraw->usd_amount),
        'charge' => showAmount($withdraw->charge),
        'rate' => showAmount($withdraw->conversion_rate),
        'trx' => $withdraw->trx,
        'post_balance' => showAmount($user->usd_balance)
    ]);

    // Send actual crypto (integrate with blockchain/payment gateway)
    // $this->sendCrypto($withdraw);

    $notify[] = ['success', 'Withdrawal approved successfully'];
    return back()->withNotify($notify);
}
```

### 3. Update Withdrawal Migration

Add new fields to `withdrawals` table:

```php
Schema::table('withdrawals', function (Blueprint $table) {
    $table->decimal('crypto_amount', 28, 8)->default(0)->after('amount');
    $table->decimal('usd_amount', 28, 8)->default(0)->after('crypto_amount');
    $table->decimal('conversion_rate', 28, 8)->default(0)->after('usd_amount');
    $table->timestamp('rate_locked_until')->nullable()->after('conversion_rate');
    $table->string('wallet_address')->nullable()->after('rate_locked_until');
});
```

### 4. Withdrawal Preview Page

Show user clear breakdown:

```blade
<!-- withdrawal_preview.blade.php -->
<div class="card">
    <div class="card-header">
        <h4>@lang('Withdrawal Preview')</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>@lang('Important'):</strong>
            @lang('You will receive') <strong>{{ $withdrawal->crypto_amount }} {{ $currency->symbol }}</strong>
            @lang('at the current rate of') <strong>${{ $withdrawal->conversion_rate }}</strong>
        </div>

        <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between">
                <span>@lang('Crypto Amount')</span>
                <strong>{{ $withdrawal->crypto_amount }} {{ $currency->symbol }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>@lang('Current Rate')</span>
                <strong>${{ showAmount($withdrawal->conversion_rate) }} / {{ $currency->symbol }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>@lang('USD Equivalent')</span>
                <strong>${{ showAmount($withdrawal->usd_amount) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>@lang('Withdrawal Fee')</span>
                <strong>${{ showAmount($withdrawal->charge) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between bg-light">
                <span><strong>@lang('Total USD Deducted')</strong></span>
                <strong class="text-danger">${{ showAmount($withdrawal->usd_amount + $withdrawal->charge) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>@lang('Your Balance After')</span>
                <strong>${{ showAmount(auth()->user()->usd_balance - ($withdrawal->usd_amount + $withdrawal->charge)) }}</strong>
            </li>
        </ul>

        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            @lang('Rate locked for 10 minutes. Complete withdrawal before rate expires.')
        </div>

        <form action="{{ route('user.withdraw.submit') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary w-100">
                @lang('Confirm Withdrawal')
            </button>
        </form>
    </div>
</div>
```

## Key Points

### User Communication

**Very Important**: Users must understand:

1. **Deposits**: "Your BTC is converted to USD at current rate"
2. **Withdrawals**: "Your USD is converted to BTC at current rate"
3. **Risk**: "You may receive more or less crypto than you deposited due to price changes"

### Example User Journey

```
Day 1:
- User deposits 0.001 BTC
- BTC price: $115,000
- User gets: $115 USD balance

Day 5:
- BTC price rises to $120,000
- User wants to withdraw 0.001 BTC
- Costs: $120 USD
- User pays $5 more than deposited

OR

Day 5:
- BTC price drops to $110,000
- User withdraws 0.001 BTC
- Costs: $110 USD
- User has $5 left over
```

## Files to Modify

1. **core/app/Http/Controllers/User/WithdrawController.php**
2. **core/app/Http/Controllers/Admin/WithdrawController.php**
3. **core/database/migrations/[new]_add_crypto_fields_to_withdrawals.php**
4. **core/resources/views/templates/basic/user/withdraw/**.blade.php

## Testing Checklist

- [ ] User can request crypto withdrawal with USD balance
- [ ] USD equivalent calculated correctly
- [ ] Fees calculated properly
- [ ] Rate lock works (expires after 10 mins)
- [ ] Insufficient balance error shows
- [ ] Withdrawal preview displays correctly
- [ ] Admin can approve/reject
- [ ] USD deducted on approval
- [ ] Conversion recorded in database
- [ ] Transaction created correctly
- [ ] User notified properly

## Summary

The USD-based withdrawal system:
- ✅ Maintains simplicity (single USD balance)
- ✅ Allows users to withdraw in crypto
- ✅ Tracks conversions properly
- ⚠️ Requires clear user communication about rate changes
- ⚠️ Needs rate locking mechanism
- ⚠️ Should have good preview/confirmation flow

---

**Next Step**: After implementing trading and withdrawal systems, the USD-based account will be fully functional!

