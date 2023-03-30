<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use DateTime;
use DateTimeInterface;
use Respect\Validation\Rules;
use Respect\Validation\Validatable;

final class ValidatorRegistry
{
    /**
     * @var array<string,Validatable|string|false> $registry
     */
    private static array $registry = [
        'bool'    => Rules\BoolType::class,
        'boolean' => Rules\BoolType::class,
        'string'  => Rules\StringType::class,
        'int'     => Rules\IntType::class,
        'integer' => Rules\IntType::class,
        'float'   => Rules\FloatType::class,
        'double'  => Rules\FloatType::class,
        'array'   => Rules\ArrayType::class,
        'object'  => Rules\ObjectType::class,
        DateTime::class => Rules\DateTime::class,
        DateTimeInterface::class => Rules\DateTime::class,
    ];

    /**
     * @param string $type
     * @return bool
     */
    public static function has(string $type): bool
    {
        return array_key_exists($type, self::$registry);
    }

    /**
     * @param string $type
     * @return false|Validatable
     */
    public static function get(string $type): false|Validatable
    {
        if(!isset(self::$registry[$type]) || false === self::$registry[$type])
            return false;

        $validator = self::$registry[$type];
        if($validator instanceof Validatable)
            return $validator;

        return self::$registry[$type] = new $validator;
    }

    private function __construct() { }
}