<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Attributes;

use Attribute;
use SlimEdge\DataTransferObject\FetchType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Fetch
{
    /**
     * @readonly
     * @var FetchType $type
     */
    public readonly FetchType $type;

    /**
     * @readonly
     * @var ?string $name
     */
    public readonly ?string $name;

    public function __construct(FetchType $type, ?string $name = null)
    {
        $this->type = $type;
        $this->name = $name;
    }
}