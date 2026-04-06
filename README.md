# FlowXfaka

FlowXfaka is a Laravel 12 based storefront and card delivery system.

## Requirements

- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- Composer 2
- Redis is optional, but recommended for higher traffic

## Fast path

1. Install dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. Run either installer entry:

   Browser:

   ```text
   https://your-domain.example/install
   ```

   If the web server user cannot create new files in the project root, create an empty writable `.env` first.

   CLI:

   ```bash
   php artisan app:install
   ```

The installer will:

- write or update `.env`
- generate `APP_KEY`
- run pending migrations
- create or update the first admin account
- initialize `site_settings`
- write `storage/app/install.lock`

## Redis and fallback mode

Redis is not mandatory for a clean install.

- If Redis is enabled during install:
  - `SESSION_DRIVER=redis`
  - `CACHE_STORE=redis`
  - `QUEUE_CONNECTION=redis`
- If Redis is left off:
  - `SESSION_DRIVER=file`
  - `CACHE_STORE=database`
  - `QUEUE_CONNECTION=database`

This means a bare MySQL-only deployment can still complete successfully.

## Non-interactive CLI install

```bash
php artisan app:install \
  --app-url="https://shop.example.com" \
  --site-name="FlowXfaka" \
  --admin-name="admin" \
  --admin-password="change-this-password" \
  --db-host="127.0.0.1" \
  --db-port=3306 \
  --db-name="flowx" \
  --db-user="flowx" \
  --db-password="secret"
```

Add `--use-redis --redis-host=127.0.0.1 --redis-port=6379` if you want the Redis profile from the first install.

Use `--force` to re-run initialization after `install.lock` already exists.

## Queue worker

Automatic payment confirmation and card delivery rely on a queue worker.

Database queue profile:

```bash
php artisan queue:work database --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

Redis queue profile:

```bash
php artisan queue:work redis --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

If you use `systemd`, adapt the bundled `flowx-queue.service` file.

## Web server

- Point Nginx or Apache to `public/`
- Keep `public/uploads/` writable for image uploads
- Keep `APP_URL` accurate before enabling payment callbacks
- Keep at least one queue worker alive in production

## Useful commands

```bash
php artisan admin:reset-password
php artisan app:install --force
php artisan migrate --force
```

## License

This project is licensed under the GNU Affero General Public License v3.0 only.

- SPDX identifier: `AGPL-3.0-only`
- Full license text: [LICENSE](LICENSE)
- If you modify and deploy this project for network use, you must provide the corresponding source under the same license.
