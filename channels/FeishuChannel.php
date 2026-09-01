<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use Exception;
use nova\framework\core\Logger;
use nova\plugin\http\HttpClient;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\FeishuConfig;
use nova\plugin\notify\NotifyChannelInterface;
use RuntimeException;

/**
 * 飞书自定义机器人 Webhook 渠道
 *
 * 发送 interactive 卡片：标题走 header，内容走 lark_md，动作按钮映射为跳转按钮。
 * 配置了 secret 时自动附加 timestamp/sign。
 */
class FeishuChannel implements NotifyChannelInterface
{
    /**
     * @throws Exception
     */
    public function send(NotifyDataDTO $data): void
    {
        $config = new FeishuConfig();

        if (empty($config->url)) {
            throw new RuntimeException('飞书 Webhook URL未设置');
        }

        try {
            $payload = $this->buildPayload($data, $config);
            $response = $this->sendRequest($config->url, $payload, $config->timeout);
            $this->assertSuccess($response);

            Logger::info('飞书通知发送成功', [
                'url' => $config->url,
                'title' => $data->title,
                'response_code' => $response['http_code'],
            ]);
        } catch (Exception $e) {
            Logger::error('飞书通知异常', [
                'error' => $e->getMessage(),
                'url' => $config->url,
            ]);
            throw $e;
        }
    }

    private function buildPayload(NotifyDataDTO $data, FeishuConfig $config): array
    {
        $payload = [
            'msg_type' => 'interactive',
            'card' => $this->buildCard($data),
        ];

        if ($config->secret !== '') {
            $timestamp = (string)time();
            $payload['timestamp'] = $timestamp;
            $payload['sign'] = $this->sign($timestamp, $config->secret);
        }

        return $payload;
    }

    private function buildCard(NotifyDataDTO $data): array
    {
        $elements = [
            [
                'tag' => 'div',
                'text' => [
                    'tag' => 'lark_md',
                    'content' => $data->message !== '' ? $data->message : '（无内容）',
                ],
            ],
        ];

        $actions = $this->buildActions($data);
        if ($actions !== []) {
            $elements[] = [
                'tag' => 'action',
                'actions' => $actions,
            ];
        }

        return [
            'header' => [
                'title' => [
                    'tag' => 'plain_text',
                    'content' => $data->title !== '' ? $data->title : '系统通知',
                ],
                'template' => $this->templateByType($data->type),
            ],
            'elements' => $elements,
        ];
    }

    private function buildActions(NotifyDataDTO $data): array
    {
        $actions = [];

        if (!empty($data->actionLeftUrl) && !empty($data->actionLeftText)) {
            $actions[] = [
                'tag' => 'button',
                'text' => [
                    'tag' => 'plain_text',
                    'content' => $data->actionLeftText,
                ],
                'url' => $data->actionLeftUrl,
                'type' => 'primary',
            ];
        }

        if (!empty($data->actionRightUrl) && !empty($data->actionRightText)) {
            $actions[] = [
                'tag' => 'button',
                'text' => [
                    'tag' => 'plain_text',
                    'content' => $data->actionRightText,
                ],
                'url' => $data->actionRightUrl,
                'type' => 'default',
            ];
        }

        return $actions;
    }

    private function templateByType(string $type): string
    {
        return match ($type) {
            NotifyDataDTO::TYPE_SUCCESS => 'green',
            NotifyDataDTO::TYPE_WARNING => 'orange',
            NotifyDataDTO::TYPE_ERROR => 'red',
            default => 'blue',
        };
    }

    /**
     * 飞书签名：以 timestamp + "\n" + secret 为 key，对空串做 HmacSHA256，再 Base64。
     */
    private function sign(string $timestamp, string $secret): string
    {
        $stringToSign = $timestamp . "\n" . $secret;

        return base64_encode(hash_hmac('sha256', '', $stringToSign, true));
    }

    private function sendRequest(string $url, array $payload, int $timeout): array
    {
        $response = HttpClient::init()
            ->timeout($timeout)
            ->post($payload, 'json')
            ->send($url);

        return [
            'http_code' => $response->getHttpCode(),
            'body' => $response->getBody(),
        ];
    }

    private function assertSuccess(array $response): void
    {
        if ($response['http_code'] >= 400) {
            throw new RuntimeException(
                '飞书请求失败: HTTP ' . $response['http_code'] . ' - ' . $response['body']
            );
        }

        $body = json_decode($response['body'], true);
        if (!is_array($body)) {
            return;
        }

        // 新旧接口字段不统一：StatusCode / code
        $code = $body['StatusCode'] ?? $body['code'] ?? 0;
        if ((int)$code !== 0) {
            $msg = $body['StatusMessage'] ?? $body['msg'] ?? 'unknown';
            throw new RuntimeException('飞书请求失败: ' . $msg);
        }
    }
}
