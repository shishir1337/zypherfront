# 🔄 Real-Time Price Updates - Setup Guide

## ✅ Scheduler Configured!

I've enabled automatic price updates that will fetch real-time prices from ZypherAPI every minute.

---

## 🚀 How to Start Price Updates

### **Option 1: Start the Scheduler** (Recommended for Development)

Open a **NEW terminal/command prompt** and run:

```bash
cd core
php artisan schedule:work
```

**Keep this terminal running!** It will update prices every minute.

You should see output like:
```
[2025-10-29 18:15:00] Running scheduled command: update-market-prices
CRYPTO PRICE UPDATE
[2025-10-29 18:15:00] Running scheduled command: update-crypto-prices
CRYPTO PRICE UPDATE
```

---

### **Option 2: Manual Update** (For Testing)

To manually update prices right now:

```bash
cd core
php artisan tinker
```

Then run:
```php
defaultCurrencyDataProvider()->updateMarkets();
```

---

## 📊 What's Happening:

- **Every 1 minute**: Prices update from ZypherAPI
- **Automatic**: No manual intervention needed
- **Real-time**: Prices will fluctuate based on market data
- **Pusher**: Updates are broadcast to all connected users

---

## 🧪 Test It:

1. **Start the scheduler** in a separate terminal:
   ```bash
   php artisan schedule:work
   ```

2. **Open your trading page**:
   ```
   http://127.0.0.1:8000/trade/ZPH_USDT
   ```

3. **Watch the price change** every minute!

4. **Keep both terminals running**:
   - Terminal 1: `php artisan serve` (your web server)
   - Terminal 2: `php artisan schedule:work` (price updates)

---

## 🔧 For Production:

On a production server, you would add this to your system crontab:

```bash
* * * * * cd /path/to/your/project/core && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and executes all scheduled tasks.

---

## ⚡ Quick Commands:

| Action | Command |
|--------|---------|
| Start price updates | `php artisan schedule:work` |
| Manual update | Visit: `http://127.0.0.1:8000/cron/market` |
| Check last update | Check trading page price timestamp |
| View logs | `core/storage/logs/laravel.log` |

---

## 🎯 Current Configuration:

- ✅ **Update Frequency**: Every 1 minute
- ✅ **Data Source**: ZypherAPI
- ✅ **API URL**: https://zypher.bigbuller.com/api
- ✅ **Pairs**: All active pairs (BNB, ETH, ZPH, etc.)
- ✅ **Real-time**: Yes, via Pusher broadcasting

---

## 📝 Notes:

1. **ZPH Price**: Will now update automatically from the API
2. **No more manual updates needed**: The 3.65 price I set will be overwritten
3. **API must be accessible**: Make sure https://zypher.bigbuller.com is reachable
4. **Price fluctuates**: Based on real market data, not static

---

## ✅ Next Steps:

1. **Start the scheduler**: `php artisan schedule:work`
2. **Refresh your trading page**
3. **Watch prices update every minute**
4. **Trading will use real-time prices**

**Prices will now fluctuate like a real crypto exchange! 📈📉**

