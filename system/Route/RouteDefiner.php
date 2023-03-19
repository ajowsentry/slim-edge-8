<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class RouteDefiner implements RouteDefinerInterface
{
    /**
     * @var string[] $methods
     */
    public readonly array $methods;

    /**
     * @var string $pattern
     */
    public readonly string $pattern;

    /**
     * @var ?string $name
     */
    public readonly ?string $name;

    /**
     * @var string[] $callable
     */
    public readonly array $callable;

    /**
     * @var ?array<string,string> $arguments
     */
    public readonly ?array $arguments;

    /**
     * @var MiddlewareDefiner[]
     */
    public readonly array $middlewares;

    /**
     * @param array<string> $methods
     * @param string $pattern
     * @param ?string $name
     * @param array<string> $callable
     * @param ?array<string,string> $arguments
     * @param MiddlewareDefiner[] $middlewares
     */
    public function __construct(
        array $methods,
        string $pattern,
        ?string $name,
        array $callable,
        ?array $arguments,
        array $middlewares,
    )
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->name = $name;
        $this->callable = $callable;
        $this->arguments = $arguments;
        $this->middlewares = $middlewares;
    }

    /** {@inheritdoc} */
    public function register(RouteCollectorProxyInterface|RouteGroupInterface $routeCollector): void
    {
        $route = $routeCollector->map(
            $this->methods,
            $this->pattern,
            $this->callable,
        );

        if(!is_null($this->name)) {
            $route->setName($this->name);
        }

        if(!is_null($this->arguments)) {
            $route->setArguments($this->arguments);
        }

        foreach($this->middlewares as $middlewareDefiner) {
            $middlewareDefiner->register($routeCollector);
        }
    }
}