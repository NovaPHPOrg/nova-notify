<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\ConfigObject;

class WebhookConfig extends ConfigObject
{
    public string $url = '';
    public string $auth_header = '';
    public int $timeout = 30;

    /**
     * 验证配置
     */
    public function onValidate(): void
    {
        if (empty($this->url)) {
            throw new \RuntimeException('Webhook URL不能为空');
        }
        
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Webhook URL格式错误');
        }
        
        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new \RuntimeException('Webhook超时时间必须在1-300秒之间');
        }
    }
} 