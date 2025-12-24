# 🔧 Fix for Coolify Deployment Error

## The Problem

You're getting this error:
```
Warning: require(/app/../vendor/autoload.php): Failed to open stream
```

This happens because:
- Coolify is set to **Root Directory:** `/core/public`
- Only the `core/public` folder is copied to `/app` in the container
- But your root `index.php` needs `/core/vendor/autoload.php` which doesn't exist

## The Fix

### In Coolify Dashboard:

1. Go to your application settings
2. Find **"Root Directory"** setting
3. Change it from `/core/public` to `/` (just a forward slash - the repository root)
4. Save the changes

### Update Post-Deployment Script:

Change the post-deployment script to:

```bash
php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**OR** if you need to be in the core directory:

```bash
cd /app/core && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Why This Works

- Root Directory `/` copies the entire repository to `/app`
- Your root `index.php` is at `/app/index.php`
- It can now find `/app/core/vendor/autoload.php` ✅
- It can find `/app/core/bootstrap/app.php` ✅

### After Making Changes

1. Click **"Deploy"** again
2. Coolify will rebuild with the correct root directory
3. The error should be fixed!

---

## Quick Summary

**Change in Coolify:**
- ❌ Root Directory: `/core/public` (wrong)
- ✅ Root Directory: `/` (correct)

**Update Post-Deployment:**
- Remove `cd core` (you're already at root)
- Or use `cd /app/core` if needed

That's it! 🎉

