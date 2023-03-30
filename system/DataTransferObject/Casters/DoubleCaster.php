<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use SlimEdge\DataTransferObject\CastException;
use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<float>
 */
class DoubleCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        if(is_numeric($value) || is_bool($value)) {
            return floatval($value);
        }

        if(is_string($value)) {
            throw new CastException("Could not cast string from '{$value}' to double");
        }

        $type = typeof($value);
        throw new CastException("Could not cast from type '{$type}' to double");
    }
}