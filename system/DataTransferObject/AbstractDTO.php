<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

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
     * @var ?array<string,mixed> $expandedProperties
     */
    private ?array $expandedProperties = null;
    
    /**
     * @var array<string,mixed> $raw
     */
    private array $raw = [];

    /**
     * @var bool|null|Validatable $validator
     */
    private bool|null|Validatable $validator = null;

    /**
     * @return array<string,PropertyMetadata>
     */
    protected static function getMetadata(): array
    {
        return MetadataRegistry::get(static::class);
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
        foreach(static::getMetadata() as $property => $metadata) {

            foreach($metadata->fetchFrom as $fetch) {
                if(array_key_exists($property, $params))
                    break;
                
                if($fetch->type === FetchType::Query) {
                    if(!isset($queryParams))
                        $queryParams = $request->getQueryParams();

                    if(array_key_exists($key = $fetch->name ?? $property, $queryParams))
                        $params[$property] = $queryParams[$key];
                }
                elseif($fetch->type === FetchType::Body) {
                    if(!isset($bodyParams))
                        $bodyParams = $request->getParsedBody();

                    if(array_key_exists($key = $fetch->name ?? $property, $bodyParams))
                        $params[$property] = $bodyParams[$key];
                }
                elseif($fetch->type === FetchType::File) {
                    if(!isset($fileParams))
                        $fileParams = $request->getUploadedFiles();

                    if(array_key_exists($key = $fetch->name ?? $property, $fileParams))
                        $params[$property] = $fileParams[$key];
                }
                elseif($fetch->type === FetchType::Args) {
                    if(!isset($argsParams))
                        $argsParams = RouteContext::fromRequest($request)
                            ->getRoutingResults()
                            ->getRouteArguments();

                    if(array_key_exists($key = $fetch->name ?? $property, $argsParams))
                        $params[$property] = $argsParams[$key];
                }
                elseif($fetch->type === FetchType::Header) {
                    if($request->hasHeader($key = $fetch->name ?? $property))
                        $params[$property] = $request->getHeaderLine($key);
                }
            }
        }

        return new static($params);
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
                if($metadata->isCollection) {
                    $output = array_map(fn($item) => $caster->cast($item), $value);
                }
                else {
                    $output = $caster->cast($value);
                }
                return true;
            }
            catch(CastException $ex) {
                /** @ignore */
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
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach($this->getProperties() as $property) {
            $raw = $this->$property;
            if($raw instanceof AbstractDTO) {
                $resolved = $raw->toArray();
            }
            elseif(is_array($raw)) {
                $resolved = array_map(fn($value) => $value instanceof AbstractDTO ? $value->toArray() : $value, $raw);
            }
            else {
                $resolved = $raw;
            }

            $result[$property] = $resolved;
        }

        return $result;
    }

    /**
     * @return ?Validatable
     */
    protected function getValidator()
    {
        if(false === $this->validator)
            return null;
        
        if($this->validator instanceof Validatable)
            return $this->validator;

        $rules = new Rules\AllOf();
        foreach($this->getMetadata() as $key => $metadata) {
            $fieldRules = [];

            if(false !== ($validator = ValidatorRegistry::get($metadata->type)))
                array_push($fieldRules, $validator);

            if(!is_null($callback = $metadata->validator)) {
                $validators = $callback();
                if($validators instanceof Validatable) {
                    if($validators instanceof AbstractComposite) {
                        array_push($fieldRules, ...$validators->getRules());
                    }
                    else {
                        array_push($fieldRules, $validators);
                    }
                }
            }

            if(count($fieldRules) > 0) {
                $rule = count($fieldRules) > 1
                    ? new Rules\AllOf(...$fieldRules)
                    : $fieldRules[0];

                if($metadata->isCollection)
                    $rule = new Rules\Each($rule);

                if($metadata->isNullable)
                    $rule = new Rules\Nullable($rule);

                $rules->addRule(new Rules\Key($key, $rule));
            }
        }

        if(count($rules->getRules()) > 0)
            return $this->validator = $rules;

        $this->validator = false;
        return null;
    }

    /**
     * Run validation, throws on error
     */
    public function check(): void
    {
        $this->getValidator()
            ->check($this->toArray());
    }

    /**
     * Run validation, return false on error
     * @return bool
     */
    public function validate(): bool
    {
        return $this->getValidator()
            ->validate($this->toArray());
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
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}