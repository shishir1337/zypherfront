# Zero Rate Order Bug - Investigation & Fix

## Issue Summary

**Date:** October 29, 2025  
**Affected User:** username123 (User ID: 13)  
**Severity:** HIGH - Allowed free trading due to zero market price

## Problem Description

A critical bug was discovered where users could place orders when the market price was `0.00`, resulting in:
- **No USDT deduction** from their balance
- **Free coin acquisition** (received BNB without paying)
- **Incorrect transaction records** showing rate as 0.00000000

### Affected Transaction Details

**Order ID 43:**
- **Amount:** 0.0091 BNB
- **Rate:** 0.00000000 ← **ZERO RATE!**
- **Total:** 0.00000000
- **Charge:** 0.00000000
- **Status:** Completed
- **Created:** 2025-10-29 22:50:59

**Transaction ID 165 (USDT Deduction):**
- **Amount Deducted:** 0.00000000 ← **SHOULD HAVE BEEN ~10 USDT**
- **Post Balance:** 1000.00000000 (unchanged)
- **Details:** "Buy 0.0091 BNB on BNB_USDT pair at 0.00000000"

**Transaction ID 166 (BNB Credit):**
- **Amount Received:** 0.0091 BNB ← User got free BNB
- **Details:** "Received 0.0091 BNB from instant buy"

### Current User Balances

- **USDT Balance:** 849.84646264
- **BNB Balance:** 0.14550000
  - Includes 0.0091 BNB acquired for free
  - Includes 0.1364 BNB from valid second order

## Root Cause Analysis

### Technical Cause

In `OrderController.php` and `Api/OrderController.php`, the market order logic was:

```php
if ($request->order_type == Status::ORDER_TYPE_MARKET) {
    $rate = $pair->marketData->price;
} else {
    $rate = $request->rate;
}

$totalAmount = $amount * $rate;  // If $rate is 0, totalAmount becomes 0!
```

**Why was the price 0?**

The `market_data` table showed:
- **Current Price:** 1100.83238532
- **Last Price:** 0.00000000 ← This was the price before the update

**Timeline:**
1. Market data was not initialized or was at 0
2. User placed first order → Got free BNB
3. Price update cron job ran → Price updated to 1100.83238532
4. User placed second order → Correctly charged

### Vulnerability Window

This issue occurs when:
1. New trading pairs are added before market data is populated
2. Market data price updates fail or are delayed
3. System restart before market data initialization
4. External API failures causing price data to be 0

## Solution Implemented

### 1. Added Market Price Validation

**File:** `core/app/Http/Controllers/User/OrderController.php`

```php
if ($request->order_type == Status::ORDER_TYPE_MARKET) {
    // Validate market data exists and has valid price
    if (!$pair->marketData) {
        return $this->response('Market data is not available for this pair. Please try again later.');
    }
    if ($pair->marketData->price <= 0) {
        return $this->response('Market price is currently unavailable. Please try again in a moment.');
    }
    $rate = $pair->marketData->price;
} else {
    $rate = $request->rate;
}

// Additional validation: ensure rate is always positive
if ($rate <= 0) {
    return $this->response('Invalid price rate. Please check and try again.');
}
```

### 2. Applied Same Fix to API Controller

**File:** `core/app/Http/Controllers/Api/OrderController.php`

Added identical validation for API endpoints.

### 3. Balance Correction Script

**File:** `core/fix_zero_rate_order.php`

Created an administrative script to:
- Identify zero-rate orders
- Calculate correct costs based on current market price
- Deduct the appropriate amount from user's balance
- Update order records
- Create correcting transactions

## How to Fix Affected User

### Option 1: Run the Correction Script (Recommended)

```bash
cd core
php fix_zero_rate_order.php
```

This will:
1. Find Order ID 43 with zero rate
2. Calculate the correct cost (~10 USDT based on current BNB price)
3. Prompt for confirmation
4. Deduct the correct amount from user's USDT balance
5. Update the order with correct rate and total
6. Create a correction transaction

### Option 2: Manual Database Fix

If you prefer manual correction:

```sql
-- First, check current state
SELECT * FROM orders WHERE id = 43;
SELECT * FROM wallets WHERE user_id = 13 AND currency_id = 3; -- USDT wallet

-- Assuming BNB price should be ~1100 USDT and amount was 0.0091
-- Cost should be approximately: 0.0091 * 1100 = 10.01 USDT

-- Update the order
UPDATE orders 
SET rate = 1100.00000000, 
    total = 10.01000000,
    charge = 0.00000000
WHERE id = 43;

-- Deduct from USDT balance
UPDATE wallets 
SET balance = balance - 10.01
WHERE user_id = 13 AND currency_id = 3 AND wallet_type = 1;

-- Insert correction transaction
INSERT INTO transactions 
(user_id, wallet_id, amount, post_balance, charge, trx_type, details, trx, remark, created_at, updated_at)
VALUES 
(13, [wallet_id], 10.01, [new_balance], 0, '-', 'Balance correction for zero-rate order #43', '[trx_id]', 'balance_correction', NOW(), NOW());
```

### Option 3: Leave As-Is with Note

If you decide not to correct:
- Document this as a known issue
- User effectively got 0.0091 BNB for free (~$10 value)
- Monitor for abuse or additional occurrences

## Prevention Measures

### Immediate Actions ✅

1. ✅ **Added validation** - Orders cannot be placed with zero or invalid rates
2. ✅ **Added market data checks** - System verifies price data exists and is valid
3. ✅ **Multiple validation layers** - Rate validated both before and after calculation

### Recommended Actions

1. **Initialize Market Data on Pair Creation**
   - Ensure all new pairs get market data populated immediately
   - Add migration to populate missing market data

2. **Add Monitoring**
   - Alert when market data prices are 0 or stale
   - Log when price updates fail

3. **Add Admin Dashboard Alert**
   - Show warning when pairs have invalid market data
   - List orders with suspicious rates (0 or extremely low)

4. **Audit All Orders**
   - Run query to find any other zero-rate orders:
   ```sql
   SELECT * FROM orders WHERE rate = 0 AND status = 1;
   ```

5. **Add Cron Job Health Check**
   - Verify market data updates are running successfully
   - Alert if prices haven't updated in X minutes

## Testing the Fix

### Test Case 1: Valid Market Order
```bash
# Ensure market data has valid price
# Place a market order
# ✓ Should execute successfully with correct rate
```

### Test Case 2: Zero Price Prevention
```bash
# Manually set market_data.price to 0
UPDATE market_data SET price = 0 WHERE pair_id = 5;

# Try to place market order
# ✓ Should receive error: "Market price is currently unavailable"
```

### Test Case 3: Missing Market Data
```bash
# Delete market data
DELETE FROM market_data WHERE pair_id = 5;

# Try to place market order
# ✓ Should receive error: "Market data is not available for this pair"
```

## Impact Assessment

### Financial Impact
- **User gained:** ~0.0091 BNB ≈ $10.01 (based on current market price)
- **Platform loss:** Same amount

### Other Affected Users
To check if other users were affected:

```sql
-- Find all zero-rate completed orders
SELECT o.id, o.user_id, u.username, o.amount, o.rate, o.total, o.created_at
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.rate = 0 AND o.status = 1
ORDER BY o.created_at DESC;
```

## Files Modified

1. `core/app/Http/Controllers/User/OrderController.php` - Added validation
2. `core/app/Http/Controllers/Api/OrderController.php` - Added validation
3. `core/fix_zero_rate_order.php` - New correction script
4. `core/check_market_data.php` - New debugging script

## Verification Steps

After applying the fix:

1. ✅ Verify no orders can be placed with zero rate
2. ✅ Check error messages display correctly
3. ✅ Test both web and API endpoints
4. ✅ Verify limit orders still work (not affected by this fix)
5. ⏳ Apply balance correction if decided
6. ⏳ Audit for other affected orders
7. ⏳ Monitor market data updates

## Conclusion

This bug was caused by a lack of validation when market price data was zero or invalid. The fix prevents future occurrences by:

1. **Validating market data exists** before using it
2. **Checking price is positive** before calculating order cost
3. **Multiple validation layers** to catch edge cases

The affected user received approximately $10 worth of BNB for free. A correction script has been provided to fix their balance if desired.

**Status:** 🟢 **FIXED** - No new zero-rate orders can be placed  
**Correction:** 🟡 **PENDING** - Admin decision needed on balance correction

---

**Next Steps:**
1. Decide whether to correct affected user's balance
2. Run audit query to check for other affected orders
3. Implement market data monitoring
4. Add market data initialization for new pairs

