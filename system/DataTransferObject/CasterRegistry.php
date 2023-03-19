<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use SlimEdge\DataTransferObject\Casters\ArrayCaster;
use SlimEdge\DataTransferObject\Casters\DoubleCaster;
use SlimEdge\DataTransferObject\Casters\ObjectCaster;
use SlimEdge\DataTransferObject\Casters\StringCaster;
use SlimEdge\DataTransferObject\Casters\BooleanCaster;
use SlimEdge\DataTransferObject\Casters\IntegerCaster;
use SlimEdge\DataTransferObject\Casters\DateTimeCaster;

final class CasterRegistry
{
    /**
     * @var array<string,string|CasterInterface> $registry
     */
    private static array $registry = [
        'bool'    => BooleanCaster::class,
        'boolean' => BooleanCaster::class,
        'string'  => StringCaster::class,
        'int'     => IntegerCaster::class,
        'integer' => IntegerCaster::class,
        'float'   => DoubleCaster::class,
        'double'  => DoubleCaster::class,
        'array'   => ArrayCaster::class,
        'object'  => ObjectCaster::class,
        DateTime::class => DateTimeCaster::class,
        DateTimeInterface::class => DateTimeCaster::class,
    ];

    /**
     * @param string $type
     * @return false|CasterInterface
     */
    public static function get(string $type): false|CasterInterface
    {
        if(!isset(self::$registry[$type])) {
            return false;
        }

        $caster = self::$registry[$type];
        if($caster instanceof CasterInterface) {
            return $caster;
        }

        return self::$registry[$type] = new $caster;
    }

    /**
     * @param string $type
     * @param string|CasterInterface $caster
     */
    public static function set(string $type, string|CasterInterface $caster): void
    {
        if($caster instanceof CasterInterface) {
            self::$registry[$type] = $caster;
        }
        elseif(is_string($caster) && class_exists($caster) && is_subclass_of($caster, CasterInterface::class)) {
            self::$registry[$type] = $caster;
        }
        else {
            $caster = typeof($caster);
            throw new InvalidArgumentException("Could not resolve '{$caster}' as caster.");
        }
    }

    private function __construct() { }
}