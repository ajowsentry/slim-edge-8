<?php

declare(strict_types=1);

return [
    'timezone' => 'Asia/Jakarta',

    'appName'    => 'Slim Edge 8',
    'appVersion' => 'v1.0.0',

    'enableBodyParsing' => true,
    
    // 'enableCache' => ENVIRONMENT !== 'development',
    // 'compileContainer' => ENVIRONMENT !== 'development',

    'enableCache' => false,
    'compileContainer' => false,

    'middlewares' => [
        // SlimEdge\HttpLog\Middleware::class,
    ],

    'alwaysLoadDependencies' => [
        
    ],
];
