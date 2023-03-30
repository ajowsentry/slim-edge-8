<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CastException;
use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<string>
 */
class StringCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        if(is_scalar($value) || is_null($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return strval($value);
        }

        $type = typeof($value);
        throw new CastException("Could not cast from type '{$type}' to string");
    }
}