<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use SlimEdge\DataTransferObject\Attributes\Fetch;

final class PropertyMetadata
{
    /**
     * @var string $property
     */
    public readonly string $property;

    /**
     * @var bool $isNullable
     */
    public bool $isNullable = true;

    /**
     * @var bool $isCollection
     */
    public bool $isCollection = false;

    /**
     * @var string $type
     */
    public string $type = 'mixed';

    /**
     * @var bool $removeInvisibleCharacters
     */
    public bool $removeInvisibleCharacters = true;

    /**
     * @var bool $trimString
     */
    public bool $trimString = true;

    /**
     * @var list<Fetch> $fetchFrom
     */
    public array $fetchFrom = [];

    /**
     * @var ?string $mutator
     */
    public ?string $mutator = null;

    /**
     * @var ?string $validator
     */
    public ?string $validator = null;

    /**
     * @var ?string $defaultCallback
     */
    public ?string $defaultCallback = null;

    public function __construct(string $property)
    {
        $this->property = $property;
    }
}