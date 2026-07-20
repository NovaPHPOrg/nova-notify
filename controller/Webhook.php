<?php

declare(strict_types=1);

namespace nova\plugin\notify\controller;

use nova\framework\http\Response;
use nova\plugin\login\controller\BaseAPIController;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\NotifyManager;
use nova\plugin\notify\WebhookConfig;

class Webhook extends BaseAPIController
{
    public function config(): Response
    {
        $webhookConfig = new WebhookConfig();

        if ($this->request->isGet()) {
            return Response::asJson([
                'code' => 200,
                'data' => get_object_vars($webhookConfig),
            ]);
        }

        $webhookConfig->url = $this->request->post('url', $webhookConfig->url);
        $webhookConfig->auth_header = $this->request->post('auth_header', $webhookConfig->auth_header);
        $webhookConfig->timeout = (int)$this->request->post('timeout', $webhookConfig->timeout);

        return Response::asJson([
            'code' => 200,
            'msg' => 'Webhook配置保存成功',
        ]);
    }

    public function test(): Response
    {
        $notify = NotifyManager::getInstance();

        $dto = new NotifyDataDTO([
            'title' => 'Webhook测试',
            'message' => '这是一条测试通知，用于验证Webhook配置是否正确。',
            'type' => NotifyDataDTO::TYPE_SUCCESS,
            'recipient' => 'test-recipient',
        ]);

        $result = $notify->send($dto, 'webhook');

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
