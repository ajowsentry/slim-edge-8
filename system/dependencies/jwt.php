<?php

declare(strict_types=1);

use SlimEdge\JWT;
use Psr\Container\ContainerInterface;

return [

    JWT\Config::class => DI\factory(function(ContainerInterface $container) {
        $config = get_cache('jwt', null, 'config');
        if(is_null($config)) {
            if($container->has('config.jwt')) {
                $config = $container->get('config.jwt');
                $result = new JWT\Config($config);
                set_cache('jwt', $result, 'config');
                return $result;
            }
            return new JWT\Config([]);
        }
        return $config;
    }),

    JWT\Decoder::class => DI\factory(function(JWT\Config $config) {
        return new JWT\Decoder($config);
    }),

    JWT\Encoder::class => DI\factory(function(JWT\Config $config) {
        return new JWT\Encoder($config);
    }),

    JWT\FetchCredential::class => DI\factory(function(JWT\Config $config) {
        return new JWT\FetchCredential($config);
    }),

    JWT\RequireCredential::class => DI\factory(function(JWT\Config $config) {
        return new JWT\RequireCredential($config);
    }),
];