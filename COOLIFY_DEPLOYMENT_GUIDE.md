# Coolify Deployment Guide for BigBuller (Official Method)

This guide follows the official Coolify documentation for deploying Laravel applications.

## Prerequisites

1. A Coolify instance running (self-hosted or cloud)
2. Git repository with your code
3. MySQL database credentials (already configured)
4. Domain name (optional, for custom domain)

## Deployment Methods

Coolify supports two official methods for deploying Laravel:

1. **Nixpacks (Recommended)** - Automatic Laravel detection with all-in-one container
2. **Dockerfile + Nginx Unit** - More control over the deployment process

---

## Method 1: Deploy with Nixpacks (Recommended)

### Step 1: Create New Resource in Coolify

1. **Login to Coolify Dashboard**
   - Navigate to your Coolify instance
   - Login with your credentials

2. **Create New Application**
   - Click "New Resource" → "Application"
   - Select your Git provider (GitHub, GitLab, etc.)
   - Select your repository
   - Choose the branch (usually `main` or `master`)

### Step 2: Configure Build Settings

1. **Set Build Pack**
   - Set **Build Pack** to `nixpacks`

2. **Set Ports**
   - Set **Ports Exposes** to `80`

3. **Root Directory**
   - The `nixpacks.toml` file is already configured in the repository
   - It handles the `core/` directory structure automatically

### Step 3: Configure Environment Variables

In Coolify's **Environment Variables** section, add/update:

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

# Optional: Redis (if you create Redis resource in Coolify)
# REDIS_HOST=<REDIS_HOST>
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# Zypher API Configuration
ZYPHER_API_URL=https://zypher.bigbuller.com/api
ZYPHER_SOCKET_URL=https://zypher.bigbuller.com
```

**Security Note:** Never commit sensitive credentials. Use Coolify's environment variable management.

### Step 4: Post-Deployment Script

Add this in Coolify's **Post-Deployment** section:

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

### Step 5: Configure Domain & SSL

1. **Add Domain**
   - In Coolify, go to your application settings
   - Add your domain name
   - Coolify will automatically provision SSL via Let's Encrypt

2. **Update APP_URL**
   - Set `APP_URL` environment variable to your domain
   - Example: `APP_URL=https://bigbuller.com`

### Step 6: Deploy!

Click **Deploy** and wait for the build to complete.

---

## Method 2: Deploy with Dockerfile and Nginx Unit

### Step 1: Create New Resource in Coolify

1. **Login to Coolify Dashboard**
2. **Create New Application**
   - Click "New Resource" → "Application"
   - Select your Git provider and repository

### Step 2: Configure Build Settings

1. **Set Build Pack**
   - Set **Build Pack** to `dockerfile`

2. **Set Ports**
   - Set **Ports Exposes** to `8000`

3. **Dockerfile Location**
   - The `Dockerfile` is already in the repository root
   - The `unit.json` file is also configured

### Step 3: Configure Environment Variables

Set the same environment variables as Method 1 (see above).

**Important:** Set these default environment variables in Coolify:

```env
APP_DEBUG=false
APP_ENV=production
APP_KEY=base64:Vg8WCWfg2LbvAs7vJNcpe6rWluzxR9TjznbZFrHNCKQ=
APP_MAINTENANCE_DRIVER=file
APP_NAME=BigBuller
CACHE_STORE=file
FILESYSTEM_DISK=public
MAIL_MAILER=log
SESSION_DRIVER=file
```

### Step 4: Post-Deployment Script

Add this in Coolify's **Post-Deployment** section:

```bash
php artisan optimize:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan optimize
```

### Step 5: Deploy!

Click **Deploy** and wait for the build to complete.

---

## Database Setup

### Create Database in Coolify (Optional)

If you want to create a database in Coolify instead of using the external MySQL:

1. In Coolify dashboard, create a new **Database** resource
2. Select **MySQL**
3. Coolify will provide connection strings automatically
4. Use those connection strings in your environment variables

### Using External Database (Current Setup)

Your MySQL database is hosted separately at `72.60.174.166`. Ensure:

1. **Firewall Rules**: Allow connections from your Coolify server's IP address
2. **Database Credentials**: Already configured in environment variables
3. **Test Connection**: After deployment, test with:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

### Run Migrations

After deployment, run migrations:

```bash
php artisan migrate --force
```

This can be added to the post-deployment script or run manually via Coolify's terminal.

---

## Queue Workers & Scheduled Tasks

### Queue Workers (Nixpacks Method)

The `nixpacks.toml` file already includes queue worker configuration. It will automatically start:
- Nginx
- PHP-FPM
- Laravel Queue Worker (2 processes by default)

To adjust the number of queue workers, edit `nixpacks.toml`:
```toml
numprocs=2  # Change this number
```

### Scheduled Tasks (Cron)

Add scheduled tasks in Coolify's cron section:

```bash
* * * * * cd /app/core && php artisan schedule:run >> /dev/null 2>&1
```

Or create a separate service in Coolify for the scheduler.

---

## Monitoring & Logs

### View Logs

1. **Application Logs**
   - Access via Coolify dashboard
   - Or check: `core/storage/logs/laravel.log`

2. **Queue Worker Logs**
   - Check: `/var/log/worker-laravel.log` (inside container)

3. **Nginx Logs**
   - Check: `/var/log/nginx-access.log` and `/var/log/nginx-error.log`

### Health Check

Your app has a health endpoint: `/up`

Configure in Coolify for automatic health checks:
- Health Check Path: `/up`
- Health Check Port: `80` (or `8000` for Dockerfile method)

---

## Troubleshooting

### Issue: 500 Error

1. **Check Storage Permissions**
   ```bash
   chmod -R 775 /var/www/html/core/storage
   chmod -R 775 /var/www/html/core/bootstrap/cache
   ```

2. **Check .env File**
   - Verify `.env` file exists in `core/` directory
   - Check all required environment variables are set

3. **Check Logs**
   - View logs in Coolify dashboard
   - Check `core/storage/logs/laravel.log`

### Issue: Database Connection Failed

1. **Verify Credentials**
   - Check environment variables in Coolify
   - Ensure database credentials are correct

2. **Check Firewall**
   - Ensure MySQL server allows connections from Coolify server IP
   - Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

3. **Check Database Server**
   - Verify MySQL server is running
   - Check if database `default` exists

### Issue: Assets Not Loading

1. **Create Storage Link**
   ```bash
   php artisan storage:link
   ```

2. **Check Permissions**
   ```bash
   chmod -R 775 public/storage
   ```

3. **Clear Cache**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

### Issue: Route Not Found

1. **Clear Route Cache**
   ```bash
   php artisan route:clear
   ```

2. **Rebuild Cache**
   ```bash
   php artisan route:cache
   ```

### Issue: Queue Not Working

1. **Check Queue Worker**
   - Verify queue worker is running (check logs)
   - Restart the application if needed

2. **Check Queue Connection**
   - Verify `QUEUE_CONNECTION` is set correctly
   - For production, consider using `redis` or `database`

---

## Environment Variables Checklist

Make sure these are set in Coolify:

### Required
- [x] `APP_NAME`
- [x] `APP_ENV=production`
- [x] `APP_KEY`
- [x] `APP_DEBUG=false`
- [x] `APP_URL`
- [x] `DB_CONNECTION`
- [x] `DB_HOST`
- [x] `DB_PORT`
- [x] `DB_DATABASE`
- [x] `DB_USERNAME`
- [x] `DB_PASSWORD`

### Optional
- [ ] `MAIL_*` (if using email)
- [ ] `REDIS_*` (if using Redis)
- [ ] `PUSHER_*` (if using Pusher)
- [ ] `ZYPHER_API_URL`
- [ ] `ZYPHER_SOCKET_URL`

---

## PHP Configuration

### PHP Version
- **Required:** PHP 8.3 (as per composer.json)
- Configure in Coolify: PHP 8.3

### PHP Extensions Required
- pdo_mysql
- mbstring
- xml
- curl
- zip
- gd (for image processing)
- opcache (for performance)

### Memory Limits (Nixpacks)
Already configured in `nixpacks.toml`:
- PHP Memory: `256M`
- Max Upload Size: `30M`
- Post Max Size: `35M`

### Memory Limits (Dockerfile)
Already configured in Dockerfile:
- PHP Memory: `512M`
- Max Upload Size: `64M`
- Post Max Size: `64M`

---

## Security Recommendations

1. **Never commit `.env` file** - Use Coolify's environment variables
2. **Set `APP_DEBUG=false`** in production
3. **Use strong `APP_KEY`** - Generate new one if needed: `php artisan key:generate`
4. **Enable HTTPS** - Coolify handles this automatically
5. **Restrict database access** - Only allow connections from Coolify server IP
6. **Regular backups** - Configure database backups
7. **Update dependencies** - Regularly update Composer and NPM packages

---

## Performance Optimization

### After Deployment

Run these commands to optimize:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Enable OPcache

OPcache is already enabled in both deployment methods for better performance.

### Queue Processing

The Nixpacks method automatically starts queue workers. Adjust the number in `nixpacks.toml` based on your needs.

---

## Support

If you encounter issues:

1. Check Coolify logs
2. Check Laravel logs: `core/storage/logs/laravel.log`
3. Verify all environment variables are set
4. Test database connection
5. Check file permissions
6. Review the official Coolify documentation

---

**Last Updated:** Based on Official Coolify Documentation
**Application:** BigBuller
**Framework:** Laravel 11
**PHP Version:** 8.3
**Deployment Methods:** Nixpacks (Recommended) | Dockerfile + Nginx Unit
