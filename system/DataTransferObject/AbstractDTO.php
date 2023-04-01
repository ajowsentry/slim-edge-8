<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

use Error;
use BackedEnum;
use ReflectionEnum;
use JsonSerializable;
use Respect\Validation\Rules;
use Slim\Routing\RouteContext;
use Respect\Validation\Validatable;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Rules\AbstractComposite;

abstract class AbstractDTO implements JsonSerializable
{
    /**
     * Allow to add property dynamically
     * @var bool $expandable
     */
    protected static bool $expandable = false;
    
    /**
     * Expose expanded property when serializing to JSON
     * @var bool $exposeExpandedProperty
     */
    protected static bool $exposeExpandedProperty = false;

    /**
     * @var ?array<string,mixed> $expandedProperties
     */
    private ?array $expandedProperties = null;
    
    /**
     * @var array<string,mixed> $raw
     */
    private array $raw = [];
    
    /**
     * @var array<string,string> $nameReferenceMap
     */
    private array $nameReferenceMap = [];

    /**
     * @return array<string,PropertyMetadata>
     */
    protected static function getMetadata(): array
    {
        return MetadataRegistry::get(static::class);
    }

    /**
     * @return ?Validatable
     */
    protected function getValidator(): ?Validatable
    {
        return ValidatorRegistry::get(static::class);
    }

    /**
     * @param ?array<string,mixed> $params
     */
    public final function __construct(?array $params = null)
    {
        if(!is_null($params)) {
            $this->hydrate($params);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return static
     */
    public static function fromRequest(ServerRequestInterface $request): static
    {
        $params = [];
        $nameReferenceMap = [];
        foreach(static::getMetadata() as $property => $metadata) {

            foreach($metadata->fetchFrom as $fetch) {
                if(array_key_exists($property, $params))
                    break;
                
                if($fetch->type === FetchType::Query) {
                    if(!isset($queryParams))
                        $queryParams = $request->getQueryParams();

                    if(array_key_exists($key = $fetch->name ?? $property, $queryParams)) {
                        $nameReferenceMap[$property] = $key;
                        $params[$property] = $queryParams[$key];
                    }
                }
                elseif($fetch->type === FetchType::Body) {
                    if(!isset($bodyParams))
                        $bodyParams = (array) $request->getParsedBody();

                    if(array_key_exists($key = $fetch->name ?? $property, $bodyParams)) {
                        $nameReferenceMap[$property] = $key;
                        $params[$property] = $bodyParams[$key];
                    }
                }
                elseif($fetch->type === FetchType::File) {
                    if(!isset($fileParams))
                        $fileParams = $request->getUploadedFiles();

                    if(array_key_exists($key = $fetch->name ?? $property, $fileParams)) {
                        $nameReferenceMap[$property] = $key;
                        $params[$property] = $fileParams[$key];
                    }
                }
                elseif($fetch->type === FetchType::Args) {
                    if(!isset($argsParams))
                        $argsParams = RouteContext::fromRequest($request)
                            ->getRoutingResults()
                            ->getRouteArguments();

                    if(array_key_exists($key = $fetch->name ?? $property, $argsParams)) {
                        $nameReferenceMap[$property] = $key;
                        $params[$property] = $argsParams[$key];
                    }
                }
                elseif($fetch->type === FetchType::Header) {
                    if($request->hasHeader($key = $fetch->name ?? $property)) {
                        $nameReferenceMap[$property] = $key;
                        $params[$property] = $request->getHeaderLine($key);
                    }
                }
            }
        }

        $result = new static($params);
        $result->nameReferenceMap = $nameReferenceMap;
        return $result;
    }

    /**
     * @param array<string,mixed> $params
     * @return void
     */
    protected function hydrate(array $params): void
    {
        foreach($params as $key => $value) {
            $this->set($key, $value);
        }

        $metadataMap = static::getMetadata();
        foreach(array_diff($this->getProperties(), array_keys($params)) as $key) {
            if(!is_null($function = $metadataMap[$key]->defaultCallback)) {
                $this->set($key, call_user_func([$this, $function]));
            }
            elseif(is_subclass_of($metadataMap[$key]->type, ScalarDTO::class)) {
                $this->set($key, new ($metadataMap[$key]->type)());
            }
        }
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function set(string $offset, mixed $value): void
    {
        $metadataMap = static::getMetadata();
        if(!isset($metadataMap[$offset])) {
            $this->setExpandedProperty($offset, $value);
            return;
        }

        $metadata = $metadataMap[$offset];
        $this->raw[$offset] = $value;

        if(is_string($value)) {
            if($metadata->removeInvisibleCharacters)
                $value = remove_invisible_characters($value);

            if($metadata->trimString)
                $value = trim($value);
        }

        if(!is_null($mutator = $metadata->mutator)) {
            $value = call_user_func([$this, $mutator], $value);
        }

        if($this->tryCast($offset, $value, $castResult)) {
            $value = $castResult;
        }

        $this->$offset = $value;
    }

    /**
     * @param string $name
     */
    public function __get(string $name): mixed
    {
        return $this->getExpandedProperty($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setExpandedProperty($name, $value);
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @param mixed &$output
     * @return bool
     */
    protected function tryCast(string $offset, mixed $value, mixed &$output): bool
    {
        $metadataMap = static::getMetadata();
        $metadata = $metadataMap[$offset];

        if($metadata->type == 'mixed' || ($metadata->isNullable && is_null($value))) {
            $output = $value;
            return true;
        }

        if(false !== ($caster = CasterRegistry::get($metadata->type))) {
            try {
                $output = $metadata->isCollection
                    ? array_map(fn($item) => $caster->cast($item), $value)
                    : $caster->cast($value);

                return true;
            }
            catch(CastException) {
                /** @ignore */
            }
        }

        if(is_subclass_of($metadata->type, ScalarDTO::class) && !($value instanceof ScalarDTO)) {
            $output = $metadata->isCollection
                ? array_map(fn($item) => new ($metadata->type)($item), $value)
                : new ($metadata->type)($value);

            return true;
        }

        if(is_subclass_of($metadata->type, BackedEnum::class) && !($value instanceof BackedEnum)) {
            $enum = new ReflectionEnum($metadata->type);
            $caster = match($enum->getBackingType()->getName()) {
                'string' => 'strval',
                'int'    => 'intval',
                default  => null,
            };

            if(!is_null($caster)) {
                $output = $metadata->isCollection
                    ? array_map(fn($item) => $metadata->type::tryFrom(($caster)($item)), $value)
                    : $metadata->type::tryFrom(($caster)($value));
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function rawGet(string $offset): mixed
    {
        if(!array_key_exists($offset, $this->raw))
            throw new PropertyNotExistsException(sprintf("Property '%s' not exists in '%s' class", $offset, static::class));

        return $this->raw[$offset];
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function rawSet(string $offset, mixed $value): void
    {
        if(!array_key_exists($offset, $this->raw))
            throw new PropertyNotExistsException(sprintf("Property '%s' not exists in '%s' class", $offset, static::class));

        $this->raw[$offset] = $value;
    }

    /**
     * @return string[]
     */
    private function getProperties(): array
    {
        $arr = array_keys(static::getMetadata());
        return is_array($this->expandedProperties)
            ? array_merge($arr, array_keys($this->expandedProperties))
            : $arr;
    }

    /**
     * @param bool $forJson Result expected for JSON serialize
     * @return array<string,mixed>
     */
    public function toArray(bool $forJson = false): array
    {
        $result = [];
        $metadataMap = static::getMetadata();
        foreach($this->getProperties() as $property) {
            try {
                $raw = $this->$property;
                if($raw instanceof AbstractDTO) {
                    $resolved = $forJson ? $raw->jsonSerialize() : $raw->toArray();
                }
                elseif($raw instanceof ScalarDTO) {
                    $resolved = $raw->get();
                }
                elseif($raw instanceof BackedEnum) {
                    $resolved = $raw->value;
                }
                elseif(is_array($raw)) {
                    $resolved = [];
                    if(count($raw) > 0) {
                        if(array_key_exists($property, $metadataMap)) {
                            $type = $metadataMap[$property]->type;
                            if(is_subclass_of($type, AbstractDTO::class)) {
                                $resolver = $forJson
                                    ? fn($value) => $value?->jsonSerialize()
                                    : fn($value) => $value?->toArray();
                            }
                            elseif(is_subclass_of($type, ScalarDTO::class)) {
                                $resolver = fn($value) => $value?->get();
                            }
                            elseif(is_subclass_of($type, BackedEnum::class)) {
                                $resolver = fn($value) => $value?->value;
                            }
                        }

                        if(!isset($resolver)) {
                            $resolver = fn($value) => $value;
                        }

                        $resolved = array_map($resolver, $raw);
                    }
                }
                else {
                    $resolved = $raw;
                }

                $result[$property] = $resolved;
            }
            catch(Error) {
                /** @ignore */
            }
        }

        return $result;
    }

    /**
     * Run validation, throws on error
     */
    public function check(): void
    {
        $this->getValidator()
            ?->check($this->toArray());
    }

    /**
     * Run validation, return false on error
     * @return bool
     */
    public function validate(): bool
    {
        return $this->getValidator()
            ?->validate($this->toArray()) ?? true;
    }

    /**
     * @param string $offset
     * @return mixed
     */
    private function getExpandedProperty(string $offset): mixed
    {
        if(!static::$expandable)
            return null;

        return $this->expandedProperties[$offset] ?? null;
    }

    /**
     * @param string $offset
     * @param mixed $value
     * 
     * @return void
     */
    private function setExpandedProperty(string $offset, mixed $value): void
    {
        if(!static::$expandable)
            return;

        if(is_null($this->expandedProperties))
            $this->expandedProperties = [];

        $this->expandedProperties[$offset] = $value;
        $this->raw[$offset] = $value;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [];

        $data = $this->toArray(true);
        foreach(static::getMetadata() as $key => $metadata) {
            if($metadata->exposeJson === true)
                $result[$key] = $data[$key];

            elseif(is_string($metadata->exposeJson))
                $result[$metadata->exposeJson] = $data[$key];

            unset($data[$key]);
        }

        return static::$exposeExpandedProperty
            ? array_merge($result, $data)
            : $result;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize());
    }
}