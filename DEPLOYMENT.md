# ICSA Website - Deployment Guide

This project is ready for nginx deployment. Follow these steps to deploy.

## Quick Start (5 Steps)

### 1. Install Nginx and PHP

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install nginx php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml -y
sudo systemctl start nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable nginx
sudo systemctl enable php8.2-fpm
```

**CentOS/RHEL:**
```bash
sudo yum install nginx php-fpm php-mysql php-mbstring php-xml -y
sudo systemctl start nginx
sudo systemctl start php-fpm
sudo systemctl enable nginx
sudo systemctl enable php-fpm
```

**macOS (Homebrew):**
```bash
brew install nginx php
brew services start nginx
brew services start php
```

### 2. Copy Nginx Config

```bash
# Copy the config file
sudo cp deploy/nginx/icsa-international.conf.example /etc/nginx/sites-available/icsa

# Edit the config (change these 3 lines)
sudo nano /etc/nginx/sites-available/icsa
```

**Change these 3 lines:**
| Setting | Example Value |
|---------|---------------|
| `server_name` | `your-domain.com` or `localhost` |
| `root` | `/var/www/icsa/public` (full path to public folder) |
| `fastcgi_pass` | `unix:/run/php/php8.2-fpm.sock` or `127.0.0.1:9000` |

Common `fastcgi_pass` values:
- Ubuntu: `unix:/run/php/php8.2-fpm.sock`
- CentOS: `unix:/run/php-fpm/www.sock`
- macOS: `127.0.0.1:9000`

### 3. Enable the Site

```bash
# Create symlink to enable site
sudo ln -s /etc/nginx/sites-available/icsa /etc/nginx/sites-enabled/

# Test config (must say "successful")
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx    # Linux
brew services restart nginx    # macOS
```

### 4. Configure the Application

```bash
# Go to project folder
cd /path/to/project

# Create .env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit .env
nano .env
```

**Required .env changes:**
```
APP_URL=https://your-domain.com          # Must match nginx server_name
APP_DEBUG=false                          # Set to false for production
DB_DATABASE=your_database                # Your MySQL database name
DB_USERNAME=your_username                # Your MySQL username
DB_PASSWORD=your_password                # Your MySQL password
ADMIN_DEFAULT_PASSWORD=secure-password    # Change this!
```

### 5. Set Permissions

```bash
# Make these folders writable by web server
chmod -R 775 storage bootstrap/cache

# For Linux: change owner to web server user
sudo chown -R www-data:www-data storage bootstrap/cache
```

## Done

- **Website:** `https://your-domain.com`
- **Admin login:** `https://your-domain.com/secure-staff-portal/login`
- **Default admin:** username `admin`, password from `.env` file

## Troubleshooting

### 502 Bad Gateway
PHP-FPM is not running or wrong socket path.

**Fix:**
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Start PHP-FPM
sudo systemctl start php8.2-fpm

# Find correct socket path
find /run -name "php*.sock" 2>/dev/null
```

### 404 Not Found
Root path is wrong or nginx not reading config.

**Fix:**
```bash
# Check nginx is reading your config
sudo nginx -T | grep "icsa"

# Verify root path exists
ls -la /var/www/icsa/public

# Check error logs
sudo tail -20 /var/log/nginx/error.log
```

### Permission Denied
Web server can't write to storage.

**Fix:**
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Check SELinux (CentOS)
ls -laZ storage/
```

### Database Connection Error
Database credentials wrong in `.env`.

**Fix:**
```bash
# Test database connection
mysql -u your_username -p your_database

# Check .env has correct DB_ settings
cat .env | grep DB_
```

### Internal Server Error
Check Laravel logs:
```bash
tail -20 storage/logs/laravel.log
```

## Project Structure

```
icsa-international/
├── app/                  # Application code
├── bootstrap/            # Framework bootstrap
├── config/               # Configuration files
├── database/             # Migrations and SQL backup
│   └── icsa_website.sql  # Database backup (if needed)
├── deploy/               # Deployment files
│   └── nginx/            # Nginx config examples
├── public/               # Web root (point nginx here)
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── images/
├── resources/            # Views and assets
├── routes/               # Route definitions
├── storage/              # Logs, cache, uploads (writable)
├── tests/                # Test files
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
├── nginx-local.conf      # Working local config (reference)
└── NGINX-SETUP.md        # Quick setup guide
```

## Security Features

The nginx config already includes:
- Blocks access to sensitive folders (`app/`, `config/`, `storage/`, `vendor/`)
- Hides hidden files (`.env`, `.git`)
- Sets security headers
- Enables caching for static assets

## Files to Give Your Team

1. **This entire project folder** (zip or git clone)
2. **`.env.example`** - they create `.env` from this
3. **`deploy/nginx/icsa-international.conf.example`** - nginx config template
4. **`NGINX-SETUP.md`** - quick reference
5. **`database/icsa_website.sql`** - database backup (if starting fresh)

## Support

If something doesn't work:
1. Check `storage/logs/laravel.log`
2. Check `/var/log/nginx/error.log`
3. Run `sudo nginx -t` to test config
4. Verify PHP-FPM is running: `sudo systemctl status php-fpm`
