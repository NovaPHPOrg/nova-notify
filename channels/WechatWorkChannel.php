<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use nova\framework\core\Logger;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\markdown\ParseMarkdown;
use nova\plugin\notify\markdown\ParseMarkdownTxt;
use nova\plugin\notify\NotifyChannelInterface;
use nova\plugin\notify\db\Model\NotifyChannelModel;
use RuntimeException;

class WechatWorkChannel implements NotifyChannelInterface
{
    /**
     * 发送企业微信通知
     *
     * @param NotifyChannelModel $channel 通道配置
     * @param NotifyDataDTO $data 通知数据
     * @throws \Exception 发送失败时抛出异常
     */
    public function send(NotifyChannelModel $channel, NotifyDataDTO $data): void
    {
        // 获取相关配置
        $config = $channel->config;
        $corpId = $config['corp_id'] ?? '';
        $corpSecret = $config['corp_secret'] ?? '';
        $agentId = $config['agent_id'] ?? '';
        $data->recipient = $data->recipient ?? $channel->config['default_recipient'];
        // 验证必要配置
        if (empty($corpId) || empty($corpSecret) || empty($agentId)) {
            throw new \Exception('企业微信配置不完整: 缺少企业ID、应用Secret或应用ID');
        }
        

        
        // 获取访问令牌
        $accessToken = $this->getAccessToken($corpId, $corpSecret);
        
        // 构建消息
        $message = $this->buildMarkdownMessage($data, $agentId);
        
        // 发送消息
        try {
            $result = $this->sendWorkWechatMessage($accessToken, $message);
            
            if ($result['errcode'] !== 0) {
                throw new RuntimeException('企业微信通知发送失败: ' . ($result['errmsg'] ?? '未知错误'));
            }
            
            Logger::info('企业微信通知发送成功', [
                'to_user' => $data->recipient ,
                'title' => $data->title ?? '系统通知'
            ]);
        } catch (RuntimeException $e) {
            Logger::error('企业微信通知异常', [
                'error' => $e->getMessage(),
                'to_user' => $data->recipient
            ]);
            throw $e;
        }
    }
    
    /**
     * 获取企业微信访问令牌
     * 
     * @param string $corpId 企业ID
     * @param string $corpSecret 应用Secret
     * @return string 访问令牌
     * @throws RuntimeException 获取失败时抛出异常
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
            throw new RuntimeException('获取企业微信访问令牌失败: 网络错误');
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new RuntimeException('获取企业微信访问令牌失败: ' . ($result['errmsg'] ?? '未知错误'));
        }
        
        return $result['access_token'];
    }
    
    /**
     * 构建企业微信Markdown消息
     * 
     * @param NotifyDataDTO $data 通知数据
     * @param string $agentId 应用ID
     * @return array 消息结构
     */
    private function buildMarkdownMessage(NotifyDataDTO $data, string $agentId): array
    {
        // 基本信息
        $title = $data->title ?? '系统通知';
        $message = $data->message ?? '';

        $toUser = $data->recipient;
        
        
        
        // 获取状态信息
        $statusEmoji = match ($data->type) {
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };
        
        $statusColor = match ($data->type) {
            'success' => 'info',  // 绿色
            'warning' => 'warning',  // 橙红色
            'error' => 'warning',  // 橙红色
            default => 'info'  // 绿色
        };
        

        
        // 构建Markdown内容 - 使用官方支持的语法
        $markdownContent = '';
        
        // 顶部分隔线和标题区域
        $markdownContent .= "# {$statusEmoji} {$title}\n\n";

        
        // 消息正文 - 使用引用块
        if (!empty($message)) {
            
            // 处理多行消息，确保每行都有引用符号 ">"
            $messageLines = explode("\n", $message);
            foreach ($messageLines as $line) {
                $markdownContent .= "{$line}\n";
            }
            $markdownContent .= "\n";
        }
        
        // 添加动作按钮 (如果有)
        if (!empty($data->actionLeftUrl) || !empty($data->actionRightUrl)) {

            $markdownContent.="---\n";
            if (!empty($data->actionLeftUrl) && !empty($data->actionLeftText)) {
                $markdownContent .= "[{$data->actionLeftText}]({$data->actionLeftUrl}) ";
            }
            
            if (!empty($data->actionRightUrl) && !empty($data->actionRightText)) {
                $markdownContent .= "  ┃  [{$data->actionRightText}]({$data->actionRightUrl})";
            }
            
            $markdownContent .= "\n\n";
        }
        // 返回完整的消息结构
        $message = [
            'msgtype' => 'text',
            'agentid' => $agentId,
            'text' => [
                'content' => (new ParseMarkdownTxt())->parse($markdownContent)
            ],
            'enable_duplicate_check' => 0,
            'duplicate_check_interval' => 1800
        ];
        
        // 添加接收者信息
        if (!empty($toUser)) {
            $message['touser'] = $toUser;
        }

        
        return $message;
    }
    
    /**
     * 发送企业微信消息
     * 
     * @param string $accessToken 访问令牌
     * @param array $message 消息内容
     * @return array 发送结果
     * @throws \Exception 发送失败时抛出异常
     */
    private function sendWorkWechatMessage(string $accessToken, array $message): array
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
            throw new \Exception('发送企业微信消息失败: 网络错误');
        }
        
        return json_decode($response, true) ?: ['errcode' => -1, 'errmsg' => '响应解析失败'];
    }

} 