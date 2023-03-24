<?php

declare(strict_types=1);

use SlimEdge\Cors;
use Psr\Container\ContainerInterface;

return [

    Cors\Config::class => DI\factory(function(ContainerInterface $container) {
        
        $cached = get_cache(Cors\Config::class, null, 'config');

        if(!is_null($cached)) {
            return $cached;
        }

        if($container->has('config.cors')) {
            $config = new Cors\Config($container->get('config.cors'));
            set_cache(Cors\Config::class, $config, 'config');
            return $config;
        }

        return new Cors\Config();
    }),

    Cors\Middleware::class => DI\factory(function(Cors\Config $config) {
        return new Cors\Middleware($config);
    }),
];