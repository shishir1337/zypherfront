# 🐳 Docker Deployment Guide for Coolify

Simple Docker-based deployment - no npm/webpack hassles!

## Step 1: In Coolify Dashboard

1. Go to your application settings
2. **Build Pack:** Select **"Dockerfile"** (not nixpacks)
3. **Root Directory:** Set to `/` (repository root)
4. **Port:** Set to `80`

## Step 2: Environment Variables

Add these in Coolify:

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

## Step 3: Post-Deployment Script

In **"Post-Deployment"** section:

```bash
cd /var/www/html/core && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && chmod -R 775 storage bootstrap/cache
```

## Step 4: Add Domain

1. Click **"Domains"**
2. Add `bigbuller.com`
3. SSL will be auto-configured

## Step 5: Deploy!

Click **"Deploy"** and wait. That's it! 🎉

---

## What This Dockerfile Does

- ✅ Uses PHP 8.3 with FPM
- ✅ Installs all required PHP extensions
- ✅ Installs Composer
- ✅ Copies your entire repository
- ✅ Runs `composer install` in the `core` directory
- ✅ Sets up Nginx + PHP-FPM with Supervisor
- ✅ No npm/webpack - just PHP!

---

## Files Created

- `Dockerfile` - Main Docker configuration
- `docker/nginx.conf` - Nginx server configuration
- `docker/supervisord.conf` - Supervisor to run Nginx + PHP-FPM
- `.dockerignore` - Excludes unnecessary files from build

---

**That's it! Much simpler than nixpacks!** 🚀

