<?php

declare(strict_types=1);

namespace nova\plugin\notify\controller;

use nova\framework\event\EventManager;
use nova\framework\http\Response;
use nova\plugin\login\controller\BaseAPIController;
use nova\plugin\notify\NotifyConfig;

class Config extends BaseAPIController
{
    public function config(): Response
    {
        $config = new NotifyConfig();
        // 业务扩展点：监听 notify.config.get/save 可读取、保存额外配置项（如事件开关）
        $ext = ['request' => $this->request, 'data' => []];

        if ($this->request->isGet()) {
            EventManager::trigger('notify.config.get', $ext);
            return Response::asJson([
                'code' => 200,
                'msg' => '获取配置成功',
                'data' => ['default_channel' => $config->default_channel] + $ext['data'],
            ]);
        }

        $config->default_channel = $this->request->post('default_channel', $config->default_channel);
        EventManager::trigger('notify.config.save', $ext);

        return Response::asJson([
            'code' => 200,
            'msg' => '配置保存成功',
        ]);
    }
}
