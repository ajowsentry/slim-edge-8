<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TypeOf
{
    /**
     * @readonly
     * @var string $type
     */
    public readonly string $type;

    /**
     * @readonly
     * @var string $isNullable
     */
    public readonly bool $isNullable;

    /**
     * @readonly
     * @var string $isCollection
     */
    public readonly bool $isCollection;

    public function __construct(string $type, bool $isNullable = false, bool $isCollection = false)
    {
        $this->type = $type;
        $this->isNullable = $isNullable;
        $this->isCollection = $isCollection;
    }
}