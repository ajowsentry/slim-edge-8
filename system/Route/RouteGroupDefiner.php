<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class RouteGroupDefiner implements RouteDefinerInterface
{
    /**
     * @var string $pattern
     */
    public readonly string $pattern;

    /**
     * @var MiddlewareDefiner[] $middlewares
     */
    public readonly array $middlewares;

    /**
     * @var RouteDefiner[] $routeDefiners
     */
    public readonly array $routeDefiners;

    /**
     * @param string $pattern
     * @param MiddlewareDefiner[] $middlewares
     * @param RouteDefiner[] $routeDefiners
     */
    public function __construct(
        string $pattern,
        array $middlewares,
        array $routeDefiners,
    )
    {
        $this->pattern = $pattern;
        $this->middlewares = $middlewares;
        $this->routeDefiners = $routeDefiners;
    }

    /** {@inheritdoc} */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface $routeCollector): void
    {
        $routeGroup = $routeCollector->group($this->pattern, new GroupResolver($this->routeDefiners));
        foreach($this->middlewares as $middlewareDefiner) {
            $middlewareDefiner->register($routeGroup);
        }
    }
}