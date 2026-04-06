# FlowXfaka

[中文](./README.md) | [English](./README.en.md)

FlowXfaka 是一个基于 Laravel 12 的开源发卡系统，面向数字商品售卖、订单支付、自动发货与后台运营管理场景。

## 特性

- 数字商品售卖与自动发货
- 支付宝 / 微信支付接入
- 商品、卡密、订单后台管理
- 前台主题与站点外观配置

## 环境要求

- PHP 8.2+
- MySQL 8+ 或 MariaDB 10.6+
- Redis 可选，较高流量场景建议启用

## 部署包安装

1. 下载 Release 里的 `FlowXfaka-v1.0.0.zip`
2. 上传并解压到服务器
3. Nginx / Apache 根目录指向 `public/`
4. 访问 `https://你的域名/install`
5. 至少保持一个队列 Worker 常驻

## 伪静态

Nginx：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Apache：根目录指向 `public/`，并启用 `mod_rewrite` 即可。

## 队列

```bash
php artisan queue:work database --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90
```

可基于仓库内的 [flowx-queue.service](./flowx-queue.service) 配置常驻任务。

## 源码部署

```bash
composer install --no-dev --optimize-autoloader
php artisan app:install
```

如需非交互安装，可查看 [README.en.md](./README.en.md) 中的 CLI 示例。

## 许可证

本项目使用 GNU Affero General Public License v3.0 only 发布。

- 协议文本见：[LICENSE](./LICENSE)
