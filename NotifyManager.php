<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use Exception;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\exception\AppExitException;
use nova\plugin\mail\MailConfig;
use nova\plugin\notify\channels\EmailChannel;
use nova\plugin\notify\channels\FeishuChannel;
use nova\plugin\notify\channels\WebhookChannel;
use nova\plugin\notify\dto\NotifyDataDTO;

/**
 * 精简版通知管理器
 *
 * 负责管理多种通知渠道的发送，支持邮件、Webhook、飞书等渠道
 * 提供统一的接口进行通知发送，支持单例模式
 *
 * @package nova\plugin\notify
 * @author Nova Framework
 */
class NotifyManager
{
    /**
     * 已注册渠道名列表（供配置校验复用，避免各处硬编码分叉）
     */
    public const CHANNELS = ['email', 'webhook', 'feishu'];

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
            'webhook' => WebhookChannel::class,
            'feishu' => FeishuChannel::class,
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
     * @var Exception|null
     */
    public ?Exception $exception = null;

    /**
     * 发送通知
     *
     * 根据指定的渠道发送通知，如果未指定渠道则使用默认渠道
     *
     * @param  NotifyDataDTO    $data    通知数据对象
     * @param string|null $channel 通知渠道类型，可选值：email、webhook、feishu
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
        } catch (Exception $e) {
            if ($e instanceof  AppExitException) {
                throw $e;
            }

            $this->exception = $e;

            return false;
        }
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
            'webhook', 'feishu' => 'test-recipient',
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

}
