<?php

declare(strict_types=1);

return [
    'require' => [
        'mail',
        'login',
        'tpl',
    ],
    'config' => [
        'framework_start' => [
            'nova\\plugin\\notify\\NotifyPluginManager',
        ],
    ],
];
