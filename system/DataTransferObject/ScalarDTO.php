<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use Stringable;
use JsonSerializable;
use Respect\Validation\Validatable;

/**
 * @template T of scalar
 */
abstract class ScalarDTO implements JsonSerializable, Stringable
{
    /**
     * @var null|T $value
     */
    public mixed $value = null;

    /**
     * @var string $type
     */
    protected string $type;

    /**
     * @param scalar $value
     */
    final public function __construct(mixed $value)
    {
        $this->set($value);
    }

    /**
     * @return null|T
     */
    final public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @param scalar $value
     * @return void
     */
    public function set(mixed $value): void
    {
        $value = $this->mutate($value);

        if(false !== $this->tryCast($value, $castResult)) {
            $value = $castResult;
        }

        $this->value = $value;
    }

    /**
     * @return Validatable
     */
    public function getValidator(): ?Validatable
    {
        return ValidatorRegistry::get($this->type) ?: null;
    }

    protected function tryCast(mixed $value, mixed &$output): bool
    {
        if(false !== ($caster = CasterRegistry::get($this->type))) {
            try {
                $output = $caster->cast($value);
                return true;
            }
            catch(CastException) {
                /** @ignore */
            }
        }
        
        $output = null;
        return false;
    }

    /**
     * @param scalar $value
     * @return scalar
     */
    protected function mutate(mixed $value): mixed
    {
        return $value;
    }

    public function getDefaultValue(): mixed
    {
        return null;
    }

    /**
     * @return scalar
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return strval($this->value);
    }
}