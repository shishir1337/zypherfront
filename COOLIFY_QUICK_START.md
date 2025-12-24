# Coolify Quick Start Guide (Official Method)

## 🚀 Fastest Deployment Path

### Method 1: Nixpacks (Recommended - Easiest)

1. **In Coolify Dashboard:**
   - New Resource → Application
   - Connect Repository → Select your Git repo
   - **Build Pack:** `nixpacks`
   - **Ports Exposes:** `80`

2. **Environment Variables** (Set in Coolify UI):

```env
APP_NAME=BigBuller
APP_ENV=production
APP_KEY=base64:Vg8WCWfg2LbvAs7vJNcpe6rWluzxR9TjznbZFrHNCKQ=
APP_DEBUG=false
APP_URL=https://your-domain.com

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

3. **Post-Deployment Script:**

```bash
cd core
chmod -R 775 storage bootstrap/cache
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

4. **Deploy!** ✅

---

### Method 2: Dockerfile + Nginx Unit

1. **In Coolify Dashboard:**
   - New Resource → Application
   - Connect Repository → Select your Git repo
   - **Build Pack:** `dockerfile`
   - **Ports Exposes:** `8000`

2. **Environment Variables:** (Same as Method 1)

3. **Post-Deployment Script:**

```bash
php artisan optimize:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan optimize
```

4. **Deploy!** ✅

---

## ⚙️ Configuration Files

The repository already includes:
- ✅ `nixpacks.toml` - For Nixpacks deployment
- ✅ `Dockerfile` - For Dockerfile deployment
- ✅ `unit.json` - For Nginx Unit configuration
- ✅ `.dockerignore` - To exclude unnecessary files

---

## 🔧 Important Notes

### Database Access
⚠️ Ensure your MySQL server (72.60.174.166) allows connections from your Coolify server's IP address.

### First Deployment
After first deployment, you may need to:
1. Run migrations: `php artisan migrate --force`
2. Create storage link: `php artisan storage:link`
3. Clear caches: `php artisan optimize:clear`

### Domain & SSL
1. Add your domain in Coolify
2. SSL will be auto-provisioned
3. Update `APP_URL` to match your domain

---

## 🐛 Quick Troubleshooting

**500 Error?**
- Check logs in Coolify dashboard
- Verify `.env` variables are set
- Check storage permissions: `chmod -R 775 storage bootstrap/cache`

**Database Connection Failed?**
- Verify database credentials
- Check firewall rules (allow Coolify server IP)
- Test: `php artisan tinker` → `DB::connection()->getPdo();`

**Assets Not Loading?**
- Run: `php artisan storage:link`
- Check file permissions on `public/storage`

---

## 📚 Need More Details?

See `COOLIFY_DEPLOYMENT_GUIDE.md` for comprehensive instructions.

---

**Recommended:** Use Method 1 (Nixpacks) - It's the easiest and follows official Coolify best practices!
