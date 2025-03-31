<?php

declare(strict_types=1);

namespace nova\plugin\notify\db\Dao;

use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    public function getNotifyStats(): array
    {

        $todayStart = strtotime(date('Y-m-d', time()));

        return [
            'total' => $this->getCount([
                'created_at>:time',
                ':time' => $todayStart,

            ]),
            'success' => $this->getCount([
                'created_at>:time',
                ':time' => $todayStart,
                'status' => 1
            ]),
            'failed' => $this->getCount([
                'created_at>:time',
                ':time' => $todayStart,
                'status' => 0
            ]),
        ];
    }

}
