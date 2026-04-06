# FlowXfaka

[中文](./README.md) | [English](./README.en.md)

FlowXfaka 是一个基于 Laravel 12 的开源发卡系统，面向数字商品售卖、订单支付、自动发货与后台运营管理场景。

## 项目简介

这个项目提供了一套完整的数字商品售卖链路：

- 前台商品展示与下单
- 支付宝 / 微信支付接入
- 支付确认后的自动发货
- 商品、卡密、订单、支付渠道后台管理
- 前台主题、背景图、品牌图标等站点外观配置
- 浏览器安装与 CLI 安装两种初始化方式

## 适用场景

- 卡密、兑换码、激活码等数字商品售卖
- 小型到中型的自动发货站点
- 需要自托管、可二开的 Laravel 发卡系统

## 环境要求

- PHP 8.2+
- MySQL 8+ 或 MariaDB 10.6+
- Composer 2
- Redis 可选，较高流量场景建议启用

## 快速开始

1. 安装依赖

```bash
composer install --no-dev --optimize-autoloader
```

2. 选择一种初始化方式

浏览器安装：

```text
https://your-domain.example/install
```

如果 Web 服务器用户没有项目根目录写权限，请先手动创建一个可写的空 `.env` 文件。

CLI 安装：

```bash
php artisan app:install
```

安装器会自动完成以下动作：

- 写入或更新 `.env`
- 生成 `APP_KEY`
- 执行数据库迁移
- 创建或更新首个管理员账号
- 初始化 `site_settings`
- 写入 `storage/app/install.lock`

## 非交互 CLI 安装

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

如果你希望首次安装就启用 Redis，可额外传入：

```bash
--use-redis --redis-host=127.0.0.1 --redis-port=6379
```

如果 `install.lock` 已存在，需要重新初始化时可使用：

```bash
php artisan app:install --force
```

## 队列与发货

支付确认与自动发货依赖队列 Worker。

数据库队列示例：

```bash
php artisan queue:work database --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

Redis 队列示例：

```bash
php artisan queue:work redis --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

如果你使用 `systemd`，可以基于仓库内的 [flowx-queue.service](./flowx-queue.service) 继续调整。

## 部署说明

- Nginx / Apache 站点根目录必须指向 `public/`
- `public/uploads/` 需要保持可写
- 正式环境请确保 `APP_URL` 与真实域名一致
- 正式环境至少保持一个队列 Worker 常驻
- 如果不开 Redis，也可以使用 MySQL + database queue 完成基础部署

## 常用命令

```bash
php artisan admin:reset-password
php artisan app:install --force
php artisan migrate --force
```

## 开发与贡献

欢迎提交 Issue 和 Pull Request。  
如果你计划长期二开或对外部署，请先阅读许可证要求，并在修改后保留必要的版权与协议说明。

## 许可证

本项目使用 GNU Affero General Public License v3.0 only 发布。

- SPDX 标识符：`AGPL-3.0-only`
- 完整协议文本见：[LICENSE](./LICENSE)
- 如果你修改并通过网络方式部署本项目，需要按 AGPL 要求提供对应源码
