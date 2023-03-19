<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use InvalidArgumentException;
use Slim\Routing\RouteContext;
use SlimEdge\Exceptions\ConfigException;
use Psr\Http\Message\ServerRequestInterface;

class Config
{
    /**
     * Is cors enabled
     * @var bool $enabled
     */
    public $enabled = true;

    /**
     * Which arigins are allowed
     * @var string[] $allowOrigins
     */
    public $allowOrigins = [ ];

    /**
     * Which headers are allowed
     * @var string[] $allowHeaders
     */
    public $allowHeaders = [ ];

    /**
     * Which credentials are allowed
     * @var string[] $allowCredentials
     */
    public $allowCredentials = [ ];

    /**
     * Which headers are allowed
     * @var string[] $exposeHeaders
     */
    public $exposeHeaders = [ ];

    /**
     * 
     * @var ?int $maxAge
     */
    public $maxAge = null;

    /**
     * Routes specific configuration.
     * Configuration is cascading and not nested
     * @var ?array<string,Config> $routes
     */
    public $routes = null;

    /**
     * @param ?array<string,mixed> $config
     */
    public function __construct(?array $config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return Config
     */
    public function forRequest(ServerRequestInterface $request): Config
    {
        if(!is_null($this->routes)) {
            $route = RouteContext::fromRequest($request)->getRoute();

            if(is_null($route))
                throw new InvalidArgumentException("Could not find route from request.");

            $routeName = $route->getName();
            return $this->routes[$routeName] ?? $this;
        }

        return $this;
    }

    /**
     * @param array<string,mixed> $config
     * @return void
     */
    protected function hydrate($config, bool $isRoot = true): void
    {
        if(isset($config['enabled'])) {
            $raw = $config['enabled'];
            if(!is_bool($raw)) {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'enabled' value in Cors config.");
            }
            $this->enabled = $raw;
        }

        $this->hydrateIterableValue($config, 'allowOrigins');
        $this->hydrateIterableValue($config, 'allowHeaders', true);
        $this->hydrateIterableValue($config, 'exposeHeaders', true);
        $this->hydrateIterableValue($config, 'allowCredentials', true, false);
        if(isset($config['maxAge'])) {
            /** @var mixed $raw */
            $raw = $config['maxAge'];
            if(is_int($raw) || is_null($raw)) {
                $resolved = $raw;
            }
            elseif(is_float($raw)) {
                $resolved = intval($raw);
            }
            elseif(is_string($raw)) {
                $timed = strtotime($raw);
                $resolved = false !== $timed ? ($timed - time()) : intval($raw);
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxAge' value in Cors config.");
            }

            if($resolved < 0) $resolved = 0;
            elseif(is_infinite($resolved)) {
                throw new ConfigException("Value for 'maxAge' must be positive integer.");
            }

            $this->maxAge = $resolved;
        }

        if($isRoot && isset($config['routes'])) {
            $raw = $config['routes'];
            if(is_array($raw)) {
                $resolved = $raw;
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'routes' value in Cors config.");
            }

            $properties = [
                'enabled',
                'allowOrigins',
                'allowHeaders',
                'allowCredentials',
                'exposeHeaders',
                'maxAge',
            ];

            foreach($resolved as $key => $value) {
                foreach($properties as $prop) {
                   !isset($value[$prop]) && $value[$prop] = $this->$prop;
                }

                $resolved[$key] = new Config;
                $resolved[$key]->hydrate($value, false);
            }

            $this->routes = $resolved;
        }
    }

    /**
     * @param array<string,mixed> $config
     * @return void
     */
    protected function hydrateIterableValue(array $config, string $key, bool $toLowerCase = false, bool $allowWildcards = true): void
    {
        if(array_key_exists($key, $config)) {
            $raw = $config[$key];
            if(is_array($raw)) {
                $resolved = $raw;
            }
            elseif(is_string($raw)) {
                $resolved = [ $raw ];
            }
            elseif(is_null($raw)) {
                $resolved = [ ];
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for '{$key}' value in Cors config.");
            }

            $resolved = array_reduce($resolved, function($acc, $item) use ($toLowerCase) {
                $item = trim($item);
                if($toLowerCase)
                    $item = strtolower($item);

                if($item !== '')
                    array_push($acc, $item);

                return $acc;
            }, []);

            if(false !== ($index = array_search('*', $resolved))) {
                unset($resolved[$index]);
                if($allowWildcards)
                    array_unshift($resolved, '*');
            }

            $this->$key = array_values($resolved);
        }
    }
}