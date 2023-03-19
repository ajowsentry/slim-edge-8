<?php

declare(strict_types=1);

use SlimEdge\Support\Paths;
use Slim\Factory\AppFactory;
use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\ServerRequestCreatorFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

return [

    'registry' => function() {
        return new ArrayObject();
    },

    StreamFactoryInterface::class => DI\factory(function() {
        foreach(Psr17FactoryProvider::getFactories() as $factory) {
            if($factory::isStreamFactoryAvailable()) {
                return $factory::getStreamFactory();
            }
        }
    }),

    ResponseFactoryInterface::class => DI\factory(function() {
        return AppFactory::determineResponseFactory();
    }),

    ServerRequestCreatorInterface::class => DI\factory(function() {
        return ServerRequestCreatorFactory::determineServerRequestCreator();
    }),

    CacheInterface::class => DI\factory(function() {
        $config = new Phpfastcache\Config\ConfigurationOption(['path' => Paths::Cache]);
        return new Phpfastcache\Helper\Psr16Adapter('Files', $config);
    }),
];