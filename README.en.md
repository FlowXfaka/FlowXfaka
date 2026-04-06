# FlowXfaka

[中文](./README.md) | [English](./README.en.md)

FlowXfaka is an open-source Laravel 12 based faka system for digital goods sales, payment processing, and automatic card delivery.

## Overview

FlowXfaka provides a complete workflow for digital goods storefronts:

- Product listing and checkout
- Alipay / WeChat Pay integration
- Automatic card delivery after payment confirmation
- Admin panels for products, cards, orders, and payment channels
- Storefront theme, background, and brand asset customization
- Browser installer and CLI installer

## Requirements

- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- Composer 2
- Redis is optional but recommended for higher traffic

## Quick Start

1. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

2. Run either installer entry

Browser installer:

```text
https://your-domain.example/install
```

If your web server user cannot write to the project root, create an empty writable `.env` file first.

CLI installer:

```bash
php artisan app:install
```

The installer will:

- write or update `.env`
- generate `APP_KEY`
- run migrations
- create or update the first admin account
- initialize `site_settings`
- write `storage/app/install.lock`

## Non-interactive CLI Install

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

To enable Redis during the initial install, also pass:

```bash
--use-redis --redis-host=127.0.0.1 --redis-port=6379
```

To re-run initialization after `install.lock` exists:

```bash
php artisan app:install --force
```

## Queue Worker

Payment confirmation and automatic delivery rely on a queue worker.

Database queue example:

```bash
php artisan queue:work database --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

Redis queue example:

```bash
php artisan queue:work redis --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

If you use `systemd`, adapt the bundled [flowx-queue.service](./flowx-queue.service) file.

## Deployment Notes

- Point Nginx or Apache to `public/`
- Keep `public/uploads/` writable
- Keep `APP_URL` aligned with your real domain
- Keep at least one queue worker alive in production
- A MySQL + database queue deployment is supported if Redis is not used

## Useful Commands

```bash
php artisan admin:reset-password
php artisan app:install --force
php artisan migrate --force
```

## Contributing

Issues and pull requests are welcome.  
If you modify and deploy this project for network use, make sure your distribution remains compliant with the license terms.

## License

This project is licensed under GNU Affero General Public License v3.0 only.

- SPDX identifier: `AGPL-3.0-only`
- Full license text: [LICENSE](./LICENSE)
