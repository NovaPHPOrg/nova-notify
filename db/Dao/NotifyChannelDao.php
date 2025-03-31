<?php

declare(strict_types=1);

namespace nova\plugin\notify\db\Dao;

use nova\plugin\notify\db\Model\NotifyChannelModel;
use nova\plugin\orm\object\Dao;

class NotifyChannelDao extends Dao
{
    public function onCreateTable(): void
    {
        // 创建默认的邮件通道
        $email = new NotifyChannelModel();
        $email->type = 'email';
        $email->name = '邮件通知';
        $email->config = [
            'host' => '',
            'port' => 465,
            'user' => '',
            'pass' => '',
            'default_recipient' => '',
            'name' => ''
        ];
        $email->created_at = time();
        $this->insertModel($email);

        // 创建默认的企业微信通道
        $wechatWork = new NotifyChannelModel();
        $wechatWork->type = 'wechat_work';
        $wechatWork->name = '企业微信通知';
        $wechatWork->config = [
            'corp_id' => '',      // 企业ID
            'corp_secret' => '',  // 应用Secret
            'agent_id' => ''  ,    // 应用ID
            'default_recipient' => ''
        ];
        $wechatWork->created_at = time();
        $wechatWork->status = 0;  // 默认禁用
        $this->insertModel($wechatWork);

    }

    /**
     * 根据类型获取通知渠道
     * @param  string                  $type
     * @return NotifyChannelModel|null
     */
    public function getChannelByType(string $type): ?NotifyChannelModel
    {
        return $this->find(null, ['type' => $type]);
    }

}
