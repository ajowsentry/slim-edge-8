<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

class KeySource
{
    /**
     * @readonly
     * @var string $source
     */
    public readonly string $source;

    /**
     * @param string $source
     */
    final public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * @param string $source
     * @return static
     */
    public static function create(string $source): static
    {
        return new static($source);
    }

    /**
     * @return string
     */
    public function resolve(): string
    {
        return file_get_contents($this->source);
    }
}