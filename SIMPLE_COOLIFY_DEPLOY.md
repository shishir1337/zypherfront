# 🚀 Simple Coolify Deployment - No Fancy Stuff

The **EASIEST** way to deploy your Laravel app with Coolify. No complex configurations needed!

## Step 1: In Coolify Dashboard

1. Click **"New Resource"** → **"Application"**
2. Connect your Git repository
3. Select your branch

## Step 2: Let Coolify Auto-Detect

- **Build Pack:** Set to **"nixpacks"** (or leave as "Auto-detect")
- **Root Directory:** Set to `/` (repository root - this is IMPORTANT!)
- **Port:** Leave default (Coolify handles this)

**⚠️ CRITICAL:** 
- Root Directory must be `/` (not `/core/public`) because your root `index.php` needs access to `/core/vendor/autoload.php`
- A `nixpacks.toml` file has been created in the repository root to ensure composer installs dependencies in the `core` directory

## Step 3: Set Environment Variables

Click **"Environment Variables"** and add:

```env
APP_NAME=BigBuller
APP_ENV=production
APP_KEY=base64:Vg8WCWfg2LbvAs7vJNcpe6rWluzxR9TjznbZFrHNCKQ=
APP_DEBUG=false
APP_URL=https://bigbuller.com

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

## Step 4: Post-Deployment Script

In **"Post-Deployment"** section, add:

```bash
cd /app/core && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Note:** Use `/app/core` (absolute path) because the working directory in the container is `/app` (repository root)

## Step 5: Add Domain

1. Click **"Domains"** in your app settings
2. Add `bigbuller.com`
3. SSL will be auto-configured by Coolify

## Step 6: Deploy!

Click **"Deploy"** and wait. That's it! 🎉

---

## That's All!

Coolify automatically:
- ✅ Detects Laravel
- ✅ Installs dependencies
- ✅ Sets up Nginx
- ✅ Configures PHP-FPM
- ✅ Handles SSL
- ✅ Manages ports
- ✅ Handles everything!

**No need for:**
- ❌ Manual Nginx configs
- ❌ Systemd services
- ❌ Port management
- ❌ SSL certificates
- ❌ Complex setups

---

## If Something Goes Wrong

### Can't find composer.json or vendor/autoload.php

**Problem:** Coolify isn't running `composer install` because `composer.json` is in the `core` subdirectory

**Solution:** 
- A `nixpacks.toml` file has been created in the repository root
- This explicitly tells Coolify to run `composer install` in the `core` directory
- **Force Rebuild** in Coolify to apply the changes

### 500 Error
- Add to Post-Deployment: `cd /app/core && chmod -R 775 storage bootstrap/cache && php artisan storage:link`

### Build Skipped / Using Cached Image
- **Force a rebuild** - Coolify is using an old cached image
- After changing Root Directory, always force rebuild!

### Database Connection Failed
- Check your MySQL server allows connections from Coolify server IP

---

**That's it! Coolify handles everything else automatically.** 🚀

