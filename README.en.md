# FlowXfaka

[中文](./README.md) | [English](./README.en.md)

FlowXfaka is an open-source Laravel 12 based faka system for digital goods sales, payment processing, and automatic card delivery.

## Features

- Digital goods sales and automatic delivery
- Alipay / WeChat Pay integration
- Admin panels for products, cards, and orders
- Storefront theme and brand customization

## Requirements

- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- Redis is optional but recommended for higher traffic

## Release Package Install

1. Download `FlowXfaka-v1.0.0.zip` from Releases
2. Upload and extract it on your server
3. Point your web root to `public/`
4. Open `https://your-domain/install`
5. Keep at least one queue worker running

## Queue

```bash
php artisan queue:work database --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

You can adapt the bundled [flowx-queue.service](./flowx-queue.service) for `systemd`.

## Source Install

```bash
composer install --no-dev --optimize-autoloader
php artisan app:install
```

## License

This project is licensed under GNU Affero General Public License v3.0 only.

- Full license text: [LICENSE](./LICENSE)
