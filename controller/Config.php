<?php

declare(strict_types=1);

namespace nova\plugin\notify\controller;

use nova\framework\http\Response;
use nova\plugin\login\controller\BaseAPIController;
use nova\plugin\notify\NotifyConfig;

class Config extends BaseAPIController
{
    public function config(): Response
    {
        $config = new NotifyConfig();

        if ($this->request->isGet()) {
            return Response::asJson([
                'code' => 200,
                'msg' => '获取配置成功',
                'data' => [
                    'default_channel' => $config->default_channel,
                ],
            ]);
        }

        $config->default_channel = $this->request->post('default_channel', $config->default_channel);

        return Response::asJson([
            'code' => 200,
            'msg' => '配置保存成功',
        ]);
    }
}
