<?php
// 中间件配置
return [
    // 别名或分组
    'alias'    => [
        'CheckToken' => app\middleware\CheckToken::class, // 校验Token
        'CheckApiAuth' => app\middleware\CheckApiAuth::class, // 校验接口权限
    ],
    // 优先级设置，此数组中的中间件会按照数组中的顺序优先执行
    'priority' => [],
];
