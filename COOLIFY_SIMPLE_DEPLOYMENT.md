# 🚀 EASIEST Deployment Guide - BigBuller to Coolify

This is the **SIMPLEST** way to deploy your Laravel app to Coolify. No complex configuration needed!

## Step-by-Step (5 Minutes)

### Step 1: In Coolify Dashboard

1. Click **"New Resource"** → **"Application"**
2. Connect your Git repository
3. Select your branch (usually `main` or `master`)

### Step 2: Configure Build Settings

**IMPORTANT:** Let Coolify auto-detect Laravel!

1. **Build Pack:** Leave it as **"Auto-detect"** or select **"Laravel"**
2. **Root Directory:** Set to `/core/public`
3. **Ports Exposes:** Leave default (usually `80`)

### Step 3: Set Environment Variables

Click on **"Environment Variables"** and add these:

```env
APP_NAME=BigBuller
APP_ENV=production
APP_KEY=base64:Vg8WCWfg2LbvAs7vJNcpe6rWluzxR9TjznbZFrHNCKQ=
APP_DEBUG=false
APP_URL=http://bigbuller.com

DB_CONNECTION=mysql
DB_HOST=72.60.174.166
DB_PORT=3306
DB_DATABASE=default
DB_USERNAME=mysql
DB_PASSWORD=ZOG4rH4hIvmt7dCbcO7ERXujtherLKxaIkPeluH7VIKwc3HRJ8RAHPsNkSUQ5NID

LOG_CHANNEL=stack
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### Step 4: Post-Deployment Script

In the **"Post-Deployment"** section, add:

```bash
cd core && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Step 5: Deploy!

Click **"Deploy"** and wait. That's it! 🎉

---

## That's All You Need!

Coolify will automatically:
- ✅ Detect it's a Laravel app
- ✅ Install PHP dependencies
- ✅ Set up Nginx
- ✅ Configure everything

---

## If Something Goes Wrong

### Issue: Can't find composer.json

**Solution:** Make sure **Root Directory** is set to `/core/public` (not `/core`)

### Issue: 500 Error

**Solution:** Add this to Post-Deployment:
```bash
cd core && chmod -R 775 storage bootstrap/cache && php artisan storage:link
```

### Issue: Database Connection Failed

**Solution:** 
1. Check your MySQL server allows connections from Coolify's IP
2. Verify database credentials in Environment Variables

---

## Optional: Add Your Domain

1. In Coolify, go to your app settings
2. Click **"Domains"**
3. Add your domain (e.g., `bigbuller.com`)
4. SSL will be auto-configured!

---

**That's it! The simplest way possible.** 🚀

