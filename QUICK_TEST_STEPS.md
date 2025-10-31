# ⚡ QUICK TEST STEPS - Copy & Paste Ready!

## 🚀 STEP-BY-STEP: Test Everything in 10 Minutes

---

## STEP 1: UPDATE CURRENCY RATES ⚙️

**Copy and run this SQL in your database:**

```sql
-- Set realistic currency rates
UPDATE currencies SET rate = 115047.40 WHERE symbol = 'BTC';
UPDATE currencies SET rate = 4219.09 WHERE symbol = 'ETH';
UPDATE currencies SET rate = 1.00 WHERE symbol = 'USDT';
UPDATE currencies SET rate = 1.00 WHERE symbol = 'USDC';
UPDATE currencies SET rate = 0.62 WHERE symbol = 'XRP';
UPDATE currencies SET rate = 655.50 WHERE symbol = 'BNB';
UPDATE currencies SET rate = 180.25 WHERE symbol = 'SOL';
UPDATE currencies SET rate = 2850.00 WHERE symbol = 'LTC';
```

---

## STEP 2: GIVE YOURSELF TEST USD BALANCE 💰

**Quick setup - Add USD directly (for testing):**

```sql
-- Replace YOUR_USER_ID with your actual user ID (usually 1, 2, 3, etc.)
UPDATE users SET usd_balance = 1000.00 WHERE id = YOUR_USER_ID;

-- Record a test deposit conversion
INSERT INTO currency_conversions (user_id, currency_id, currency_symbol, conversion_type, crypto_amount, usd_amount, conversion_rate, trx, created_at, updated_at)
VALUES (YOUR_USER_ID, 1, 'BTC', 'deposit', 0.01, 1000.00, 100000, 'TEST001', NOW(), NOW());
```

**Find your user ID:**
```sql
SELECT id, username, email, usd_balance FROM users WHERE username = 'your_username';
```

---

## STEP 3: LOGIN & CHECK DASHBOARD 👀

1. **Login to your account:**
   ```
   URL: http://127.0.0.1:8000/login
   ```

2. **Go to Dashboard:**
   ```
   URL: http://127.0.0.1:8000/user/dashboard
   ```

3. **You should see:**
   ```
   ✅ USD Balance: $1,000.00 USD
   ✅ Available: $1,000.00
   ✅ In Orders: $0.00
   ✅ Portfolio: Empty (no holdings yet)
   ✅ NO individual crypto wallets shown
   ```

---

## STEP 4: TEST BUY CRYPTO (SPOT TRADING) 📈

### Option A: Using Trade Page

1. **Navigate to Trading:**
   ```
   URL: http://127.0.0.1:8000/user/trade
   Or click "Trade" or "Spot Trading" in menu
   ```

2. **Select Trading Pair:**
   ```
   Choose: ETH/USDT or BTC/USDT
   ```

3. **Place BUY Order:**
   ```
   Side: BUY
   Amount: 0.01 ETH (or 0.0001 BTC)
   Price: Market price
   ```

4. **Confirm the order**

5. **Expected Result:**
   ```
   ✅ USD Balance decreased
   ✅ Portfolio shows: 0.01 ETH
   ✅ Transaction recorded
   ```

### Option B: Using API/Direct Route (If available)

```
POST to: /user/usd-trade/order/ETH_USDT

Data:
{
  "amount": 0.01,
  "rate": 4219.09,
  "order_side": 1,  // 1 = BUY
  "order_type": 2   // 2 = MARKET
}
```

---

## STEP 5: CHECK YOUR PORTFOLIO 📊

1. **Go back to Dashboard:**
   ```
   URL: http://127.0.0.1:8000/user/dashboard
   ```

2. **You should see Portfolio Table:**
   ```
   ┌────────┬─────────┬───────────┬─────────────┬──────┐
   │ Asset  │ Amount  │ Avg Buy   │ Current Val │ P&L  │
   ├────────┼─────────┼───────────┼─────────────┼──────┤
   │ ETH    │ 0.01    │ $4,219.09 │ $42.19      │ $0   │
   └────────┴─────────┴───────────┴─────────────┴──────┘
   ```

3. **Verify:**
   ```
   ✅ Shows your crypto holdings
   ✅ Shows average buy price
   ✅ Shows current value
   ✅ Shows profit/loss
   ```

---

## STEP 6: TEST SELL CRYPTO 📉

### Simulate Price Increase (Optional):
```sql
-- Make ETH price go up to $4,500
UPDATE currencies SET rate = 4500.00 WHERE symbol = 'ETH';

-- Clear cache to see new price
-- Run in terminal: php artisan cache:clear
```

### Sell Your ETH:

1. **Go to Trading Page:**
   ```
   Select: ETH/USDT pair
   ```

2. **Place SELL Order:**
   ```
   Side: SELL
   Amount: 0.005 ETH (half of your holdings)
   Price: Market price
   ```

3. **Confirm the order**

4. **Expected Result:**
   ```
   ✅ USD Balance increased
   ✅ Portfolio now shows: 0.005 ETH (remaining)
   ✅ You made profit if price went up!
   ```

---

## STEP 7: TEST WITHDRAWAL 💸

1. **Go to Withdraw Page:**
   ```
   URL: http://127.0.0.1:8000/user/withdraw
   Or click "Withdraw" in menu
   ```

2. **Select Cryptocurrency:**
   ```
   Choose: BTC (or any crypto)
   ```

3. **Enter Amount:**
   ```
   Amount: 0.001 BTC (small test amount)
   ```

4. **System will calculate:**
   ```
   BTC Rate: $115,047.40
   USD Needed: 0.001 × $115,047.40 = $115.05
   Fee: ~$2-3 (depends on your settings)
   Total: ~$117-118 USD
   
   ✅ Check: Do you have enough USD? 
   ```

5. **Enter Your BTC Address:**
   ```
   Enter a valid BTC address (your test wallet)
   Network: Bitcoin
   ```

6. **Submit Withdrawal Request**

7. **Expected Result:**
   ```
   ✅ Request submitted successfully
   ✅ USD balance decreased by ~$117
   ✅ Status: Pending approval
   ✅ Rate locked at $115,047.40
   ```

---

## STEP 8: ADMIN APPROVAL (Test as Admin) 👨‍💼

1. **Login as Admin:**
   ```
   URL: http://127.0.0.1:8000/admin/login
   Admin username/password
   ```

2. **Go to Pending Withdrawals:**
   ```
   Navigate: Admin Panel → Withdrawals → Pending
   ```

3. **View Withdrawal Details:**
   ```
   You'll see:
   - User: your_username
   - Crypto Amount: 0.001 BTC ← SEND THIS EXACT AMOUNT!
   - USD Deducted: $115.05
   - Conversion Rate: $115,047.40 (LOCKED)
   - User Address: [provided address]
   ```

4. **Approve/Process:**
   ```
   - Click "Approve" or "Process"
   - (In real scenario: send 0.001 BTC to user's address)
   - Mark as completed
   ```

---

## ✅ VERIFICATION COMMANDS

**Check your balances in database:**

```sql
-- Check USD balance
SELECT 
    id,
    username, 
    usd_balance, 
    usd_balance_in_order 
FROM users 
WHERE id = YOUR_USER_ID;

-- Check portfolio
SELECT 
    p.*,
    c.symbol,
    c.name,
    p.amount,
    p.average_buy_price,
    p.total_invested_usd
FROM user_portfolios p
JOIN currencies c ON p.currency_id = c.id
WHERE p.user_id = YOUR_USER_ID;

-- Check conversions (last 5)
SELECT 
    id,
    currency_symbol,
    conversion_type,
    crypto_amount,
    usd_amount,
    conversion_rate,
    created_at
FROM currency_conversions 
WHERE user_id = YOUR_USER_ID 
ORDER BY id DESC 
LIMIT 5;

-- Check transactions (last 5)
SELECT 
    id,
    amount,
    post_balance,
    trx_type,
    details,
    created_at
FROM transactions 
WHERE user_id = YOUR_USER_ID 
ORDER BY id DESC 
LIMIT 5;

-- Check withdrawals
SELECT 
    id,
    amount as total_usd,
    usd_amount,
    crypto_amount,
    conversion_rate,
    currency,
    status,
    created_at
FROM withdrawals 
WHERE user_id = YOUR_USER_ID 
ORDER BY id DESC;
```

---

## 🎯 EXPECTED RESULTS SUMMARY

### After All Tests:

**Starting:**
- USD Balance: $1,000.00
- Portfolio: Empty

**After Buy:**
- USD Balance: ~$958 (bought 0.01 ETH for ~$42)
- Portfolio: 0.01 ETH

**After Sell:**
- USD Balance: ~$980 (sold 0.005 ETH for ~$22)
- Portfolio: 0.005 ETH (remaining)
- Profit: Made some profit if price increased!

**After Withdrawal:**
- USD Balance: ~$863 (withdrew 0.001 BTC costing ~$117)
- Portfolio: 0.005 ETH (unchanged)
- Pending: 0.001 BTC withdrawal

---

## 🐛 QUICK TROUBLESHOOTING

### Issue: Dashboard shows error
```bash
# Clear cache
cd C:\Users\amaiz\Documents\git\bigbuller\Files\core
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Currency rates not updating
```sql
-- Verify rates
SELECT symbol, name, rate FROM currencies WHERE symbol IN ('BTC', 'ETH', 'USDT');

-- If rate is 0 or NULL, update them (see STEP 1)
```

### Issue: Can't buy/sell
```
1. Check USD balance: SELECT usd_balance FROM users WHERE id = YOUR_ID;
2. Check currency rates are set (not 0)
3. Clear cache
4. Check browser console for errors (F12)
```

### Issue: Withdrawal not working
```
1. Verify USD balance is sufficient
2. Check withdrawals table exists:
   SHOW TABLES LIKE 'withdrawals';
3. Verify currency rate is set
4. Check withdrawal methods are enabled in admin
```

---

## 📱 WHAT TO LOOK FOR

### ✅ Dashboard Should Show:
- Single USD balance (NOT multiple crypto wallets)
- Portfolio table with your holdings
- Profit/Loss for each asset
- Clean, simple interface

### ✅ Transactions Should Show:
- "Deposit: $X USD from Y crypto"
- "Buy: X crypto for $Y USD"
- "Sell: X crypto for $Y USD (Profit: $Z)"
- "Withdraw: $X USD → Y crypto"

### ✅ Portfolio Should Show:
- Asset symbol (BTC, ETH, etc.)
- Amount you own
- Average buy price
- Current value
- Profit/Loss ($ and %)

### ❌ Should NOT See:
- Individual crypto wallet balances (0.001 BTC, 0.5 ETH, etc.)
- "Spot Wallet", "Funding Wallet", etc.
- Multiple wallet tabs

---

## 🎊 SUCCESS CHECKLIST

- [ ] Dashboard loads without errors
- [ ] Shows USD balance instead of crypto wallets
- [ ] Can buy crypto with USD
- [ ] Portfolio appears after buying
- [ ] Can sell crypto back to USD
- [ ] Profit/Loss calculates correctly
- [ ] Can request withdrawal
- [ ] Withdrawal checks USD balance
- [ ] Admin can see withdrawal details
- [ ] All conversions are recorded

---

## 🚀 READY TO TEST?

**Follow steps 1-8 in order!**

1. Update currency rates ✅
2. Add test USD balance ✅
3. Login & check dashboard ✅
4. Buy some crypto ✅
5. Check portfolio ✅
6. Sell some crypto ✅
7. Test withdrawal ✅
8. Admin approval ✅

**Total time: ~10 minutes**

---

**Need help?** Check `USER_TESTING_GUIDE.md` for detailed explanations!

**Last Updated:** October 27, 2025  
**Status:** Ready to test!

🎯 **Start Testing Now!** 🎯

