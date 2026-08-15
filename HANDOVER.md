# ICSA Website - Nginx Handover Notes

## Quick Start

1. Copy nginx config:
   ```bash
   sudo cp deploy/nginx/icsa-international.conf.example /etc/nginx/sites-available/icsa-international
   sudo ln -s /etc/nginx/sites-available/icsa-international /etc/nginx/sites-enabled/
   ```

2. Edit the config and update:
   - `server_name` → your domain (e.g., `icsakuwait.com`)
   - `root` → full path to `public/` folder
   - `fastcgi_pass` → your PHP-FPM socket/port

3. Set permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache  # adjust user as needed
   ```

4. Configure `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Then edit `.env`:
   - `APP_URL=https://your-domain.com` (must match nginx server_name)
   - `APP_DEBUG=false` (for production)
   - Database credentials
   - `ADMIN_DEFAULT_PASSWORD` (change this!)

5. Restart services:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   sudo systemctl restart php8.2-fpm  # or your PHP version
   ```

## Project Structure

- **Web root:** `public/` (not the project root)
- **Nginx config:** `deploy/nginx/icsa-international.conf.example`
- **Admin URL:** `{APP_URL}/{ADMIN_PORTAL_PATH}/login` (default: `/secure-staff-portal/login`)
- **Default admin:** username `admin`, password from `.env`

## Important Notes

- **Security:** Config already blocks access to `app/`, `config/`, `storage/`, `vendor/`, etc.
- **Assets:** Uses `asset()` helper - works on any domain/subdirectory
- **Subdirectory support:** If deploying to `https://domain.com/subdir/`, set `APP_URL=https://domain.com/subdir`
- **Database:** Migration + seeder already run, SQL backup in `database/icsa_website.sql`

## Troubleshooting

- **404 errors:** Check `root` path points to `public/` folder
- **Permission denied:** Check `storage/` and `bootstrap/cache/` are writable by web server
- **Blank page:** Check `APP_DEBUG=true` temporarily to see error, check logs in `storage/logs/`
- **CSS/JS not loading:** Check `APP_URL` matches actual URL exactly

## Files Modified for Nginx

- `.env.example` - updated `APP_URL` default
- `README.md` - URLs updated to `/icsa-international`
- `deploy/nginx/icsa-international.conf.example` - added security rules
