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
 *
 * 负责管理多种通知渠道的发送，支持邮件、企业微信、Webhook等渠道
 * 提供统一的接口进行通知发送，支持单例模式
 *
 * @package nova\plugin\notify
 * @author Nova Framework
 */
class NotifyManager
{
    /**
     * 通知配置实例
     *
     * @var NotifyConfig
     */
    private NotifyConfig $notifyConfig;

    /**
     * 通知渠道映射表
     *
     * 键为渠道类型，值为对应的渠道类名
     *
     * @var array<string, string>
     */
    private array $channelMap;

    /**
     * 构造函数
     *
     * 初始化通知配置和渠道映射
     */
    public function __construct()
    {
        $this->notifyConfig =  new NotifyConfig();
        $this->channelMap = [
            'email' => EmailChannel::class,
            'wechat_work' => WechatWorkChannel::class,
            'webhook' => WebhookChannel::class,
        ];
    }

    /**
     * 获取通知管理器单例实例
     *
     * 使用 Context 确保全局只有一个实例
     *
     * @return NotifyManager 通知管理器实例
     */
    public static function getInstance(): NotifyManager
    {
        return Context::instance()->getOrCreateInstance('notify', function () {
            return new NotifyManager();
        });
    }

    /**
     * 最后一次发送通知时的异常信息
     *
     * @var \Exception|null
     */
    public ?\Exception $exception = null;

    /**
     * 发送通知
     *
     * 根据指定的渠道发送通知，如果未指定渠道则使用默认渠道
     *
     * @param  NotifyDataDTO    $data    通知数据对象
     * @param  string|null      $channel 通知渠道类型，可选值：email、wechat_work、webhook
     * @return bool             发送是否成功
     * @throws AppExitException 当遇到应用退出异常时重新抛出
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

            $this->exception = $e;

            return false;
        }
    }

    /**
     * 从数组发送通知（便捷方法）
     *
     * 将数组数据转换为 NotifyDataDTO 对象后发送通知
     *
     * @param  array       $data    通知数据数组，包含 title、message、type、recipient 等字段
     * @param  string|null $channel 通知渠道类型
     * @return bool        发送是否成功
     */
    public function sendFromArray(array $data, ?string $channel = null): bool
    {
        $dto = NotifyDataDTO::fromArray($data);
        return $this->send($dto, $channel);
    }

    /**
     * 发送测试通知
     *
     * 向指定渠道发送测试通知，用于验证渠道配置是否正确
     *
     * @param  string $channel 通知渠道类型
     * @return bool   发送是否成功
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
     * 获取可用的通知渠道列表
     *
     * @return array<string> 可用的通知渠道类型数组
     */
    public function getAvailableChannels(): array
    {
        return array_keys($this->channelMap);
    }

    /**
     * 获取通知配置实例
     *
     * @return NotifyConfig 通知配置对象
     */
    public function getConfig(): NotifyConfig
    {
        return $this->notifyConfig;
    }
}
