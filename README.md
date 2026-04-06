# FlowXfaka

[中文](./README.md) | [English](./README.en.md)

👋 欢迎来到 FlowXfaka

这一切的起因很简单：在使用过市面上各种自助发卡网之后，我萌生了一个强烈的念头，那就是搞一个完全属于个人的发卡站，并用 AI 起了个名字，叫做 FlowXfaka。

但现实很骨感：我对代码一窍不通。

所以，你现在看到的是一个极度离谱但又真实存在的项目：代码是 AI 帮我写的，Bug 是 AI 帮我改的，就连怎么开源、怎么敲 Git 命令上传到这里，也几乎都是 AI 一手包办的。毫不夸张地说，整个代码库里，可能只有你现在正在看的这段简介，含“人”量勉强达到了 50%。

虽然技术上我是个彻底的甩手掌柜，但我把个人极端的审美偏执倾注在了这里。我厌倦了传统发卡网沉闷的工业风，所以 FlowXfaka 的 UI 使用了大量的 **毛玻璃（Glassmorphism）** 元素，主打一个通透、清新，希望能给个人站长和顾客带来完全不一样的视觉体验。

它基于 Laravel 12，目前已经跑通了前台展示、支付、自动发货与后台管理的完整闭环。但由于它本质上是 AI 强行“施法”捏出来的，项目细节依然比较粗糙，仍然需要继续打磨。

如果你喜欢这个颜值，欢迎拿去用；如果你是路过的技术大佬，也非常欢迎提 Issue 捉虫，或者直接提 PR 教教我和我的 AI 该怎么把代码写得更稳一点。

## 特性

- 支持自定义前台模板，可按自身业务风格继续开发和扩展页面。
- 支持站点背景图、品牌图标、文字模式等前台外观配置，便于快速调整展示效果。
- 支持数字商品售卖与自动发货，适用于卡密、兑换码、激活码、账号类等场景。
- 支持支付宝、微信支付接入，满足常见国内收款需求。
- 支持商品、卡密、订单、支付渠道等后台管理，便于日常运营维护。
- 支持浏览器安装与命令行安装，既可直接部署，也可作为源码二次开发。
- 支持自托管部署，方便按自己的服务器环境和业务需求进行控制与扩展。
- 基于 Laravel 12 构建，便于继续开发营销功能、支付方式、主题模板与后台模块。

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
