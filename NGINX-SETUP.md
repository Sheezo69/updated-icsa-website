# Nginx Setup (Simple)

## 1. Copy & Edit Config
```bash
sudo cp deploy/nginx/icsa-international.conf.example /etc/nginx/sites-available/icsa
sudo nano /etc/nginx/sites-available/icsa
```
Change these 3 lines:
- `server_name` → your domain
- `root` → full path to `public/` folder  
- `fastcgi_pass` → your PHP socket (usually `unix:/run/php/php8.2-fpm.sock`)

## 2. Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/icsa /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 3. Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

## 4. Configure .env
```bash
cp .env.example .env
php artisan key:generate
```
Edit `.env`:
```
APP_URL=https://your-domain.com
APP_DEBUG=false
ADMIN_DEFAULT_PASSWORD=your-secure-password
```

## Done
- Website: https://your-domain.com
- Admin: https://your-domain.com/secure-staff-portal/login
- Default login: admin / (password from .env)
