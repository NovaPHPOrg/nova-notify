<?php

declare(strict_types=1);

namespace nova\plugin\notify\db\Model;

use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\orm\object\Model;

class NotificationModel extends Model
{
    public string $type = '';         // 通知类型：wechat=微信，email=邮件等
    public int $status = 0;           // 状态：0=失败，1=成功
    public string $created_at = '';   // 创建时间
    public ?string $error = null;     // 错误信息
    public ?NotifyDataDTO $data = null;

    public function getUnique(): array
    {
        return [];
    }

    public function getNoEscape(): array
    {
        return ['error'];
    }

    public function isSuccess(): bool
    {
        return $this->status === 1;
    }
}
