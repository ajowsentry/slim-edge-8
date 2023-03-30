<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use SlimEdge\Exceptions\ConfigException;

if(! function_exists('enable_cache')) {

    /**
     * @param string $scope
     */
    function enable_cache(string $scope): bool
    {
        if(is_cli()) {
            return false;
        }

        /**
         * @var array<string,mixed> $config
         */
        $config = container('config');

        $cacheEnabled = $config['enableCache'] ?? false;
        if(is_bool($cacheEnabled)) {
            return $cacheEnabled;
        }

        if(is_array($cacheEnabled)) {
            return in_array($scope, $cacheEnabled, true);
        }

        if(is_string($cacheEnabled)) {
            return $cacheEnabled === $scope;
        }
        
        $class = typeof($cacheEnabled);
        throw new ConfigException("Could not resolve '{$class}' for config enableCache");
    }
}

if(! function_exists('get_cache')) {

    /**
     * @param string  $key
     * @param mixed   $default
     * @param ?string $scope
     * @return mixed
     */
    function get_cache(string $key, $default = null, ?string $scope = null): mixed
    {
        if(is_null($scope) || enable_cache($scope)) {
            /** @var CacheInterface $cache */
            $cache = container(CacheInterface::class);
            $cacheKey = str_replace(['\\', '/'], ['_', '_'], $key);
            return $cache->get($scope ? "{$scope}-{$cacheKey}" : $cacheKey, $default);
        }

        return $default;
    }
}

if(! function_exists('set_cache')) {

    /**
     * @param string  $key
     * @param mixed   $value
     * @param ?string $scope
     * @return bool True on success and false on failure.
     */
    function set_cache(string $key, mixed $value, ?string $scope = null): bool
    {
        if(is_null($scope) || enable_cache($scope)) {
            /** @var CacheInterface $cache */
            $cache = container(CacheInterface::class);
            $cacheKey = str_replace(['\\', '/'], ['_', '_'], $key);
            return $cache->set($scope ? "{$scope}-{$cacheKey}" : $cacheKey, $value);
        }

        return false;
    }
}

if(! function_exists('data_cache')) {

    /**
     * @template T
     * @param string|list<string> $key
     * @param Closure():T $getter
     * @param int $ttl
     * @return T
     */
    function data_cache(array|string $key, Closure $getter, int $ttl = 5): mixed
    {
        if(enable_cache('data')) {
            /** @var CacheInterface $cache */
            $cache = container(CacheInterface::class);
            $cacheKey = 'data-' . str_replace(
                ['\\', '/'],
                ['_', '_'],
                is_array($key) ? join('.', $key) : $key
            );

            if($cache->has($cacheKey))
                return $cache->get($cacheKey);
        }

        $data = $getter();

        if(isset($cache, $cacheKey))
            $cache->set($cacheKey, $data, $ttl);

        return $data;
    }
}