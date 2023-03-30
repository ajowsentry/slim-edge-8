<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

/**
 * @template T
 */
interface CasterInterface
{
    /**
     * @param mixed $value
     * @return T
     */
    public function cast(mixed $value): mixed;
}