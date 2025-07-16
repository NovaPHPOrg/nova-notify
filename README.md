# 精简版通知模块

## 概述

这是一个精简的通知模块，支持邮件、企业微信和Webhook通知。使用DTO提供类型安全和更好的代码可读性，同时保持简洁的设计。配置使用独立的配置类管理。

## 配置

### 通知模块配置 (NotifyConfig)

```php
// config.php
return [
    'notify' => [
        'default_channel' => 'email', // 默认通知渠道: email, wechat_work, webhook
    ]
];
```

### 邮件配置 (MailConfig)

复用现有的邮件模块配置：

```php
// config.php
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

### 企业微信配置 (WechatConfig)

```php
// config.php
return [
    'wechat' => [
        'corp_id' => 'your-corp-id',
        'corp_secret' => 'your-corp-secret',
        'agent_id' => 'your-agent-id',
        'default_recipient' => 'user-id'
    ]
];
```

### Webhook配置 (WebhookConfig)

```php
// config.php
return [
    'webhook' => [
        'url' => 'https://your-webhook-url.com/api/notify',
        'auth_header' => 'Authorization: Bearer your-token', // 可选
        'timeout' => 30 // 超时时间（秒）
    ]
];
```

## 使用方法

### 使用DTO（推荐）

```php
use nova\plugin\notify\NotifyManager;
use nova\plugin\notify\dto\NotifyDataDTO;

$notify = NotifyManager::getInstance();

// 创建DTO
$dto = new NotifyDataDTO([
    'title' => '订单通知',
    'message' => '您有一个新订单',
    'type' => 'success',
    'recipient' => 'user@example.com'
]);

// 发送邮件通知
$result = $notify->send($dto, 'email');

// 发送企业微信通知
$result = $notify->send($dto, 'wechat_work');

// 发送Webhook通知
$result = $notify->send($dto, 'webhook');
```

### 使用数组（便捷方法）

```php
// 直接使用数组发送
$result = $notify->sendFromArray([
    'title' => '测试通知',
    'message' => '这是一个测试',
    'type' => 'default',
    'recipient' => 'user@example.com'
], 'webhook');
```

### 带动作按钮的通知

```php
$dto = new NotifyDataDTO([
    'title' => '新订单',
    'message' => '您有一个新订单需要处理',
    'type' => 'success',
    'recipient' => 'admin@example.com',
    'actionLeftUrl' => 'https://example.com/orders/123',
    'actionLeftText' => '查看订单',
    'actionRightUrl' => 'https://example.com/orders/123/approve',
    'actionRightText' => '批准订单'
]);

$notify->send($dto, 'webhook');
```

### 测试通知

```php
// 测试邮件通知
$notify->test('email');

// 测试企业微信通知
$notify->test('wechat_work');

// 测试Webhook通知
$notify->test('webhook');
```

## 通知类型

支持的通知类型：

- `default` - 默认通知（蓝色）
- `success` - 成功通知（绿色）
- `warning` - 警告通知（橙色）
- `error` - 错误通知（红色）

## DTO字段说明

```php
class NotifyDataDTO
{
    public string $title;           // 通知标题
    public string $message;         // 通知内容
    public string $type = 'default'; // 通知类型
    public ?string $recipient = null; // 收件人
    public ?string $actionLeftUrl = null;   // 左侧按钮链接
    public ?string $actionLeftText = null;  // 左侧按钮文本
    public ?string $actionRightUrl = null;  // 右侧按钮链接
    public ?string $actionRightText = null; // 右侧按钮文本
}
```

## Webhook数据格式

Webhook渠道会向指定URL发送POST请求，数据格式如下：

```json
{
    "title": "通知标题",
    "message": "通知内容",
    "type": "success",
    "recipient": "收件人",
    "actionLeftUrl": "左侧按钮链接",
    "actionLeftText": "左侧按钮文本",
    "actionRightUrl": "右侧按钮链接",
    "actionRightText": "右侧按钮文本",
    "timestamp": 1640995200,
    "channel": "webhook"
}
```

## 配置类说明

### NotifyConfig
- `default_channel` - 默认通知渠道

### MailConfig (复用邮件模块)
- `host` - SMTP服务器地址
- `port` - SMTP端口
- `username` - 邮箱用户名
- `password` - 邮箱密码
- `site` - 发件人名称

### WechatConfig
- `corp_id` - 企业微信企业ID
- `corp_secret` - 企业微信应用Secret
- `agent_id` - 企业微信应用ID
- `default_recipient` - 默认收件人

### WebhookConfig
- `url` - Webhook接收地址
- `auth_header` - 认证头部（可选）
- `timeout` - 请求超时时间（秒）

## 优势

1. **类型安全** - 使用DTO提供编译时类型检查
2. **代码可读性** - 属性访问比数组键更直观
3. **IDE支持** - 更好的自动完成和重构支持
4. **配置分离** - 每个模块使用独立的配置类
5. **复用现有配置** - 邮件配置复用MailConfig
6. **灵活扩展** - 支持多种通知渠道，包括Webhook
7. **性能更好** - 移除了数据库操作和复杂的输出缓冲
8. **易于扩展** - 简单的接口设计，容易添加新的通知渠道