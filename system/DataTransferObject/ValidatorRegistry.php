<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use Error;
use ReflectionClass;
use ReflectionEnum;
use ReflectionProperty;
use Respect\Validation\Rules;
use Respect\Validation\Rules\AbstractComposite;
use Respect\Validation\Validatable;
use SlimEdge\DataTransferObject\Attributes\Fetch;
use SlimEdge\Support\Paths;

final class ValidatorRegistry
{
    private static bool $autoloadRegistered = false;

    /**
     * @var array<string,Validatable|string|null> $registry
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

    private static array $definitionRegistry = [
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
     * @return ?Validatable
     */
    public static function get(string $type): ?Validatable
    {
        if(array_key_exists($type, self::$registry)) {

            $validator = self::$registry[$type];

            if($validator instanceof Validatable)
                return $validator;

            elseif(is_null($validator))
                return null;
            
            else return self::$registry[$type] = new $validator;
        }

        if(is_subclass_of($type, AbstractDTO::class)) {
            self::registerSplAutoload();
            $validator = get_cache($type, null, 'validator');

            if(is_null($validator)) {
                $validator = self::createDTOValidator($type);
                if(!is_null($validator)) {
                    set_cache($type, $validator, 'validator');
                }
            }

            return self::$registry[$type] = $validator;
        }
        
        return self::$registry[$type] = null;
    }

    /**
     * @template T of AbstractDTO
     * @param class-string<T> $class
     * @return ?Validatable
     */
    private static function createDTOValidator(string $class)
    {
        return new ("{$class}Validator");
    }

    private static function registerSplAutoload(): void
    {
        self::$autoloadRegistered = self::$autoloadRegistered || spl_autoload_register(static function(string $class) {
            if(str_ends_with($class, 'Validator') && is_subclass_of($dtoClass = substr($class, 0, -9), AbstractDTO::class)) {
                $scriptPath = Paths::Cache . '/dto/' . str_replace('\\', '/', $class) . '.php';
                if(is_file($scriptPath)) {
                    include $scriptPath;
                    return;
                }

                $directory = dirname($scriptPath);
                if(!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }

                $script = self::generateDTOValidatorClass($dtoClass);
                if(false !== file_put_contents($scriptPath, $script)) {
                    include $scriptPath;
                }
            }
        });
    }

    /**
     * @template T of AbstractDTO
     * @param class-string<T> $class
     */
    private static function generateDTOValidatorClass(string $class)
    {
        $rules = [];
        $metadataMap = MetadataRegistry::get($class);

        // $definer = null;
        foreach($metadataMap as $key => $metadata) {
            $fieldRules = [];

            if(array_key_exists($metadata->type, self::$definitionRegistry)) {
                $definer = self::$definitionRegistry[$metadata->type];
                array_push($fieldRules, "new \\{$definer}");
            }

            elseif(is_subclass_of($metadata->type, ScalarDTO::class)) {
                array_push($fieldRules, "\\{$metadata->type}::getValidator()");
            }

            elseif(is_subclass_of($metadata->type, BackedEnum::class)) {
                $type = (new ReflectionEnum($metadata->type))->getBackingType()->getName();
                if(array_key_exists($type, self::$definitionRegistry)) {
                    $definer = self::$definitionRegistry[$type];
                    array_push($fieldRules, "new \\{$definer}");
                }
            }

            if(!is_null($method = $metadata->validator))
                array_push($fieldRules, "\\{$class}::{$method}()");

            elseif(is_subclass_of($metadata->type, AbstractDTO::class))
                array_push($fieldRules, "new \\{$metadata->type}Validator");

            $rule = null;
            if(count($fieldRules) > 0) {
                if(count($fieldRules) == 1) {
                    $rule = $fieldRules[0];
                }
                else {
                    $rule = "new Rules\AllOf(\n"
                        . join("", array_map(fn($item) => "    {$item},\n", $fieldRules))
                        . ")";
                }

                if($metadata->isCollection) {
                    $rule = "new Rules\Each({$rule})";
                }

                if($metadata->isNullable) {
                    $rule = "new Rules\Nullable({$rule})";
                }
            }

            $quotedKey = '"' . escape_php_string($key) . '"';
            $rule = !is_null($rule)
                ? "\$rule = new Rules\Key({$quotedKey}, {$rule});\n"
                : "\$rule = new Rules\Key({$quotedKey});\n";

            $namelist = array_unique(array_map(
                fn(Fetch $fetch) => $fetch->name,
                $metadata->fetchFrom,
            ));

            if(count($namelist) > 1) {
                $lastName = array_pop($namelist);
                $name = join(', ', $namelist) . ' or ' . $lastName;
            }

            elseif(count($namelist) == 1) {
                $name = $namelist[0];
            }

            if(isset($name)) {
                $quotedName = '"' . escape_php_string($name) . '"';
                $rule .= "\$rule->setName({$quotedName});\n";
            }

            $rule .= "\$this->addRule(\$rule);";

            array_push($rules, $rule);
        }

        $script = file_get_contents(__DIR__ . '/CompiledValidator.tpl');
        $script = str_replace('ValidatorNamespace', substr($class, 0, strrpos($class, '\\')), $script);
        $script = str_replace('ValidatorName', basename($class) . "Validator", $script);
        $script = str_replace("// Implementation", ltrim(shift_indent(join("\n\n", $rules), 12), ' '), $script);

        return $script;
    }

    private function __construct() { }
}