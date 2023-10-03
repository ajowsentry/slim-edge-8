<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

use Slim\Routing\RouteContext;
use Slim\Interfaces\RouteInterface;
use SlimEdge\Exceptions\ConfigException;
use Psr\Http\Message\ServerRequestInterface;

class Config
{
    /**
     * @var ?int $maxFileSize
     */
    public ?int $maxFileSize;

    /**
     * @var ?int $maxDays
     */
    public ?int $maxDays;

    /**
     * @var ?string $path
     */
    public ?string $path;
    
    /**
     * @var bool $logErrors
     */
    public bool $logErrors = false;

    /**
     * @var ?string $writer
     */
    public ?string $writer = null;

    /**
     * @var ?array<string,mixed> $routes
     */
    public ?array $routes = null;

    /**
     * @var Config\Request $logRequest
     */
    public Config\Request $logRequest;

    /**
     * @var Config\Response $logResponse
     */
    public Config\Response $logResponse;

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
     * @param array<string,mixed> $config
     * @param bool $isRoot
     */
    private function hydrate(array $config, bool $isRoot = true): void
    {
        if(isset($config['maxFileSize'])) {
            $raw = $config['maxFileSize'];
            if(is_int($raw) || is_float($raw) || is_string($raw)) {
                $resolved = intval($raw);
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxFileSize' value in HttpLog config.");
            }

            if($resolved < 0) {
                throw new ConfigException("Invalid value for 'maxFileSize' in HttpLog config. Value must be positive integer.");
            }

            $this->maxFileSize = $resolved;
        }

        if(isset($config['maxDays'])) {
            $raw = $config['maxDays'];
            if(is_int($raw) || is_float($raw) || is_string($raw)) {
                $resolved = intval($raw);
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxDays' value in HttpLog config.");
            }

            if($resolved < 1) {
                throw new ConfigException("Invalid value for 'maxDays' in HttpLog config. Value must be positive integer.");
            }

            $this->maxFileSize = $resolved;
        }

        if(isset($config['logErrors'])) {
            if(!is_bool($config['logErrors'])) {
                $type = typeof($config['logErrors']);
                throw new ConfigException("Could not resolve {$type} for 'logErrors' value in HttpLog config.");
            }

            $this->logErrors = $config['logErrors'];
        }

        if(isset($config['path'])) {
            if(!is_string($config['path'])) {
                $type = typeof($config['path']);
                throw new ConfigException("Could not resolve {$type} for 'path' value in HttpLog config.");
            }

            $this->path = $config['path'];
        }

        if(isset($config['writer'])) {
            if(!is_string($config['writer'])) {
                $type = typeof($config['writer']);
                throw new ConfigException("Could not resolve {$type} for 'writer' value in HttpLog config.");
            }

            $this->writer = strval($config['writer']);
        }

        $this->logRequest = new Config\Request($config['logRequest'] ?? null);
        $this->logResponse = new Config\Response($config['logResponse'] ?? null);

        if($isRoot && isset($config['routes'])) {
            $raw = $config['routes'];
            if(is_array($raw)) {
                $resolved = $raw;
            }
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'routes' value in HttpLog config.");
            }

            $properties = [
                'maxFileSize',
                'path',
                'logErrors',
                'writer',
                'logRequest',
                'logResponse',
            ];

            foreach($resolved as $key => $value) {
                foreach($properties as $prop) {
                   !isset($value[$prop]) && $value[$prop] = $this->$prop;
                }

                $resolved[$key] = new Config;
                $resolved[$key]->hydrate($value, false);
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return Config
     */
    public function forRequest(ServerRequestInterface $request): Config
    {
        if(!is_null($this->routes) && $request->getAttribute(RouteContext::ROUTING_RESULTS, false)) {
            $routeName = RouteContext::fromRequest($request)->getRoute()->getName();
            return $this->routes[$routeName] ?? $this;
        }

        return $this;
    }

    /**
     * @param RouteInterface $route
     * @param string $key
     * @return ?array<string,mixed>
     */
    public function getConfigForRoute(RouteInterface $route, string $key): ?array
    {
        if(!empty($route->getName()) && is_array($this->routes))
            if(isset($this->routes[$route->getName()][$key]))
                return $this->routes[$route->getName()][$key];

        return null;
    }

    /**
     * @param string $route
     * @param string $key
     * @return ?array<string,mixed>
     */
    public function getConfigForPath(string $path, string $key): ?array
    {
        if(!empty($path) && is_array($this->routes))
            if(isset($this->routes[$path][$key]))
                return $this->routes[$path][$key];

        return null;
    }
}
