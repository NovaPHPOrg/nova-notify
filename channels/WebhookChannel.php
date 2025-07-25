<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use Exception;
use nova\framework\core\Logger;
use nova\plugin\http\HttpClient;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\markdown\ParseMarkdown;
use nova\plugin\notify\NotifyChannelInterface;
use nova\plugin\notify\WebhookConfig;

class WebhookChannel implements NotifyChannelInterface
{
    /**
     * @throws Exception
     */
    public function send(NotifyDataDTO $data): void
    {
        $webhookConfig = new WebhookConfig();

        if (empty($webhookConfig->url)) {
            throw new \RuntimeException('Webhook URL未设置');
        }

        try {
            // 构建请求数据
            $requestData = [
                'title' => $data->title,
                'message' => (new ParseMarkdown())->parse($data->message),
                'type' => $data->type,
                'recipient' => $data->recipient,
                'actionLeftUrl' => $data->actionLeftUrl,
                'actionLeftText' => $data->actionLeftText,
                'actionRightUrl' => $data->actionRightUrl,
                'actionRightText' => $data->actionRightText,
                'timestamp' => time(),
                'channel' => 'webhook'
            ];

            $headers = [];
            if (!empty($webhookConfig->auth_header)) {
                $headers[] = $webhookConfig->auth_header;
            }

            // 发送请求
            $response = $this->sendRequest($webhookConfig->url, $requestData, $headers, $webhookConfig->timeout);

            if ($response['http_code'] >= 400) {
                throw new \RuntimeException('Webhook请求失败: HTTP ' . $response['http_code'] . ' - ' . $response['body']);
            }

            Logger::info('Webhook通知发送成功', [
                'url' => $webhookConfig->url,
                'title' => $data->title,
                'response_code' => $response['http_code']
            ]);
        } catch (Exception $e) {
            Logger::error('Webhook通知异常', [
                'error' => $e->getMessage(),
                'url' => $webhookConfig->url
            ]);
            throw $e;
        }
    }

    /**
     * 发送HTTP请求
     */
    private function sendRequest(string $url, array $data, array $headers, int $timeout): array
    {
        $http  = HttpClient::init();
        $response = $http->setHeaders($headers)->post($data, "form")->send($url);

        if ($response->getHttpCode() >= 400) {
            throw new \RuntimeException('Webhook请求失败');
        }

        return [
            'http_code' => $response->getHttpCode(),
            'body' => $response->getBody()
        ];
    }
}
