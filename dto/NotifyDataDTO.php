<?php

declare(strict_types=1);

namespace nova\plugin\notify\dto;

/**
 * 精简版通知数据传输对象
 */
class NotifyDataDTO
{
    public string $title;
    public string $message;
    public string $type = 'default';
    public ?string $recipient = null;
    public ?string $actionLeftUrl = null;
    public ?string $actionLeftText = null;
    public ?string $actionRightUrl = null;
    public ?string $actionRightText = null;

    public function __construct(array $data = [])
    {
        $this->title = $data['title'] ?? '系统通知';
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'default';
        $this->recipient = $data['recipient'] ?? null;
        $this->actionLeftUrl = $data['actionLeftUrl'] ?? null;
        $this->actionLeftText = $data['actionLeftText'] ?? null;
        $this->actionRightUrl = $data['actionRightUrl'] ?? null;
        $this->actionRightText = $data['actionRightText'] ?? null;
    }

    /**
     * 从数组创建DTO
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    const string TYPE_DEFAULT = 'default';
    const string TYPE_INFO = 'default';
    const string TYPE_WARNING = 'warning';
    const string TYPE_ERROR = 'error';
    const string TYPE_SUCCESS = 'success';

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'recipient' => $this->recipient,
            'actionLeftUrl' => $this->actionLeftUrl,
            'actionLeftText' => $this->actionLeftText,
            'actionRightUrl' => $this->actionRightUrl,
            'actionRightText' => $this->actionRightText,
        ];
    }
}
