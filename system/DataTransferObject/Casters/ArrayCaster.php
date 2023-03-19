<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CasterInterface;

class ArrayCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        return (array) $value;
    }
}