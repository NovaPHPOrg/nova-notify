<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\ConfigObject;

class WechatConfig extends ConfigObject
{
    public string $corp_id = '';
    public string $corp_secret = '';
    public string $agent_id = '';
    public string $default_recipient = '';

    /**
     * 验证配置
     */
    public function onValidate(): void
    {
        if (empty($this->corp_id)) {
            throw new \RuntimeException('企业微信企业ID不能为空');
        }
        if (empty($this->corp_secret)) {
            throw new \RuntimeException('企业微信应用Secret不能为空');
        }
        if (empty($this->agent_id)) {
            throw new \RuntimeException('企业微信应用ID不能为空');
        }
    }
} 