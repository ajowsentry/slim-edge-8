<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

interface CasterInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function cast(mixed $value): mixed;
}