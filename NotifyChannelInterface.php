<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\plugin\notify\dto\NotifyDataDTO;
use RuntimeException;

interface NotifyChannelInterface
{
    /**
     * 发送通知
     * @param  NotifyDataDTO     $data 通知数据
     * @throws RuntimeException 发送失败时抛出异常
     */
    public function send(NotifyDataDTO $data): void;
}
