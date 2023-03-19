<?php

declare(strict_types=1);

namespace SlimEdge\Route\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(flags: Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Route
{
    public const ValidMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'PURGE'];

    /** @var string|string[] */
    public $methods;

    /** @var string */
    public $pattern;

    /** @var ?string */
    public $name;

    /** @var array<string,mixed> */
    public $arguments;

    /**
     * @param string|string[] $methods
     * @param string $pattern
     * @param ?string $name
     * @param array<string,mixed> $arguments
     */
    public function __construct(string|array $methods, string $pattern, ?string $name = null, array $arguments = [])
    {
        $methods = array_filter(array_map('strtoupper', (array) $methods), function ($item): bool {
            return in_array($item, self::ValidMethods);
        });

        if (empty($methods)) {
            throw new InvalidArgumentException("Route has no valid method");
        }

        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->name = $name;
        $this->arguments = $arguments;
    }
}