<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\ConfigObject;

class NotifyConfig extends ConfigObject
{
    public string $default_channel = 'email';

    /**
     * 验证配置
     */
    public function onValidate(): void
    {
        if (!in_array($this->default_channel, ['email', 'webhook'])) {
            throw new \RuntimeException('无效的默认通知渠道: ' . $this->default_channel);
        }
    }
}
