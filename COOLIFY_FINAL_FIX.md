# ✅ Final Fix: Add nixpacks.toml

## The Problem

Coolify's auto-detection isn't finding `composer.json` because it's in the `core` subdirectory, so `composer install` never runs, and `vendor/autoload.php` is missing.

## The Solution

A `nixpacks.toml` file has been created in the repository root. This explicitly tells Coolify to:
1. Change to the `core` directory
2. Run `composer install`
3. Run `npm install` and `npm run production`

## What You Need to Do

### Step 1: Commit and Push the New File

```bash
git add nixpacks.toml
git commit -m "Add nixpacks.toml to fix composer install"
git push
```

### Step 2: In Coolify Dashboard

1. Make sure **Root Directory** is set to `/` (repository root)
2. Make sure **Build Pack** is set to **"nixpacks"** (or "Auto-detect")
3. Click **"Deploy"** (or it will auto-deploy when it detects the new commit)

### Step 3: Watch the Build Logs

You should now see:
- ✅ `cd core`
- ✅ `composer install --no-dev --optimize-autoloader --no-interaction`
- ✅ `npm install`
- ✅ `npm run production`
- ✅ `vendor/autoload.php` created

### Step 4: Verify Post-Deployment Works

After the build completes, the post-deployment script should succeed:
```bash
cd /app/core && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## What the nixpacks.toml Does

```toml
[phases.build]
cmds = [
    "cd core",
    "composer install --no-dev --optimize-autoloader --no-interaction",
    "npm install",
    "npm run production"
]
```

This ensures that:
- Composer installs dependencies in `/app/core/vendor`
- NPM builds assets
- Everything is ready for Laravel to run

---

**That's it! Commit, push, and redeploy!** 🚀

