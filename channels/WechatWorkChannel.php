<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use nova\framework\core\Logger;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\NotifyChannelInterface;
use nova\plugin\notify\WechatConfig;

class WechatWorkChannel implements NotifyChannelInterface
{
    public function send(NotifyDataDTO $data): void
    {
        $wechatConfig = new WechatConfig();

        if (empty($wechatConfig->corp_id)) {
            throw new \RuntimeException('企业微信配置未设置');
        }

        $corpId = $wechatConfig->corp_id;
        $corpSecret = $wechatConfig->corp_secret;
        $agentId = $wechatConfig->agent_id;

        if (empty($corpSecret) || empty($agentId)) {
            throw new \RuntimeException('企业微信配置不完整: 缺少企业ID、应用Secret或应用ID');
        }

        $recipient = $data->recipient ?? $wechatConfig->default_recipient;
        if (!$recipient) {
            throw new \RuntimeException('收件人未设置');
        }

        try {
            // 获取访问令牌
            $accessToken = $this->getAccessToken($corpId, $corpSecret);

            // 构建消息
            $message = $this->buildMessage($data, $agentId, $recipient);

            // 发送消息
            $result = $this->sendMessage($accessToken, $message);

            if ($result['errcode'] !== 0) {
                throw new \RuntimeException('企业微信通知发送失败: ' . ($result['errmsg'] ?? '未知错误'));
            }

            Logger::info('企业微信通知发送成功', [
                'to_user' => $recipient,
                'title' => $data->title
            ]);
        } catch (\Exception $e) {
            Logger::error('企业微信通知异常', [
                'error' => $e->getMessage(),
                'to_user' => $recipient
            ]);
            throw $e;
        }
    }

    /**
     * 获取企业微信访问令牌
     */
    private function getAccessToken(string $corpId, string $corpSecret): string
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpId}&corpsecret={$corpSecret}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('获取企业微信访问令牌失败: 网络错误');
        }

        $result = json_decode($response, true);

        if (!isset($result['access_token'])) {
            throw new \RuntimeException('获取企业微信访问令牌失败: ' . ($result['errmsg'] ?? '未知错误'));
        }

        return $result['access_token'];
    }

    /**
     * 构建企业微信消息
     */
    private function buildMessage(NotifyDataDTO $data, string $agentId, string $recipient): array
    {
        $title = $data->title;
        $message = $data->message;
        $type = $data->type;

        $statusEmoji = match ($type) {
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };

        // 构建文本内容
        $content = "{$statusEmoji} {$title}\n\n{$message}";

        // 添加动作按钮
        if (!empty($data->actionLeftUrl) || !empty($data->actionRightUrl)) {
            $content .= "\n\n";
            if (!empty($data->actionLeftUrl) && !empty($data->actionLeftText)) {
                $content .= "{$data->actionLeftText}: {$data->actionLeftUrl}";
            }
            if (!empty($data->actionRightUrl) && !empty($data->actionRightText)) {
                $content .= "\n{$data->actionRightText}: {$data->actionRightUrl}";
            }
        }

        return [
            'msgtype' => 'text',
            'agentid' => $agentId,
            'text' => [
                'content' => $content
            ],
            'touser' => $recipient,
            'enable_duplicate_check' => 0,
            'duplicate_check_interval' => 1800
        ];
    }

    /**
     * 发送企业微信消息
     */
    private function sendMessage(string $accessToken, array $message): array
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('发送企业微信消息失败: 网络错误');
        }

        $result = json_decode($response, true);
        if (!$result) {
            throw new \RuntimeException('发送企业微信消息失败: 响应解析错误');
        }

        return $result;
    }
}
