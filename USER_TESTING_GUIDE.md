# 🧪 USER TESTING GUIDE - Complete Walkthrough

## 🎯 Test Scenario: Deposit → Trade → Withdraw

This guide will help you test the complete USD-based system as a regular user.

---

## 📋 PREREQUISITES

### Before You Start:
1. ✅ All migrations completed (done!)
2. ✅ Currency rates updated in database
3. ✅ Test user account created
4. ✅ You have some test crypto to deposit

### Update Currency Rates First:
```sql
-- Run this in your database to set realistic rates
UPDATE currencies SET rate = 115047.40 WHERE symbol = 'BTC';
UPDATE currencies SET rate = 4219.09 WHERE symbol = 'ETH';
UPDATE currencies SET rate = 1.00 WHERE symbol = 'USDT';
UPDATE currencies SET rate = 1.00 WHERE symbol = 'USDC';
UPDATE currencies SET rate = 0.62 WHERE symbol = 'XRP';
UPDATE currencies SET rate = 655.50 WHERE symbol = 'BNB';
```

---

## 🧪 TEST SCENARIO 1: COMPLETE FLOW (Recommended)

### Starting Point:
- **User:** test@example.com (or your test user)
- **USD Balance:** $0
- **Portfolio:** Empty

---

## STEP 1: DEPOSIT CRYPTO 💰

### Action: Deposit Bitcoin

1. **Login** to your user account
   ```
   URL: http://127.0.0.1:8000/login
   Username: your_test_user
   Password: your_password
   ```

2. **Go to Deposit Page**
   ```
   Click: "Deposit" or "Add Funds"
   Or navigate to: /user/deposit
   ```

3. **Select Cryptocurrency**
   ```
   Choose: BTC (Bitcoin)
   ```

4. **Get Deposit Address**
   ```
   - System shows you a BTC deposit address
   - Copy the address
   ```

5. **Send Test Amount**
   ```
   Send: 0.001 BTC (or any small amount)
   To: The address provided
   ```

6. **Wait for Confirmation**
   ```
   - Wait for blockchain confirmation (or use admin to confirm manually)
   - System will detect the deposit
   ```

### Expected Result:
```
✅ Deposit detected: 0.001 BTC
✅ Conversion: 0.001 × $115,047 = $115.05 USD
✅ Your USD Balance: $115.05
✅ Dashboard shows: "$115.05 USD" (NOT "0.001 BTC")
✅ Conversion recorded in currency_conversions table
```

### Verify:
- **Dashboard:** Should show "USD Balance: $115.05"
- **Transaction History:** Should show "Deposit: $115.05 USD from 0.001 BTC"
- **No BTC Wallet:** Should NOT see individual BTC wallet balance

---

## STEP 2: TRADE - BUY CRYPTO (SPOT) 📈

### Action: Buy Ethereum with USD

1. **Go to Trading/Spot Trading**
   ```
   Navigate to: Spot Trading page
   Select Pair: ETH/USDT or ETH/BTC
   ```

2. **Place BUY Order**
   ```
   Market: Spot
   Type: Market Order (or Limit)
   Side: BUY
   Amount: 0.01 ETH
   Price: $4,219 (current ETH price)
   ```

3. **Calculate Cost**
   ```
   Cost = 0.01 × $4,219 = $42.19
   Fee (0.5%) = $0.21
   Total = $42.40 USD
   ```

4. **Confirm Order**
   ```
   - System checks: USD Balance >= $42.40 ✓
   - Click "Buy ETH"
   - Confirm transaction
   ```

### Expected Result:
```
✅ Order executed successfully
✅ USD Balance: $115.05 - $42.40 = $72.65
✅ Portfolio: +0.01 ETH (invested $42.19)
✅ Dashboard shows:
   - USD Balance: $72.65
   - Portfolio: 0.01 ETH ($42.19 invested, Current: $42.19, P&L: $0)
```

### Verify:
- **Dashboard Portfolio Table:**
  ```
  Asset  | Amount | Avg Buy Price | Current Price | P&L    | P&L %
  ETH    | 0.01   | $4,219       | $4,219        | $0.00  | 0%
  ```
- **Transaction History:** "Buy Order: 0.01 ETH for $42.40 USD"

---

## STEP 3: TRADE - SELL CRYPTO (SPOT) 📉

### Action: Sell Some Ethereum Back to USD

Let's assume ETH price rose to $4,500 (you can simulate this by updating the rate)

```sql
-- Simulate price increase (optional for testing)
UPDATE currencies SET rate = 4500 WHERE symbol = 'ETH';
```

1. **Go to Trading Page**
   ```
   Select: ETH/USDT pair
   ```

2. **Place SELL Order**
   ```
   Market: Spot
   Type: Market Order
   Side: SELL
   Amount: 0.005 ETH (half of your holdings)
   Price: $4,500
   ```

3. **Calculate Revenue**
   ```
   Revenue = 0.005 × $4,500 = $22.50
   Fee (0.5%) = $0.11
   Net = $22.39 USD
   ```

4. **Confirm Order**
   ```
   - System checks: Portfolio has 0.005 ETH ✓
   - Click "Sell ETH"
   - Confirm transaction
   ```

### Expected Result:
```
✅ Order executed successfully
✅ USD Balance: $72.65 + $22.39 = $95.04
✅ Portfolio: 0.005 ETH remaining (invested $21.10)
✅ Profit from trade: +$1.29 USD
✅ Dashboard shows updated portfolio and P&L
```

### Verify:
- **USD Balance:** Increased to $95.04
- **Portfolio:** Now shows 0.005 ETH
- **P&L Calculation:**
  ```
  Invested: $21.10 (half of original $42.19)
  Current Value: 0.005 × $4,500 = $22.50
  Profit: $22.50 - $21.10 = $1.40 USD (plus more from sold portion)
  ```

---

## STEP 4: WITHDRAW CRYPTO 💸

### Action: Withdraw Bitcoin to External Wallet

1. **Go to Withdraw Page**
   ```
   Navigate to: /user/withdraw
   ```

2. **Select Cryptocurrency**
   ```
   Select: BTC (Bitcoin)
   ```

3. **Enter Amount**
   ```
   Amount: 0.0005 BTC
   ```

4. **System Calculates USD Required**
   ```
   BTC Price: $115,047
   USD Needed: 0.0005 × $115,047 = $57.52
   Fee: $2.00 (example)
   Total: $59.52 USD
   ```

5. **Check Balance**
   ```
   Your USD Balance: $95.04
   Required: $59.52
   ✓ Sufficient balance
   ```

6. **Enter Withdrawal Details**
   ```
   BTC Address: [Your BTC wallet address]
   Network: Bitcoin (BTC)
   Amount: 0.0005 BTC
   ```

7. **Review Withdrawal**
   ```
   Preview shows:
   - Crypto Amount: 0.0005 BTC
   - USD Value: $57.52
   - Fee: $2.00
   - Total Deducted: $59.52 USD
   - Rate Locked: $115,047 per BTC
   - You will receive: 0.0005 BTC
   ```

8. **Confirm Withdrawal**
   ```
   - Enter 2FA code (if enabled)
   - Click "Confirm Withdrawal"
   ```

### Expected Result:
```
✅ Withdrawal request submitted
✅ USD Balance: $95.04 - $59.52 = $35.52
✅ Withdrawal Status: Pending (waiting for admin approval)
✅ Rate locked at $115,047 per BTC
✅ Admin will send: 0.0005 BTC to your address
```

### Verify:
- **USD Balance:** Decreased to $35.52
- **Withdrawal History:** Shows pending withdrawal
- **Conversion Record:** Created in currency_conversions table
- **Transaction:** Shows "Withdraw $57.52 USD → 0.0005 BTC"

---

## STEP 5: ADMIN APPROVAL (Simulate Admin)

### Action: Approve Withdrawal as Admin

1. **Login as Admin**
   ```
   URL: http://127.0.0.1:8000/admin/login
   ```

2. **Go to Withdrawals**
   ```
   Navigate to: Admin → Withdrawals → Pending
   ```

3. **View Withdrawal Details**
   ```
   User: test@example.com
   Crypto Amount: 0.0005 BTC ← Send this exact amount!
   USD Deducted: $57.52
   Conversion Rate: $115,047 (locked)
   User's Address: [provided address]
   ```

4. **Process Withdrawal**
   ```
   - Send 0.0005 BTC to user's address
   - Mark as "Approved" or "Complete"
   ```

### Expected Result:
```
✅ Withdrawal approved
✅ User receives: 0.0005 BTC in their external wallet
✅ System records completion
```

---

## 📊 FINAL RESULTS SUMMARY

### Starting Position:
```
USD Balance: $0
Portfolio: Empty
```

### After All Transactions:
```
USD Balance: $35.52
Portfolio: 0.005 ETH (worth ~$22.50)
Total Value: $35.52 + $22.50 = $58.02

Transactions:
1. Deposited: 0.001 BTC → $115.05 USD
2. Bought: 0.01 ETH for $42.40 USD
3. Sold: 0.005 ETH for $22.39 USD (profit: ~$1.29)
4. Withdrew: 0.0005 BTC costing $59.52 USD

Net Result: Started with 0.001 BTC, ended with $58.02 value
(Plus 0.0005 BTC in external wallet)
```

---

## 🧪 TEST SCENARIO 2: QUICK TEST (5 Minutes)

### For Quick Testing:

1. **Deposit**
   ```sql
   -- Simulate deposit directly in database
   UPDATE users SET usd_balance = 100.00 WHERE id = YOUR_USER_ID;
   
   -- Record conversion
   INSERT INTO currency_conversions (user_id, currency_id, currency_symbol, conversion_type, crypto_amount, usd_amount, conversion_rate, trx, created_at, updated_at)
   VALUES (YOUR_USER_ID, 1, 'BTC', 'deposit', 0.001, 100.00, 100000, 'TEST001', NOW(), NOW());
   ```

2. **Check Dashboard**
   ```
   - Should show $100 USD balance
   - Portfolio should be empty
   ```

3. **Try Buying Crypto**
   ```
   - Go to trading page
   - Buy 0.01 ETH
   - Verify USD deducted
   - Verify portfolio shows ETH
   ```

4. **Try Selling Crypto**
   ```
   - Sell the 0.01 ETH
   - Verify USD balance increases
   - Verify portfolio updated
   ```

5. **Try Withdrawal**
   ```
   - Request withdrawal of 0.0001 BTC
   - Verify USD check works
   - Verify rate locking
   ```

---

## 🔍 VERIFICATION CHECKLIST

### After Each Step, Verify:

#### ✅ Database Checks:
```sql
-- Check user USD balance
SELECT id, username, usd_balance, usd_balance_in_order FROM users WHERE id = YOUR_USER_ID;

-- Check portfolio
SELECT * FROM user_portfolios WHERE user_id = YOUR_USER_ID;

-- Check conversions
SELECT * FROM currency_conversions WHERE user_id = YOUR_USER_ID ORDER BY id DESC;

-- Check transactions
SELECT * FROM transactions WHERE user_id = YOUR_USER_ID ORDER BY id DESC LIMIT 5;

-- Check withdrawals
SELECT id, user_id, amount, usd_amount, crypto_amount, conversion_rate, status FROM withdrawals WHERE user_id = YOUR_USER_ID ORDER BY id DESC;
```

#### ✅ Dashboard Checks:
- [ ] Shows single USD balance (not multiple wallets)
- [ ] Portfolio table appears (when you have holdings)
- [ ] Portfolio shows correct P&L
- [ ] Transaction history shows USD amounts
- [ ] No errors on page load

#### ✅ Trading Checks:
- [ ] Can buy crypto with USD
- [ ] USD balance decreases correctly
- [ ] Portfolio shows new holding
- [ ] Can sell crypto back to USD
- [ ] USD balance increases correctly
- [ ] Portfolio updates after sell

#### ✅ Withdrawal Checks:
- [ ] Can select crypto to withdraw
- [ ] Shows USD cost calculation
- [ ] Checks balance before allowing
- [ ] Locks conversion rate
- [ ] Records conversion properly
- [ ] Admin sees correct crypto amount

---

## 🐛 TROUBLESHOOTING

### Common Issues:

#### Issue 1: "Invalid currency rate"
```
Problem: Currency rate is 0 or NULL
Solution:
UPDATE currencies SET rate = 115047.40 WHERE symbol = 'BTC';
UPDATE currencies SET rate = 4219.09 WHERE symbol = 'ETH';
```

#### Issue 2: "Insufficient balance"
```
Problem: USD balance not showing correctly
Solution:
- Check database: SELECT usd_balance FROM users WHERE id = YOUR_ID;
- Verify deposit was processed
- Clear cache: php artisan cache:clear
```

#### Issue 3: Portfolio not showing
```
Problem: user_portfolios table missing or empty
Solution:
- Verify table exists: SHOW TABLES LIKE 'user_portfolios';
- Check if you have holdings: SELECT * FROM user_portfolios WHERE user_id = YOUR_ID;
- Make a buy trade to populate portfolio
```

#### Issue 4: Withdrawal shows wrong amount
```
Problem: Conversion calculation incorrect
Solution:
- Verify currency rate is current
- Check conversion_rate field in withdrawals table
- Rate should be locked at withdrawal request time
```

---

## 📱 EXPECTED USER EXPERIENCE

### Dashboard View:
```
┌─────────────────────────────────────────┐
│  💰 USD BALANCE                         │
│  ────────────────────────────────────   │
│  Total Balance: $95.04 USD              │
│  ✅ Available: $95.04                   │
│  🔒 In Orders: $0.00                    │
│                                         │
│  ℹ️ USD-Based Account                   │
│  All crypto deposits are automatically  │
│  converted to USD.                      │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  📊 YOUR CRYPTO PORTFOLIO               │
├────────┬─────────┬─────────┬────────────┤
│ Asset  │ Amount  │ Avg Buy │ P&L        │
├────────┼─────────┼─────────┼────────────┤
│ ETH    │ 0.005   │ $4,219  │ +$1.40 ✅  │
└────────┴─────────┴─────────┴────────────┘
```

### Transaction History:
```
1. Withdraw: $57.52 USD → 0.0005 BTC
2. Sell: 0.005 ETH → $22.39 USD (Profit: +$1.29)
3. Buy: 0.01 ETH for $42.40 USD
4. Deposit: 0.001 BTC → $115.05 USD
```

---

## ✅ SUCCESS CRITERIA

### You've Successfully Tested When:
- [x] Can deposit crypto → converts to USD
- [x] Dashboard shows USD balance (not crypto wallets)
- [x] Can buy crypto with USD
- [x] Portfolio tracks holdings
- [x] Can sell crypto for USD
- [x] Profit/Loss calculates correctly
- [x] Can withdraw crypto using USD balance
- [x] Withdrawal rate is locked
- [x] All conversions are recorded
- [x] Transaction history shows USD amounts

---

## 🎯 TEST VARIATIONS

### Variation 1: Multiple Currencies
```
1. Deposit BTC → USD
2. Buy ETH with USD
3. Buy XRP with USD
4. Sell half of ETH → USD
5. Withdraw BTC
```

### Variation 2: Edge Cases
```
1. Try to withdraw more than balance (should fail)
2. Try to buy with insufficient balance (should fail)
3. Try to sell crypto you don't have (should fail)
4. Check rate changes don't affect existing orders
```

### Variation 3: Profit Tracking
```
1. Buy crypto at price X
2. Wait (or change rate in DB)
3. Check portfolio shows updated P&L
4. Sell at higher price
5. Verify profit is correct
```

---

## 📞 NEXT STEPS AFTER TESTING

### If Everything Works:
✅ System is ready for production!
- Consider adding auto rate updates
- Set up monitoring
- Train support team
- Go live!

### If Issues Found:
❌ Document the issue
- Note exact steps to reproduce
- Check logs: `storage/logs/laravel.log`
- Check database state
- Review relevant controller code

---

## 🎊 CONGRATULATIONS!

If you've completed this testing guide successfully, your **USD-based cryptocurrency trading platform is fully functional**!

Users can now:
- ✅ Deposit any crypto → Get USD
- ✅ Trade with simple USD balance
- ✅ Track portfolio with P&L
- ✅ Withdraw crypto anytime

---

**Last Updated:** October 27, 2025  
**System Version:** USD-Based Account v1.0  
**Test Status:** Ready for QA

🎯 **Happy Testing!** 🎯

