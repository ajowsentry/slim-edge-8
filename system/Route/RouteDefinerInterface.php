<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

interface RouteDefinerInterface
{
    /**
     * @param RouteCollectorProxyInterface|RouteGroupInterface $routeCollector
     * @return void
     */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface $routeCollector): void;
}