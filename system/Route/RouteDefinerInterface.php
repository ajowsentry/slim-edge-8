<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteInterface;

interface RouteDefinerInterface
{
    /**
     * @param RouteCollectorProxyInterface|RouteGroupInterface|RouteInterface $routeCollector
     * @return void
     */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface|RouteInterface $routeCollector): void;
}