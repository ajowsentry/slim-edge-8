<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_METHOD)]
final class ExposeJson
{
    /**
     * @var bool|string $name
     */
    public bool|string $name;

    /**
     * @param bool|string $name
     */
    public function __construct(bool|string $name = true)
    {
        $this->name = $name;
    }
}