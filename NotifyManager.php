<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\exception\AppExitException;
use nova\plugin\mail\MailConfig;
use nova\plugin\notify\channels\EmailChannel;
use nova\plugin\notify\channels\WebhookChannel;
use nova\plugin\notify\channels\WechatWorkChannel;
use nova\plugin\notify\dto\NotifyDataDTO;

/**
 * 精简版通知管理器
 */
class NotifyManager
{
    private NotifyConfig $notifyConfig;
    private array $channelMap;

    public function __construct()
    {
        $this->notifyConfig =  new NotifyConfig();
        $this->channelMap = [
            'email' => EmailChannel::class,
            'wechat_work' => WechatWorkChannel::class,
            'webhook' => WebhookChannel::class,
        ];
    }

    public static function getInstance(): NotifyManager
    {
        return Context::instance()->getOrCreateInstance('notify', function () {
            return new NotifyManager();
        });
    }

    /**
     * 发送通知
     */
    public function send(NotifyDataDTO $data, ?string $channel = null): bool
    {
        $channelType = $channel ?? $this->notifyConfig->default_channel ?? 'email';

        if (!isset($this->channelMap[$channelType])) {
            Logger::error("无效的通知渠道: {$channelType}");
            return false;
        }

        try {
            $channelClass = $this->channelMap[$channelType];
            $channelInstance = new $channelClass();
            $channelInstance->send($data);

            Logger::info("通知发送成功: {$channelType}");
            return true;
        } catch (\Exception $e) {
            if ($e instanceof  AppExitException) {
                throw $e;
            }

            Logger::error("通知发送失败: {$channelType} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * 从数组发送通知（便捷方法）
     */
    public function sendFromArray(array $data, ?string $channel = null): bool
    {
        $dto = NotifyDataDTO::fromArray($data);
        return $this->send($dto, $channel);
    }

    /**
     * 发送测试通知
     */
    public function test(string $channel): bool
    {
        $recipient = match ($channel) {
            'email' => (new MailConfig())->defaultRecipient,
            'wechat_work' => (new WechatConfig())->default_recipient,
            'webhook' => 'test-recipient',
            default => ''
        };

        $dto = new NotifyDataDTO([
            'title' => '测试通知',
            'message' => '这是一个测试通知',
            'type' => 'default',
            'recipient' => $recipient,
        ]);

        return $this->send($dto, $channel);
    }

    /**
     * 获取可用的通知渠道
     */
    public function getAvailableChannels(): array
    {
        return array_keys($this->channelMap);
    }

    /**
     * 获取配置
     */
    public function getConfig(): NotifyConfig
    {
        return $this->notifyConfig;
    }
}
