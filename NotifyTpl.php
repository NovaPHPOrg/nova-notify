<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\Instance;
use nova\framework\http\Request;
use nova\framework\http\Response;
use nova\framework\route\Route;
use nova\plugin\login\AdminPageInterface;
use nova\plugin\tpl\ViewResponse;
use function nova\framework\route;

/**
 * 通知设置后台页面（menu / 路由 / tpl）。
 */
class NotifyTpl extends Instance implements AdminPageInterface
{
    public function registerRouter(string $model, string $controller): void
    {
        $default = route($model, $controller, 'init');
        Route::getInstance()
            ->get('/notify/config', $default)
            ->get('/notify/webhook', $default)
            ->get('/notify/feishu', $default);
    }

    public function route(ViewResponse $view, Request $request): ?Response
    {
        return match ($request->getPath()) {
            '/notify/config' => $view->asTpl(ROOT_PATH . DS . 'nova/plugin/notify/tpl/config', [
                'notices' => [],
            ]),
            '/notify/webhook' => $view->asTpl(ROOT_PATH . DS . 'nova/plugin/notify/tpl/webhook'),
            '/notify/feishu' => $view->asTpl(ROOT_PATH . DS . 'nova/plugin/notify/tpl/feishu'),
            default => null,
        };
    }

    public function menu(): array
    {
        return [
            'title' => '通知设置',
            'icon' => 'notifications',
            'pjax' => true,
            'sub' => [
                [
                    'title' => '通知渠道',
                    'icon' => 'notifications',
                    'url' => '/notify/config',
                    'pjax' => true,
                ],
                [
                    'title' => 'Webhook配置',
                    'icon' => 'webhook',
                    'url' => '/notify/webhook',
                    'pjax' => true,
                ],
                [
                    'title' => '飞书配置',
                    'icon' => 'chat',
                    'url' => '/notify/feishu',
                    'pjax' => true,
                ],
            ],
        ];
    }
}
