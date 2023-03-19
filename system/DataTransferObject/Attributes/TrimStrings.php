<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Attributes;

use Attribute;

/**
 * Trim each string parameter
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TrimStrings
{
    /**
     * @readonly
     * @var bool $value
     */
    public readonly bool $value;

    public function __construct(bool $value = true)
    {
        $this->value = $value;
    }
}