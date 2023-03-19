<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;

interface RouteDefinerInterface
{
    /**
     * @param RouteCollectorProxyInterface|RouteGroupInterface $routeCollector
     * @return void
     */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface $routeCollector): void;
}