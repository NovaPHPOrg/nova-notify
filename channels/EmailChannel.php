<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use nova\plugin\mail\Mail;
use nova\plugin\mail\MailException;
use nova\plugin\mail\MailTpl;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\NotifyChannelInterface;

class EmailChannel implements NotifyChannelInterface
{
    /**
     */
    public function send(NotifyDataDTO $data): void
    {
        try {
            // 确定收件人
            $recipient = $data->recipient;
            if (empty($recipient)) {
                throw new \RuntimeException('收件人未设置');
            }

            // 使用MailTpl构建邮件内容
            $mailTpl = new MailTpl();
            $logo = $this->getSystemLogo();
            $content = $this->buildMessageContent($data);

            $htmlBody = $mailTpl->notice($logo, $content);

            // 使用Mail::send发送邮件
            Mail::send($recipient, '', $data->title, $htmlBody);

        } catch (MailException|\Exception $e) {
            throw new \RuntimeException('邮件发送失败：' . $e->getMessage());
        }
    }

    /**
     * 获取系统Logo
     */
    private function getSystemLogo(): string
    {
        // 生成系统邮件图标的SVG
        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>';

        return "data:image/svg+xml;base64," . base64_encode($iconSvg);
    }

    /**
     * 构建邮件内容
     */
    private function buildMessageContent(NotifyDataDTO $data): string
    {
        // 获取状态信息
        $statusInfo = $this->getStatusInfo($data->type);

        // 构建主要内容
        $content = '<div style="margin-bottom: 20px;">';

        // 添加状态标识
        $content .= '<div style="display: flex; align-items: center; margin-bottom: 15px; padding: 10px; background-color: ' . $statusInfo['bgColor'] . '; color: white; border-radius: 6px;">';
        $content .= '<span style="font-size: 18px; margin-right: 8px;">' . $statusInfo['emoji'] . '</span>';
        $content .= '<strong>' . $statusInfo['text'] . '</strong>';
        $content .= '</div>';

        // 添加消息内容
        $content .= '<div style="line-height: 1.6; color: #333;">';
        $content .= $data->message;
        $content .= '</div>';

        // 添加动作按钮
        if (!empty($data->actionLeftUrl) || !empty($data->actionRightUrl)) {
            $content .= '<div style="text-align: center; margin: 30px 0;">';

            if (!empty($data->actionLeftUrl) && !empty($data->actionLeftText)) {
                $content .= '<a href="' . htmlspecialchars($data->actionLeftUrl) . '" style="display: inline-block; margin: 0 10px; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">';
                $content .= htmlspecialchars($data->actionLeftText);
                $content .= '</a>';
            }

            if (!empty($data->actionRightUrl) && !empty($data->actionRightText)) {
                $content .= '<a href="' . htmlspecialchars($data->actionRightUrl) . '" style="display: inline-block; margin: 0 10px; padding: 12px 24px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">';
                $content .= htmlspecialchars($data->actionRightText);
                $content .= '</a>';
            }

            $content .= '</div>';
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * 获取状态相关信息
     */
    private function getStatusInfo(string $type): array
    {
        return match ($type) {
            'success' => [
                'emoji' => '✅',
                'text' => '成功',
                'bgColor' => '#4CAF50'
            ],
            'warning' => [
                'emoji' => '⚠️',
                'text' => '警告',
                'bgColor' => '#FF9800'
            ],
            'error' => [
                'emoji' => '❌',
                'text' => '错误',
                'bgColor' => '#F44336'
            ],
            default => [
                'emoji' => 'ℹ️',
                'text' => '通知',
                'bgColor' => '#2196F3'
            ]
        };
    }
}
