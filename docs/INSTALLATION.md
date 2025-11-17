# Installation Guide

## Fresh Installation

### 1. Clone Repository
```bash
git clone <repository-url> camr
cd camr
```

### 2. Run Setup Script
```bash
composer setup
```

This will:
- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install Node.js dependencies
- Build frontend assets

### 3. Configure Table Columns (Optional but Recommended)

The application will work immediately with default column configurations. However, **for production environments**, you should create a custom configuration:

```bash
# Option A: Use the setup script
./scripts/setup-table-config.sh

# Option B: Manual copy
cp resources/js/config/tableColumns.example.ts resources/js/config/tableColumns.ts
```

**Then customize the file for your environment:**
```bash
# Edit the configuration
nano resources/js/config/tableColumns.ts

# Or use your preferred editor
code resources/js/config/tableColumns.ts
```

**Rebuild frontend after customization:**
```bash
npm run build
```

### 4. Seed Database (Development Only)
```bash
php artisan db:seed
```

This creates demo data including:
- 7 users (admin@example.com / password, test@example.com / password)
- ~50 sites, buildings, locations
- ~40 meters
- 27,360 meter data records
- 25,536 load profiles

### 5. Start Development Server
```bash
composer dev
```

This starts:
- PHP development server (port 8000)
- Queue worker
- Log viewer (pail)
- Vite dev server

### 6. Access Application
Open your browser to `http://localhost:8000`

---

## Production Deployment

### 1. Clone and Install
```bash
git clone <repository-url> camr
cd camr
composer install --no-dev --optimize-autoloader
```

### 2. Environment Configuration
```bash
cp .env.example .env
nano .env
```

Configure:
- `APP_ENV=production`
- `APP_DEBUG=false`
- Database credentials
- Mail settings
- Queue driver

### 3. **IMPORTANT: Table Columns Configuration**

**On production, you MUST create a custom table configuration:**

```bash
./scripts/setup-table-config.sh
```

**Then customize it for your production needs:**
```bash
nano resources/js/config/tableColumns.ts
```

**Why is this important?**
- Default config is for development
- Production might need different column visibility
- Different environments may have different requirements
- Customization prevents conflicts with example updates

### 4. Application Setup
```bash
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

### 5. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 6. Set Permissions
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 7. Configure Web Server

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/camr/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
```

### 8. Setup Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/camr-worker.conf`:
```ini
[program:camr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/camr/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/camr/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start camr-worker:*
```

### 9. Setup Scheduler (Cron)

Add to crontab:
```bash
* * * * * cd /var/www/camr && php artisan schedule:run >> /dev/null 2>&1
```

---

## Environment-Specific Configurations

### Development
- Uses `tableColumns.example.ts` by default (automatic fallback)
- Console shows: `⚠ Using example configuration`
- Hot reload enabled
- Debug mode on

### Staging
- **Create custom** `tableColumns.ts` from example
- Customize for staging needs
- Enable debug logging
- Use staging database

### Production
- **MUST create custom** `tableColumns.ts`
- Disable debug mode
- Enable caching
- Use production database
- Configure proper logging

---

## Table Columns Configuration

### Why Have Different Configs?

Different environments might need:

**Development:**
- All columns visible for testing
- Wide columns for debugging
- Extra metadata columns

**Staging:**
- Similar to production
- May include test columns
- Performance testing columns

**Production:**
- Only essential columns visible
- Optimized column widths
- User-facing columns only

### Configuration Workflow

1. **Start with example**:
   ```bash
   cp resources/js/config/tableColumns.example.ts resources/js/config/tableColumns.ts
   ```

2. **Customize for environment**:
   - Adjust default column visibility
   - Set appropriate column widths
   - Configure column order
   - Set which columns are sortable

3. **Test changes**:
   ```bash
   npm run build
   # Verify tables render correctly
   ```

4. **Deploy**:
   - File is NOT in Git (`.gitignore`)
   - Each environment has its own config
   - No conflicts between environments

### Example Customizations

**Hide "Created At" in production:**
```typescript
{
  key: 'created',
  label: 'Created',
  visible: false,  // Hidden in production
  order: 4,
  // ...
}
```

**Reorder columns for better UX:**
```typescript
// Move status column first
{
  key: 'status',
  order: 0,  // First column (was order: 4)
  // ...
}
```

**Adjust column widths:**
```typescript
{
  key: 'description',
  width: '300px',  // Wider in production (was 'auto')
  // ...
}
```

---

## Troubleshooting

### "Using example configuration" warning

**Symptom**: Console shows warning about example config

**Solution**: Create custom config for your environment:
```bash
./scripts/setup-table-config.sh
npm run build
```

### Table columns not displaying correctly

**Possible causes:**
1. Frontend assets not built: `npm run build`
2. Browser cache: Hard refresh (Ctrl+Shift+R)
3. LocalStorage conflicts: Clear localStorage for the site

### Changes not reflecting

**Solution**:
```bash
# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Rebuild frontend
npm run build

# Hard refresh browser
```

### Permission errors on tableColumns.ts

**Solution**:
```bash
chmod 644 resources/js/config/tableColumns.ts
```

---

## Verification Checklist

After installation, verify:

- [ ] Application loads without errors
- [ ] Can log in with seeded credentials
- [ ] All index tables display correctly
- [ ] Column preferences can be toggled
- [ ] Search and filters work
- [ ] Data exports successfully
- [ ] Queue worker is running (production)
- [ ] Scheduler is running (production)
- [ ] Custom `tableColumns.ts` exists (production)
- [ ] No console errors in browser

---

## Support

- **Documentation**: See `/docs` directory
- **Table Configuration**: `docs/TABLE_COLUMN_CONFIGURATION.md`
- **Database Seeding**: `docs/DATABASE_SEEDING.md`

---

**Last Updated**: 2025-11-17
**Version**: 1.0.0
