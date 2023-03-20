<?php

declare(strict_types=1);

use SlimEdge\HttpLog;
use Psr\Container\ContainerInterface;

return [

    HttpLog\Config::class => DI\factory(function(ContainerInterface $container) {
        $cached = get_cache(HttpLog\Config::class, null, 'config');

        if(!is_null($cached)) {
            return $cached;
        }

        if($container->has('config.http_logger')) {
            $config = new HttpLog\Config($container->get('config.http_logger'));
            set_cache(HttpLog\Config::class, $config, 'config');
            return $config;
        }

        return new HttpLog\Config([]);
    }),

    HttpLog\Middleware::class => DI\factory(function(HttpLog\Config $config) {

        return new HttpLog\Middleware($config);
    }),
];