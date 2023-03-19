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
     * @var bool $isNullable
     */
    public readonly bool $isNullable;

    /**
     * @readonly
     * @var bool $isCollection
     */
    public readonly bool $isCollection;

    /**
     * @param string $type
     * @param bool $isNullable
     * @param bool $isCollection
     */
    public function __construct(string $type, bool $isNullable = false, bool $isCollection = false)
    {
        $this->type = $type;
        $this->isNullable = $isNullable;
        $this->isCollection = $isCollection;
    }
}