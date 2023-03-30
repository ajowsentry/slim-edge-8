<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject\Casters;

use DateTime;
use DateTimeInterface;
use SlimEdge\DataTransferObject\CastException;
use SlimEdge\DataTransferObject\CasterInterface;

/**
 * @implements CasterInterface<DateTime>
 */
class DateTimeCaster implements CasterInterface
{
    /** {@inheritdoc} */
    public function cast(mixed $value): mixed
    {
        if($value instanceof DateTime) {
            return $value;
        }

        if($value instanceof DateTimeInterface) {
            return new DateTime($value->format(DateTimeInterface::RFC3339_EXTENDED));
        }

        if(false !== ($dateObject = date_create($value))) {
            return $dateObject;
        }

        if(is_string($value)) {
            throw new CastException("Could not cast string from '{$value}' to date");
        }

        $type = typeof($value);
        throw new CastException("Could not cast from type '{$type}' to date");
    }
}