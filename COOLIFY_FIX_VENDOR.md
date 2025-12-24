# 🔧 Fix: vendor/autoload.php Missing

## The Problem

You're getting:
```
Warning: require(/app/core/vendor/autoload.php): Failed to open stream
```

**Root cause:** Coolify is using a **cached build image** from when Root Directory was `/core/public`. The new build with Root Directory `/` was skipped.

## The Fix

### Option 1: Force Rebuild (Easiest)

In Coolify Dashboard:

1. Go to your application
2. Click on **"Deployments"** or **"Builds"**
3. Look for a **"Force Rebuild"** or **"Rebuild"** button
4. Click it to force a fresh build

**OR** trigger a new build by:
- Making a small commit (add a space to README.md and commit)
- Or manually trigger deployment again

### Option 2: Add Build Command

In Coolify Dashboard:

1. Go to your application settings
2. Find **"Build Command"** or **"Build"** section
3. Add this build command:

```bash
cd core && composer install --no-dev --optimize-autoloader --no-interaction
```

This ensures composer installs dependencies even if auto-detection misses it.

### Option 3: Clear Build Cache

If there's a way to clear the build cache in Coolify, do that, then redeploy.

---

## After Rebuild

Once the rebuild completes with Root Directory `/`, you should see:
- ✅ `composer install` running during build
- ✅ `vendor` directory created in `/app/core/vendor`
- ✅ Post-deployment script working

---

## Verify It's Fixed

After rebuild, check the logs. You should see:
- Composer installing packages
- `vendor/autoload.php` exists
- Post-deployment commands succeed

---

**The key is forcing a fresh build with Root Directory `/`!** 🚀

