# Nova 多渠道通知插件

这是一个支持多种通知渠道的 Nova 框架插件。通过此插件，您可以方便地向用户发送不同类型的通知，如邮件、微信、企业微信和微信公众号等。

## 功能特点

- 支持多种通知渠道：邮件、微信、企业微信、微信公众号等
- 基于数据库的渠道管理，可在后台动态启用/禁用渠道
- 通知状态跟踪和失败重试机制
- 批量发送通知支持
- 通知模板支持

## 安装

通过 Composer 安装：

```bash
composer require nova/plugin-notify
```

## 配置

在 `config/notify.php` 中添加以下配置：

```php
return [
    // 默认通知渠道
    'default_channel' => 'email',
    
    // 邮件配置（可选，优先使用数据库中的配置）
    'email' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your-email@example.com',
        'password' => 'your-password',
        'from' => [
            'address' => 'noreply@example.com',
            'name' => '通知系统'
        ]
    ],
    
    // 微信配置（可选，优先使用数据库中的配置）
    'wechat' => [
        'corp_id' => 'your-corp-id',
        'corp_secret' => 'your-corp-secret',
        'agent_id' => 'your-agent-id'
    ]
];
```

## 数据库配置

通知渠道配置存储在数据库中，创建表时会自动生成默认的渠道配置。您可以在后台管理界面修改这些配置并启用/禁用相应的渠道。

默认生成的渠道包括：
- 邮件通知 (email)
- 微信通知 (wechat)
- 企业微信通知 (wechat_work)
- 微信公众号通知 (wechat_gzh)

## 渠道管理

通过 NotifyChannelDao 可以管理通知渠道：

```php
use nova\plugin\notify\db\Dao\NotifyChannelDao;

// 获取所有启用的渠道
$activeChannels = NotifyChannelDao::getInstance()->getActiveChannels();

// 切换渠道状态
NotifyChannelDao::getInstance()->toggleChannelStatus(1, true);  // 启用ID为1的渠道
NotifyChannelDao::getInstance()->toggleChannelStatus(2, false); // 禁用ID为2的渠道

// 更新渠道配置
$config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    // 其他配置...
];
NotifyChannelDao::getInstance()->updateChannelConfig(1, $config);
```

## 使用示例

### 发送单个通知

```php
use nova\plugin\notify\NotifyManager;

// 实例化通知管理器
$notifyManager = new NotifyManager();

// 发送单个通知（使用默认渠道）
$businessId = '2024052500001';
$data = [
    'business_id' => $businessId,
    'notification_type' => 'success',
    'title' => '操作成功',
    'content' => '您的操作已成功完成',
    'details' => '操作详情内容...',
    'email' => 'user@example.com',
    'open_id' => 'ox-xxxxx', // 微信公众号OpenID
    'user_id' => 'user123', // 企业微信用户ID
    'url' => 'https://example.com/details/12345', // 通知跳转链接
    'remark' => '感谢您的使用！' // 备注信息
];

// 使用默认渠道发送
$success = $notifyManager->send($businessId, null, $data);

// 使用指定渠道发送
$success = $notifyManager->send($businessId, 'email', $data);
$success = $notifyManager->send($businessId, 'wechat', $data);
$success = $notifyManager->send($businessId, 'wechat_work', $data);
$success = $notifyManager->send($businessId, 'wechat_gzh', $data);
```

### 同时发送多个渠道通知

```php
// 同时通过多个渠道发送通知
$channels = ['email', 'wechat', 'wechat_work', 'wechat_gzh'];
$results = $notifyManager->sendMultiple($businessId, $channels, $data);

// 检查结果
foreach ($results as $channel => $success) {
    echo "通过 {$channel} 发送通知: " . ($success ? '成功' : '失败') . "\n";
}

// 通过所有启用的渠道发送通知
$results = $notifyManager->sendMultiple($businessId, [], $data);
```

### 不同类型的通知示例

```php
// 成功通知
$successData = [
    'notification_type' => 'success',
    'title' => '操作成功',
    'content' => '您的操作已成功完成',
    'details' => '详细信息...'
];

// 警告通知
$warningData = [
    'notification_type' => 'warning',
    'title' => '系统警告',
    'content' => '系统检测到异常行为',
    'details' => '异常详情...'
];

// 错误通知
$errorData = [
    'notification_type' => 'error',
    'title' => '错误提醒',
    'content' => '系统发生错误',
    'details' => '错误详情...'
];

// 自定义内容通知
$customData = [
    'notification_type' => 'default',
    'title' => '自定义通知',
    'content' => '这是一条完全自定义的通知内容'
];

$notifyManager->send($businessId, 'email', $successData);
$notifyManager->send($businessId, 'wechat_work', $warningData);
$notifyManager->send($businessId, 'wechat_gzh', $errorData);
$notifyManager->send($businessId, 'wechat', $customData);
```

### 重试失败的通知

```php
// 重试失败的通知
$results = $notifyManager->retryFailedNotifications(10, 30); // 重试最多10条，30分钟前失败的通知
```

### 查询通知记录

```php
// 获取特定业务ID的通知记录
$notifications = $notifyManager->getNotifications($businessId);

// 获取失败的通知记录
$failedNotifications = $notifyManager->getFailedNotifications(10);
```

### 注册自定义通知渠道

```php
// 注册自定义通知渠道
$notifyManager->registerChannel('custom_channel', new YourCustomChannel());

// 发送自定义渠道通知
$notifyManager->send($businessId, 'custom_channel', $data);
```

## 扩展新的通知渠道

1. 创建一个实现 `NotifyChannelInterface` 接口的类：

```php
use nova\plugin\notify\NotifyChannelInterface;
use nova\plugin\notify\db\Model\NotifyChannelModel;

class YourCustomChannel implements NotifyChannelInterface
{
    public function send(NotifyChannelModel $channel, array $data): void
    {
        // 实现您的通知发送逻辑...
    }
}
```

2. 在数据库中添加新的通知渠道配置：

```php
use nova\plugin\notify\db\Model\NotifyChannelModel;
use nova\plugin\notify\db\Dao\NotifyChannelDao;

$customChannel = new NotifyChannelModel();
$customChannel->type = 'custom_channel';
$customChannel->name = '自定义通知渠道';
$customChannel->config = [
    // 渠道配置...
];
$customChannel->created_at = date('Y-m-d H:i:s');
$customChannel->status = 1; // 启用
NotifyChannelDao::getInstance()->insertModel($customChannel);
```

3. 在 `NotifyManager` 类中注册新渠道的映射：

```php
// 在 NotifyManager::registerAvailableChannels() 方法中添加映射
$channelMap = [
    // 已有映射...
    'custom_channel' => YourCustomChannel::class,
];
```

## 许可证

MIT