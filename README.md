# 精简版通知模块

## 概述

通知模块支持邮件、通用 Webhook、飞书自定义机器人。DTO 保证类型安全，各渠道用独立 Config 类管理。

## 配置

### 通知模块配置 (NotifyConfig)

```php
// config.php
return [
    'notify' => [
        'default_channel' => 'email', // email | webhook | feishu
    ]
];
```

### 邮件配置 (MailConfig)

复用邮件模块配置：

```php
return [
    'mail' => [
        'host' => 'smtp.qq.com',
        'port' => 465,
        'username' => 'your-email@qq.com',
        'password' => 'your-password',
        'site' => '系统通知'
    ]
];
```

### Webhook配置 (WebhookConfig)

```php
return [
    'webhook' => [
        'url' => 'https://your-webhook-url.com/api/notify',
        'auth_header' => 'Authorization: Bearer your-token', // 可选
        'timeout' => 30
    ]
];
```

### 飞书配置 (FeishuConfig)

```php
return [
    'feishu' => [
        'url' => 'https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx',
        'secret' => '', // 可选，开启签名校验时填写
        'timeout' => 30
    ]
];
```

## 使用方法

```php
use nova\plugin\notify\NotifyManager;
use nova\plugin\notify\dto\NotifyDataDTO;

$notify = NotifyManager::getInstance();

$dto = new NotifyDataDTO([
    'title' => '订单通知',
    'message' => '您有一个新订单',
    'type' => 'success',
    'recipient' => 'user@example.com'
]);

$notify->send($dto, 'email');
$notify->send($dto, 'webhook');
$notify->send($dto, 'feishu');
```

### 带动作按钮

```php
$dto = new NotifyDataDTO([
    'title' => '新订单',
    'message' => '您有一个新订单需要处理',
    'type' => 'success',
    'actionLeftUrl' => 'https://example.com/orders/123',
    'actionLeftText' => '查看订单',
    'actionRightUrl' => 'https://example.com/orders/123/approve',
    'actionRightText' => '批准订单'
]);

$notify->send($dto, 'feishu');
```

### 测试

```php
$notify->test('email');
$notify->test('webhook');
$notify->test('feishu');
```

## 通知类型

- `default` - 默认（蓝色）
- `success` - 成功（绿色）
- `warning` - 警告（橙色）
- `error` - 错误（红色）

飞书渠道会把类型映射到卡片 header template。

## 飞书消息格式

飞书渠道向 Webhook 发送 `interactive` 卡片：

- 标题 → card.header
- 内容 → lark_md（支持飞书 Markdown 子集）
- 动作按钮 → 跳转 button（Webhook 机器人不支持回调交互）
- 配置了 `secret` 时自动附加 `timestamp` / `sign`

## 后台入口

- `/notify/config` — 默认渠道
- `/notify/webhook` — 通用 Webhook
- `/notify/feishu` — 飞书机器人
