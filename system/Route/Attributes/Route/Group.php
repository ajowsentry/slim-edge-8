<?php

declare(strict_types=1);

namespace SlimEdge\Route\Attributes\Route;

use Attribute;

#[Attribute(flags: Attribute::TARGET_CLASS)]
class Group
{
    /** @var ?string */
    public $pattern = null;

    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern;
    }
}