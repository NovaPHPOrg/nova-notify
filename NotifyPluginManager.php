<?php

declare(strict_types=1);

namespace nova\plugin\notify;

use nova\framework\core\StaticRegister;
use nova\framework\route\RouteTrait;
use nova\plugin\login\AdminPage;
use nova\plugin\login\route\Permission;

/**
 * 通知插件启动器（对齐 AiPluginManager / WebdavManager）。
 */
class NotifyPluginManager extends StaticRegister
{
    use RouteTrait;

    public function __construct()
    {
        $this->controllerNamespace = 'nova\\plugin\\notify\\controller\\';
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->getOrPost('/notify/api/config', $this->map('config', 'config'));
        $this->getOrPost('/notify/api/webhook', $this->map('webhook', 'config'));
        $this->post('/notify/api/webhook/test', $this->map('webhook', 'test'));
    }

    public static function registerInfo(): void
    {
        Permission::getInstance()->registerPermissions('通知设置', 'notify_manage', [
            'GET /notify/config',
            'GET /notify/webhook',
            'ANY /notify/api*',
        ]);

        self::getInstance()->bindPrefixDispatch('/notify');
        AdminPage::bind(NotifyTpl::getInstance());
    }
}
