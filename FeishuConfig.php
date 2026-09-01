<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\ConfigObject;
use RuntimeException;

/**
 * 飞书自定义机器人 Webhook 配置
 */
class FeishuConfig extends ConfigObject
{
    /** @var string 飞书机器人 Webhook 地址 */
    public string $url = '';

    /** @var string 签名校验密钥（可选，开启签名校验时填写） */
    public string $secret = '';

    /** @var int 请求超时时间（秒） */
    public int $timeout = 30;

    public function onValidate(): void
    {
        if (empty($this->url)) {
            throw new RuntimeException('飞书 Webhook URL不能为空');
        }

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('飞书 Webhook URL格式错误');
        }

        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new RuntimeException('飞书超时时间必须在1-300秒之间');
        }
    }
}
