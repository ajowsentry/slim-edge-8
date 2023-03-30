<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<object>
 */
class ObjectCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        return is_object($value) ? $value : (object) $value;
    }
}