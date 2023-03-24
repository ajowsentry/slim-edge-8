<?php

declare(strict_types=1);

namespace SlimEdge\Route\Attributes\Route;

use Attribute;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(flags: Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @var class-string<MiddlewareInterface> $middleware Middleware class
     */
    public $middleware;

    /**
     * @var array<mixed> $arguments
     */
    public $arguments = [];

    /**
     * @param class-string<MiddlewareInterface> $middleware
     * @param mixed ...$arguments
     */
    public function __construct(string $middleware, mixed ...$arguments)
    {
        if(!class_exists($middleware))
            throw new InvalidArgumentException("Could not resolve '{$middleware}' middleware");

        $this->middleware = $middleware;
        $this->arguments = $arguments;
    }
}
