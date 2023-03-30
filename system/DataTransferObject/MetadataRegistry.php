<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use ReflectionClass;
use ReflectionProperty;
use ReflectionAttribute;
use ReflectionNamedType;
use InvalidArgumentException;
use SlimEdge\DataTransferObject\Attributes\Fetch;
use SlimEdge\DataTransferObject\Attributes\TypeOf;
use SlimEdge\DataTransferObject\Attributes\TrimStrings;

final class MetadataRegistry
{
    /**
     * Property metadatas stored
     * @var array<string,array<string,PropertyMetadata>> $registry
     */
    private static array $registry = [];

    /**
     * @param string $className
     * @return array<string,PropertyMetadata>
     */
    public static function get(string $className): array
    {
        if(isset(static::$registry[$className])) {
            return static::$registry[$className];
        }

        if(is_null($metadatas = get_cache($className, null, 'dto'))) {
            $metadatas = self::create($className);
            set_cache($className, $metadatas, 'dto');
        }

        return static::$registry[$className] = $metadatas;
    }

    /**
     * @param string $className
     * @return array<string,PropertyMetadata>
     */
    public static function create(string $className): array
    {
        if(!class_exists($className))
            throw new ClassNotFoundException("Class '{$className}' not found.");
        
        if(!is_subclass_of($className, AbstractDTO::class))
            throw new InvalidArgumentException("Class '{$className}' must extends from '" . AbstractDTO::class. "'");
        
        $propertyMetadatas = [];

        $class = new ReflectionClass($className);
        foreach($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if($property->isStatic())
                continue;
            
            $metadata = new PropertyMetadata($property->getName());
            $type = $property->getType();
            $typeOfAttributes = $property->getAttributes(TypeOf::class);
            if(isset($typeOfAttributes[0])) {
                $typeOf = $typeOfAttributes[0]->newInstance();
                $metadata->type = $typeOf->type;
                $metadata->isNullable = $typeOf->isNullable;
                $metadata->isCollection = $typeOf->isCollection;
            }
            elseif($type instanceof ReflectionNamedType) {
                $metadata->type = $type->getName();
                $metadata->isNullable = $type->allowsNull();
            }
            elseif(!is_null($type)) {
                $refType = typeof($type);
                throw new InvalidTypeException("DTO does not support property '{$property}' with type {$refType}");
            }

            $fetchFromAttrubutes = $property->getAttributes(Fetch::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach($fetchFromAttrubutes as $attribute) {
                $fetchFrom = $attribute->newInstance();
                array_push($metadata->fetchFrom, $fetchFrom);
            }

            $trimStringsAttributes = $property->getAttributes(TrimStrings::class);
            if(isset($trimStringsAttributes[0])) {
                $trimStrings = $trimStringsAttributes[0]->newInstance();
                $metadata->trimString = $trimStrings->value;
            }

            $removeInvisibleCharactersAttributes = $property->getAttributes(TrimStrings::class);
            if(isset($removeInvisibleCharactersAttributes[0])) {
                $removeInvisibleCharacters = $removeInvisibleCharactersAttributes[0]->newInstance();
                $metadata->removeInvisibleCharacters = $removeInvisibleCharacters->value;
            }

            if($class->hasMethod($mutator = 'mutate' . to_pascal_case($metadata->property))) {
                $metadata->mutator = $mutator;
            }

            if($class->hasMethod($validator = 'get' . to_pascal_case($metadata->property) . 'Validator')) {
                $metadata->validator = $validator;
            }

            if($class->hasMethod($defaultCallback = 'getDefault' . to_pascal_case($metadata->property))) {
                $metadata->defaultCallback = $defaultCallback;
            }

            $propertyMetadatas[$metadata->property] = $metadata;
        }

        return $propertyMetadatas;
    }

    private function __construct() { }
}