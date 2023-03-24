<?php

declare(strict_types=1);

use SlimEdge\JWT;
use Psr\Container\ContainerInterface;

return [

    JWT\Config::class => DI\factory(function(ContainerInterface $container) {

        $cached = get_cache(JWT\Config::class, null, 'config');

        if(!is_null($cached)) {
            return $cached;
        }

        if($container->has('config.jwt')) {
            $config = new JWT\Config($container->get('config.jwt'));
            set_cache(JWT\Config::class, $config, 'config');
            return $config;
        }

        return new JWT\Config();
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