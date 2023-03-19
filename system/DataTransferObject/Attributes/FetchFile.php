<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Attributes;

use Attribute;
use SlimEdge\DataTransferObject\FetchType;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class FetchFile extends Fetch
{
    public function __construct(?string $name = null)
    {
        parent::__construct(FetchType::File, $name);
    }
}