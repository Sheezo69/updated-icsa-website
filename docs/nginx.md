# Nginx Setup

This project runs well on Nginx as a normal Laravel app.

## 1. Use the `public/` directory as the web root

Set your Nginx `root` to:

```text
/path/to/project/public
```

Do not point Nginx at the project root.

## 2. Use the example server block

Start from:

```text
deploy/nginx/icsa-international.conf.example
```

Update these values for your machine:

- `server_name`
- `root`
- `fastcgi_pass`
- log file paths if needed

## 3. Match `APP_URL` to your Nginx host

In `.env`:

```dotenv
APP_URL=https://your-domain.example
```

If you use a local host such as `http://icsa-international.test`, update `APP_URL` to match it exactly.

## 4. Make sure writable directories stay writable

Laravel needs write access to:

- `storage/`
- `bootstrap/cache/`

Example:

```bash
chmod -R 775 storage bootstrap/cache
```

If your Nginx or PHP-FPM user differs from your shell user, adjust ownership as needed for your system.

## 5. Restart services

After enabling the site, reload Nginx and restart PHP-FPM.

## Notes

- The frontend now resolves asset and API paths dynamically, so it works from either a root domain or a subdirectory.
- The old root `index.php` can stay in the repo for Apache/XAMPP compatibility, but Nginx should serve `public/index.php` through the `public/` web root.
