<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;

final class MiddlewareDefiner implements RouteDefinerInterface
{
    /**
     * @var string $middlewareClass
     */
    public readonly string $middlewareClass;

    /**
     * @var array<mixed> $arguments
     */
    public readonly array $arguments;

    /**
     * @param string $middlewareClass
     * @param array<mixed> $arguments
     */
    public function __construct(string $middlewareClass, array $arguments = [])
    {
        $this->middlewareClass = $middlewareClass;
        $this->arguments = $arguments;
    }

    /** {@inheritdoc} */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface $routeCollector): void
    {
        $routeCollector->add(
            count($this->arguments) > 0
                ? new ($this->middlewareClass)(...$this->arguments)
                : $this->middlewareClass
        );
    }
}