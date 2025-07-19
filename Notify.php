<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\StaticRegister;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\notify\dto\NotifyDataDTO;

/**
 * 通知插件主类
 *
 * 负责处理各种通知渠道的配置和测试功能，包括企业微信和Webhook通知
 * 继承自StaticRegister，提供静态注册功能
 *
 * @package nova\plugin\notify
 * @author Nova Framework
 */
class Notify extends StaticRegister
{
    /**
     * 企业微信配置模板路径常量
     */
    const string WECHAT_CONFIG_TPL = ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'notify' . DS . 'tpl' . DS . 'wechat-config';

    /**
     * Webhook配置模板路径常量
     */
    const string WEBHOOK_CONFIG_TPL = ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'notify' . DS . 'tpl' . DS . 'webhook-config';

    /**
     * 通知配置模板路径常量
     */
    const string CONFIG_TPL = ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'notify' . DS . 'tpl' . DS . 'notify';

    /**
     * 注册插件信息
     *
     * 在路由执行前添加事件监听器，处理通知相关的路由请求
     * 包括企业微信配置、测试和Webhook配置、测试功能
     *
     * @return void
     */
    public static function registerInfo(): void
    {
        // 添加路由前事件监听器
        EventManager::addListener("route.before", function ($event, &$data) {
            // 检查必要的依赖类是否存在
            if (!class_exists('\nova\plugin\cookie\Session') || !class_exists('\nova\plugin\login\LoginManager')) {
                return;
            }

            // 检查用户是否已登录
            if (!\nova\plugin\login\LoginManager::getInstance()->checkLogin()) {
                return;
            }

            // 处理企业微信相关路由
            if ($data == "/notify/wechat/config") {
                Notify::handleWechatConfig();
            } elseif ($data == "/notify/wechat/test") {
                Notify::handleWechatTest();
            }
            // 处理Webhook相关路由
            elseif ($data == "/notify/webhook/config") {
                Notify::handleWebhookConfig();
            } elseif ($data == "/notify/webhook/test") {
                Notify::handleWebhookTest();
            }
        });
    }

    /**
     * 处理企业微信配置请求
     *
     * GET请求：返回当前企业微信配置信息
     * POST请求：保存企业微信配置信息
     *
     * @throws AppExitException 当需要返回响应时抛出
     * @return void
     */
    private static function handleWechatConfig(): void
    {
        // 创建企业微信配置对象
        $wechatConfig = new WechatConfig();

        // GET请求：返回配置信息
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'data' => get_object_vars($wechatConfig),
            ]));
        }
        // POST请求：保存配置信息
        else {
            $data = $_POST;
            // 更新配置参数，使用POST数据或保持默认值
            $wechatConfig->corp_id = $data['corp_id'] ?? $wechatConfig->corp_id;
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
     *
     * 发送测试通知到企业微信，验证配置是否正确
     *
     * @throws AppExitException 当需要返回响应时抛出
     * @return void
     */
    private static function handleWechatTest(): void
    {
        // 获取通知管理器实例
        $notify = NotifyManager::getInstance();
        // 创建企业微信配置对象
        $config = new WechatConfig();

        // 创建测试通知数据
        $dto = new NotifyDataDTO([
            'title' => '企业微信测试',
            'message' => '这是一条测试通知，用于验证企业微信配置是否正确。',
            'type' => NotifyDataDTO::TYPE_SUCCESS,
            'recipient' => $config->default_recipient,
        ]);

        // 发送测试通知
        $result = $notify->send($dto, 'wechat_work');

        // 根据发送结果返回相应信息
        if ($result) {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => '测试通知发送成功'
            ]));
        } else {
            throw new AppExitException(Response::asJson([
                'code' => 500,
                'msg' => '测试通知发送失败: ' . $notify->exception->getMessage()
            ]));
        }
    }

    /**
     * 处理Webhook配置请求
     *
     * GET请求：返回当前Webhook配置信息
     * POST请求：保存Webhook配置信息
     *
     * @throws AppExitException 当需要返回响应时抛出
     * @return void
     */
    private static function handleWebhookConfig(): void
    {
        // 创建Webhook配置对象
        $webhookConfig = new WebhookConfig();

        // GET请求：返回配置信息
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'data' => get_object_vars($webhookConfig),
            ]));
        }
        // POST请求：保存配置信息
        else {
            $data = $_POST;
            // 更新配置参数，使用POST数据或保持默认值
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
     *
     * 发送测试通知到Webhook，验证配置是否正确
     *
     * @throws AppExitException 当需要返回响应时抛出
     * @return void
     */
    private static function handleWebhookTest(): void
    {
        // 获取通知管理器实例
        $notify = NotifyManager::getInstance();

        // 创建测试通知数据
        $dto = new NotifyDataDTO([
            'title' => 'Webhook测试',
            'message' => '这是一条测试通知，用于验证Webhook配置是否正确。',
            'type' => NotifyDataDTO::TYPE_SUCCESS,
            'recipient' => 'test-recipient',
        ]);

        // 发送测试通知
        $result = $notify->send($dto, 'webhook');

        // 根据发送结果返回相应信息
        if ($result) {
            throw new AppExitException(Response::asJson([
                'code' => 200,
                'msg' => '测试通知发送成功'
            ]));
        } else {
            throw new AppExitException(Response::asJson([
                'code' => 500,
                'msg' => '测试通知发送失败: ' . $notify->exception->getMessage()
            ]));
        }
    }
}
