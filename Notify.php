<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\StaticRegister;

use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\notify\dto\NotifyDataDTO;

class Notify extends StaticRegister
{
    // 添加模板常量定义
    const string WECHAT_CONFIG_TPL = ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'notify' . DS . 'tpl' . DS . 'wechat-config';
    const string WEBHOOK_CONFIG_TPL = ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'notify' . DS . 'tpl' . DS . 'webhook-config';

    public static function registerInfo(): void
    {
        EventManager::addListener("route.before", function ($event, &$data) {
            if (!class_exists('\nova\plugin\cookie\Session') || !class_exists('\nova\plugin\login\LoginManager')) {
                return;
            }
            \nova\plugin\cookie\Session::getInstance()->start();
            if (!\nova\plugin\login\LoginManager::getInstance()->checkLogin()) {
                return;
            }
            // 企业微信配置
            if ($data == "/notify/wechat/config") {
                Notify::handleWechatConfig();
            } elseif ($data == "/notify/wechat/test") {
                Notify::handleWechatTest();
            }
            // Webhook配置
            elseif ($data == "/notify/webhook/config") {
                Notify::handleWebhookConfig();
            } elseif ($data == "/notify/webhook/test") {
                Notify::handleWebhookTest();
            }
        });
    }

    /**
     * 处理企业微信配置请求
     * @throws AppExitException
     */
    private static function handleWechatConfig(): void
    {

        $wechatConfig =  new WechatConfig();

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'data' => get_object_vars($wechatConfig),
            ]));
        } else {
            $data = $_POST;
            $wechatConfig->corp_id = $data['corp_id'] ??  $wechatConfig->corp_id;
            $wechatConfig->corp_secret = $data['corp_secret'] ?? $wechatConfig->corp_secret;
            $wechatConfig->agent_id = $data['agent_id'] ?? $wechatConfig->agent_id;
            $wechatConfig->default_recipient = $data['default_recipient'] ?? $wechatConfig->default_recipient;
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => '企业微信配置保存成功'
            ]));
        }

    }

    /**
     * 处理测试企业微信请求
     * @throws AppExitException
     */
    private static function handleWechatTest(): void
    {

        $notify = NotifyManager::getInstance();
        $config = new WechatConfig();
        $dto = new NotifyDataDTO([
            'title' => '企业微信测试',
            'message' => '这是一条测试通知，用于验证企业微信配置是否正确。',
            'type' => NotifyDataDTO::TYPE_SUCCESS,
            'recipient' => $config->default_recipient,
        ]);

        $result = $notify->send($dto, 'wechat_work');

        if ($result) {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => '测试通知发送成功'
            ]));
        } else {
            throw new AppExitException(Response::asJson([
                'code' => 500,
                'msg' => '测试通知发送失败: '.$notify->exception->getMessage()
            ]));
        }
    }

    /**
     * 处理Webhook配置请求
     * @throws AppExitException
     */
    private static function handleWebhookConfig(): void
    {

        $webhookConfig = new WebhookConfig();

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'data' => get_object_vars($webhookConfig),
            ]));
        } else {
            $data = $_POST;
            $webhookConfig->url = $data['url'] ?? $webhookConfig->url;
            $webhookConfig->auth_header = $data['auth_header'] ?? $webhookConfig->auth_header;
            $webhookConfig->timeout = (int)($data['timeout'] ?? $webhookConfig->timeout);

            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => 'Webhook配置保存成功'
            ]));
        }

    }

    /**
     * 处理测试Webhook请求
     * @throws AppExitException
     */
    private static function handleWebhookTest(): void
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
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => '测试通知发送成功'
            ]));
        } else {
            throw new AppExitException(Response::asJson([
                'code' => 500,
                'msg' => '测试通知发送失败: '.$notify->exception->getMessage()
            ]));
        }
    }
}
