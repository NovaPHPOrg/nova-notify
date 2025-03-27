<?php

declare(strict_types=1);

namespace nova\plugin\notify\channels;

use nova\framework\core\Context;
use nova\plugin\notify\db\Model\NotifyChannelModel;
use nova\plugin\notify\dto\NotifyDataDTO;
use nova\plugin\notify\markdown\ParseMarkdown;
use nova\plugin\notify\NotifyChannelInterface;
use nova\plugin\notify\phpmail\PHPMailer;
use function nova\framework\config;

class EmailChannel implements NotifyChannelInterface
{
    public function send(NotifyChannelModel $channel, NotifyDataDTO $data): void
    {
            $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
            try {
                $data->recipient = $data->recipient ?? $channel->config['default_recipient'];
                //服务器配置
                $mail->CharSet = "UTF-8";                     //设定邮件编码
                $mail->SMTPDebug = Context::instance()->isDebug();                        // 调试模式输出
                $mail->isSMTP();                             // 使用SMTP
                $mail->Host = $channel->config['host'];                // SMTP服务器
                $mail->SMTPAuth = true;                      // 允许 SMTP 认证
                $mail->Username = $channel->config['user'];                // SMTP 用户名  即邮箱的用户名
                $mail->Password = $channel->config['pass'];             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
                $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
                $mail->Port = $channel->config['port'];                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
                $mail->Timeout = 10;
                $mail->setFrom($channel->config['user'],$channel->config['name']);  //发件人
                $mail->addAddress($data->recipient);  // 收件人
                //$mail->addAddress('ellen@example.com');  // 可添加多个收件人
                $mail->addReplyTo($channel->config['user']); //回复的时候回复给哪个邮箱 建议和发件人一致
                //$mail->addCC('cc@example.com');                    //抄送
                //$mail->addBCC('bcc@example.com');                    //密送

                //发送附件
                // $mail->addAttachment('../xy.zip');         // 添加附件
                // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                //Content
                $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
                $mail->Subject = $data->title;
                
                // 使用格式化的HTML模板
                $mail->Body = $this->buildHtmlMessage($data, $channel->config['name'] ?? '系统通知');
                $mail->AltBody = strip_tags($data->message) ?: '客户端不支持html显示，请更换邮件客户端。';
                $mail->send();
        } catch (\Exception $e) {
            throw new \RuntimeException('邮件发送失败：' . $e->getMessage());
        }
    }
    
    /**
     * 构建HTML格式的邮件内容
     * 
     * @param NotifyDataDTO $data 通知数据
     * @param string $siteName 站点名称
     * @return string 格式化后的HTML内容
     */
    private function buildHtmlMessage(NotifyDataDTO $data, string $siteName): string
    {
        // 获取基本信息
        $title = $data->title ?? '系统通知';
        $message = $data->message ?? '';

        $message = (new ParseMarkdown())->parse($message);

        $date = date('Y-m-d H:i:s');
        
        // 获取状态信息和对应的颜色
        list($bgColor, $color, $statusEmoji, $statusText) = $this->getStatusInfo($data->type ?? 'default');
        
         // 构建HTML内容
        $html = <<<HTML
<body style="color: #666; font-size: 14px; font-family: 'Open Sans',Helvetica,Arial,sans-serif;">
<div class="box-content" style=" margin: 20px auto;  max-width: 600px;">
    <div class="header-tip"  style="font-size: 12px;color: #aaa;text-align: right;padding-right: 25px;padding-bottom: 10px;"> Powered by Ankio </div>
    <div class="info-top"
         style="padding: 15px 25px;border-top-left-radius: 10px;border-top-right-radius: 10px;background: {$bgColor};color: #fff;overflow: hidden;line-height: 32px;">
      
        <div style="color:{$color}"><strong>{$statusEmoji} {$title}</strong></div>
        <div style="font-size: 14px; margin-top: 5px;"><strong>{$statusText}</strong></div>
    </div>
    <div class="info-wrap" style="border:1px solid #ddd;overflow: hidden;padding: 15px 15px 20px;">
        <div class="tips" style="padding:15px;"><p style="margin: 10px 0;">{$message}</p></div>
HTML;

        // 添加动作按钮 (如果有)
        if (!empty($data->actionLeftUrl) || !empty($data->actionRightUrl)) {
            $html .= '<div class="actions" style="text-align: center; margin: 20px 0;">';
            
            if (!empty($data->actionLeftUrl) && !empty($data->actionLeftText)) {
                $html .= '<a href="' . $data->actionLeftUrl . '" style="display: inline-block; margin: 0 10px; padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">' . $data->actionLeftText . '</a>';
            }
            
            if (!empty($data->actionRightUrl) && !empty($data->actionRightText)) {
                $html .= '<a href="' . $data->actionRightUrl . '" style="display: inline-block; margin: 0 10px; padding: 8px 16px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;">' . $data->actionRightText . '</a>';
            }
            
            $html .= '</div>';
        }

        // 添加时间和页脚
        $html .= <<<HTML
        <div class="time" style="text-align: right; color: #999; padding: 0 15px 15px;">{$date}</div>
        </div>
    <div style="background-color: #F5F5F5;direction: ltr;padding: 16px;margin-bottom: 6px;border-bottom-left-radius: 10px;border-bottom-right-radius: 10px;">
        <table>
            <tbody>
            <tr>
                <td style="direction: ltr;"><span
                            style="font-family: Roboto-Regular,Helvetica,Arial,sans-serif; font-size: 13px;  line-height: 1.6; color: rgba(0,0,0,0.54);">本邮件发自《{$siteName}》，由系统自动发送，请勿回复。</span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
HTML;

        return $html;
    }
    
    /**
     * 获取状态相关信息
     * 
     * @param string $type 通知类型
     * @return array [背景颜色, 文字颜色, 状态图标, 状态文本]
     */
    private function getStatusInfo(string $type): array
    {
        // 设置状态图标
        $statusEmoji = match ($type) {
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };
        
        // 设置状态文本
        $statusText = match ($type) {
            'success' => '成功',
            'warning' => '警告',
            'error' => '错误',
            default => '通知'
        };
        
        // 设置背景颜色
        $bgColor = match ($type) {
            'success' => '#4CAF50',  // 绿色
            'warning' => '#FF9800',  // 橙色
            'error' => '#F44336',    // 红色
            default => '#2196F3'     // 蓝色
        };
        
        // 设置文本颜色（白色）
        $color = '#FFFFFF';
        
        return [$bgColor, $color, $statusEmoji, $statusText];
    }
}
