<?php

declare(strict_types=1);

namespace nova\plugin\notify\dto;

/**
 * 标准化通知数据传输对象
 * 
 * 该类用于规范化所有通知渠道的输入数据格式，
 * 各个渠道实现可以从该对象中提取所需的数据。
 */
class NotifyDataDTO
{
    // 业务ID，可以是订单号或其他唯一标识
    public ?string $title = null;            // 通知标题
    public ?string $message = null;          // 通知正文内容
    public ?string $type = 'default';        // 通知类型：default, success, warning, error
    public ?array $payloadData = [];         // 自定义附加数据，可由渠道自行解析


    // 动作相关
    public ?string $actionLeftUrl = null;        // 操作链接
    public ?string $actionLeftText = null;       // 操作按钮文本

    // 动作相关
    public ?string $actionRightUrl = null;        // 操作链接
    public ?string $actionRightText = null;       // 操作按钮文本
    // 接收者相关
    public ?string $recipient = null;   // 收件人

    // 时间相关
    public ?string $createdAt = null;        // 创建时间
    public ?string $expiredAt = null;        // 过期时间


   
    /**
     * 创建一个新的通知数据对象
     */
    public function __construct(array $data = [])
    {
        // 设置当前时间
        $this->createdAt = date('Y-m-d H:i:s');
        
        // 从传入的数组填充属性
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                // 未定义的属性放入 payloadData
                $this->payloadData[$key] = $value;
            }
        }
    }


} 