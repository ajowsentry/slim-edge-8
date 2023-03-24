<?php

declare(strict_types=1);

namespace SlimEdge\Route\Attributes\Route;

use Attribute;
use SlimEdge\Route\Attributes\Route;

#[Attribute(flags: Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Put extends Route
{
    /**
     * @param string $pattern
     * @param ?string $name
     * @param array<string,mixed> $arguments
     */
    public function __construct(string $pattern, ?string $name = null, array $arguments = [])
    {
        parent::__construct(['PUT'], $pattern, $name, $arguments);
    }
}