<?php

declare(strict_types=1);

namespace nova\plugin\notify\controller;

use nova\framework\http\Response;
use nova\plugin\login\controller\BaseAPIController;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\FeishuConfig;
use nova\plugin\notify\NotifyManager;

class Feishu extends BaseAPIController
{
    public function config(): Response
    {
        $config = new FeishuConfig();

        if ($this->request->isGet()) {
            return Response::asJson([
                'code' => 200,
                'data' => get_object_vars($config),
            ]);
        }

        $config->url = $this->request->post('url', $config->url);
        $config->secret = $this->request->post('secret', $config->secret);
        $config->timeout = (int)$this->request->post('timeout', $config->timeout);

        return Response::asJson([
            'code' => 200,
            'msg' => '飞书配置保存成功',
        ]);
    }

    public function test(): Response
    {
        $notify = NotifyManager::getInstance();

        $dto = new NotifyDataDTO([
            'title' => '飞书测试',
            'message' => '这是一条测试通知，用于验证飞书 Webhook 配置是否正确。',
            'type' => NotifyDataDTO::TYPE_SUCCESS,
            'recipient' => 'feishu',
        ]);

        $result = $notify->send($dto, 'feishu');

        if ($result) {
            return Response::asJson([
                'code' => 200,
                'msg' => '测试通知发送成功',
            ]);
        }

        return Response::asJson([
            'code' => 500,
            'msg' => '测试通知发送失败: ' . ($notify->exception?->getMessage() ?? 'unknown'),
        ]);
    }
}
