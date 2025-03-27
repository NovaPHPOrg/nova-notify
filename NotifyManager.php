<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use app\task\NotifyTasker;
use http\Exception\RuntimeException;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\plugin\notify\adapter\NotifyChannelAdapter;
use nova\plugin\notify\channels\EmailChannel;
use nova\plugin\notify\channels\WechatWorkChannel;
use nova\plugin\notify\db\Dao\NotifyChannelDao;
use nova\plugin\notify\db\Dao\NotificationDao;
use nova\plugin\notify\db\Model\NotificationModel;
use nova\plugin\notify\dto\NotifyDataDTO;
use function nova\framework\config;

/**
 * 通知管理器 V2
 *
 * 使用 NotifyDataDTO 标准化数据结构的通知管理器
 */
class NotifyManager
{

    /**
     * @var string 默认使用的通知渠道
     */
    private string $defaultChannel = '';

    /**
     * 构造函数 - 注册通知渠道
     */
    public function __construct()
    {

        // 设置默认通知渠道
        $this->defaultChannel = config('notify.default_channel') ?? 'email';
    }


    static function getInstance(): NotifyManager
    {
        return Context::instance()->getOrCreateInstance('notify', function () {
            return new NotifyManager();
        });
    }


    private array $channelMap = [
        'email' => EmailChannel::class,
        'wechat_work' => WechatWorkChannel::class,
    ];

    private function getChannel(?string $type = null): NotifyChannelInterface
    {
        if (empty($type)) {
            $type = $this->defaultChannel;
        }
        if (!array_key_exists($type, $this->channelMap)) {
            throw new RuntimeException("无效的通知渠道");
        }
        $channel = $this->channelMap[$type];

        return new $channel();
    }


    /**
     * 获取默认的通知渠道
     */
    public function getDefaultChannel(): string
    {
        return $this->defaultChannel;
    }

    /**
     * 发送通知
     *
     * @param NotifyDataDTO $data 标准化通知数据
     * @param string|null $channel 通知渠道，为null时使用默认渠道
     * @return bool 是否发送成功
     */
    public function send(NotifyDataDTO $data, ?string $channel = null): ?string
    {
        $channelType = $channel ?? $this->defaultChannel;

        $channelConfig = NotifyChannelDao::getInstance()->getChannelByType($channelType);
        if (!$channelConfig || !$channelConfig->isActive()) {
            Logger::error("通知渠道 {$channelType} 未配置或未激活");
            return "通知渠道 {$channelType} 未配置或未激活";
        }

        // 创建通知记录
        $notification = new NotificationModel();
        $notification->type = $channelType;
        $notification->created_at = date('Y-m-d H:i:s');
        $notification->data = $data;

        try {
            ob_start();
            $this->getChannel($channelType)->send($channelConfig, $data);
            $result = ob_get_clean();
            $notification->status = 1;
            $notification->error = $result;
            NotificationDao::getInstance()->insertModel($notification);
            Logger::info(" {$channelType} 通知发送成功");
            return null;
        } catch (\RuntimeException $e) {
            $notification->status = 0;
            $notification->error = $e->getMessage();
            NotificationDao::getInstance()->insertModel($notification);
            Logger::error("{$channelType} 通知发送失败: " . $e->getMessage());
            return "{$channelType} 通知发送失败: " . $e->getMessage();
        }
    }
    public function test(string $channel): ?string
    {
        $dto = new NotifyDataDTO();
        $dto->type = $channel;
        $dto->title = 'test';
        $dto->message = "这是一个测试通知\n测试 **xxxx** ";
        $dto->actionLeftUrl = Context::instance()->request()->getBasicAddress();
        $dto->actionLeftText = '知道了';


        $dto->actionRightUrl = Context::instance()->request()->getBasicAddress();
        $dto->actionRightText = '退下吧';
        return $this->send($dto, $channel);
    }
} 