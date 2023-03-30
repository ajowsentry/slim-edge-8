<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CastException;
use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<bool>
 */
class BooleanCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        if(is_scalar($value) || is_null($value)) {
            return boolval($value);
        }

        $type = typeof($value);
        throw new CastException("Could not cast from type '{$type}' to boolean");
    }
}