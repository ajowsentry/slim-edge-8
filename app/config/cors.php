<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'allowOrigins' => 'https://example.com',
    'allowHeaders' => [
        'Accept',
        'Authorization',
        'Content-Type',
    ],
    'allowCredentials' => [
        'Cookie', 'Authorization',
    ],
    'exposeHeaders' => [],
    'maxAge' => null,
    'routes' => []
];
