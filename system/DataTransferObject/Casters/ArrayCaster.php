<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<array<mixed,mixed>>
 */
class ArrayCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        return (array) $value;
    }
}