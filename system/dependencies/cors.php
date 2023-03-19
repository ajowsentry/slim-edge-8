<?php

declare(strict_types=1);

use SlimEdge\Cors;
use Psr\Container\ContainerInterface;

return [

    Cors\Config::class => DI\factory(function(ContainerInterface $container) {
        $config = get_cache(Cors\Config::class, null, 'config');
        if(is_null($config)) {
            if($container->has('config.cors')) {
                $config = $container->get('config.cors');
                $result = new Cors\Config($config);
                set_cache(Cors\Config::class, $result, 'config');
                return $result;
            }
            return new Cors\Config([]);
        }
        return $config;
    }),

    Cors\Middleware::class => DI\factory(function(Cors\Config $config) {
        return new Cors\Middleware($config);
    }),
];