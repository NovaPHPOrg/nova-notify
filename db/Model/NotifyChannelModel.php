<?php

declare(strict_types=1);

namespace nova\plugin\notify\db\Model;

use nova\plugin\orm\object\Model;

class NotifyChannelModel extends Model
{
    public int $id = 0;
    public string $type = '';       // 渠道类型：wechat, email
    public string $name = '';       // 渠道名称
    public array $config = [];      // 渠道配置（JSON存储）
    public int $status = 1;         // 状态：0=禁用，1=启用
    public int $created_at = 0; // 创建时间

    public function getUnique(): array
    {
        return ['type'];
    }

    public function getNoEscape(): array
    {
        return ['config'];
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
