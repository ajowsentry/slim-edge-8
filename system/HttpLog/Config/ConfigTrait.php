<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Config;

use Slim\Interfaces\RouteInterface;

trait ConfigTrait
{
    /**
     * @param array<string,mixed> $config
     * @return void
     */
    public function override(array $config): void
    {
        $this->hydrate($config);
    }

    /**
     * @param array<string,list<string>> $headers
     * @return array<string,string>
     */
    public function filterHeaders(array $headers): array
    {
        $newHeaders = !is_null($this->headers)
            ? array_intersect_key($headers, array_flip($this->headers))
            : $headers;
        
        $finalHeaders = !is_null($this->ignoreHeaders)
            ? array_diff_key($newHeaders, array_flip($this->ignoreHeaders))
            : $newHeaders;

        return array_map(fn(array $values) => join(', ', $values), $finalHeaders);
    }

    /**
     * @param ?RouteInterface $route
     * @return bool
     */
    public function checkRoute(?RouteInterface $route): bool
    {
        if(is_null($route)) {
            return true;
        }

        $routeName = $route->getName();
        if(is_null($routeName)) {
            $routeAction = $route->getCallable();
            if(is_string($routeAction)) {
                $routeName = $routeAction;
            }
            elseif(is_array($routeAction)) {
                $routeName = join(':', $routeAction);
            }
            elseif(is_object($routeAction)) {
                $routeName = get_class($routeAction);
            }
        }

        if(is_null($routeName)) {
            return true;
        }

        if(!is_null($this->routes) && !in_array($routeName, $this->routes)) {
            return false;
        }

        if(!is_null($this->ignoreRoutes) && in_array($routeName, $this->ignoreRoutes)) {
            return false;
        }

        if(!is_null($this->ignoreRoutes) && in_array($routeName, $this->ignoreRoutes)) {
            return false;
        }

        return true;
    }

    public function checkPath(string $path)
    {
        if(is_null($this->ignoreRoutes))
            return true;
        
        foreach($this->ignoreRoutes as $route) {
            if($route == $path || (str_ends_with($route, '*') && str_starts_with($path, substr($route, 0, -1))))
                return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $config
     * @param string $key
     * @return ?array<mixed>
     */
    public function getConfigItemArray(array $config, string $key): ?array
    {
        if(isset($config[$key])) {
            $configItem = $config[$key];
            if(is_array($configItem)) {
                return $configItem;
            }
            elseif(is_string($configItem)) {
                $configItem = [$configItem];
            }
            elseif(is_scalar($configItem)) {
                $configItem = [strval($configItem)];
            }
            else {
                throw new \RuntimeException("Could not resolve '{$key}' value from config");
            }

            return array_map('strval', $configItem);
        }

        return null;
    }

    /**
     * @param string $value
     * @return string
     */
    public function normalizeHeader(string $value): string
    {
        return str_replace('_', '-', strtolower($value));
    }
}