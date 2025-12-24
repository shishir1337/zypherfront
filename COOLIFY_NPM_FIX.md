# 🔧 Fix: npm run production Failing

## The Problem

The build is failing because:
```
ERROR in /js/app
Module not found: Error: Can't resolve '/app/core/resources/js/app.js'
```

**Root cause:** The `resources/js/app.js` and `resources/sass/app.scss` files don't exist, but `webpack.mix.js` is trying to compile them.

## The Solution

The `nixpacks.toml` has been updated to:
1. Check if the asset files exist before running `npm run production`
2. Skip the build step if files don't exist (instead of failing)

### Updated Build Command

```toml
[phases.build]
cmds = [
    "cd core",
    "composer install --no-dev --optimize-autoloader --no-interaction",
    "npm install",
    "if [ -f resources/js/app.js ] && [ -f resources/sass/app.scss ]; then npm run production; else echo 'Skipping npm build - asset files not found'; fi"
]
```

## What This Does

- ✅ Runs `composer install` (required)
- ✅ Runs `npm install` (installs dependencies)
- ✅ Conditionally runs `npm run production` only if asset files exist
- ✅ Doesn't fail the build if asset files are missing

## Next Steps

1. **Commit and push** the updated `nixpacks.toml`:
   ```bash
   git add nixpacks.toml
   git commit -m "Make npm build conditional on asset files existing"
   git push
   ```

2. **Redeploy** in Coolify - the build should now succeed!

---

## Alternative: Create Placeholder Files

If you want to always run the npm build, you can create empty placeholder files:

```bash
mkdir -p core/resources/js core/resources/sass
touch core/resources/js/app.js
touch core/resources/sass/app.scss
```

But the conditional approach is better if you don't need these files.

---

**The build should now complete successfully!** 🚀

