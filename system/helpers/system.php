<?php

declare(strict_types=1);

use Slim\App;
use SlimEdge\Support\Paths;

if(! function_exists('env_aware_file')) {

    /**
     * @param string $filepath
     * @return string[] Absolute filepaths
     */
    function env_aware_file(string $filepath): array
    {
        $files = [];
        if(is_file($resolved = $filepath . '.php')) {
            array_push($files, $resolved);
        }

        if(defined('ENVIRONMENT')) {
            if(is_file($resolved = $filepath . '.' . ENVIRONMENT . '.php')) {
                array_push($files, $resolved);
            }

            if(is_file($resolved = dirname($filepath) . '/' . ENVIRONMENT . '/' . basename($filepath) . '.php')) {
                array_push($files, $resolved);
            }
        }

        return $files;
    }
}

if(! function_exists('load_config')) {

    /**
     * @param string $name Config name
     * @return array<string,mixed>
     */
    function load_config(string $name = 'config'): array
    {
        $config = [];

        $load = function(string $path): array {
            $config = require $path;
            assert(is_array($config), "Config file '{$path}' must return array");
            return $config;
        };

        foreach(env_aware_file(Paths::Config . '/' . $name) as $path) {
            $config = array_merge_deep($config, $load($path));
        }

        return $config;
    }
}

if(! function_exists('load_helper')) {

    /**
     * @param string $name Helper name
     * @return void
     */
    function load_helper(string $name): void
    {
        $load = function(string $script): void {
            require_once $script;
        };

        $helpers = env_aware_file(Paths::Helpers . '/' . $name);
        foreach(array_reverse($helpers) as $script) {
            $load($script);
        }
    }
}

if(! function_exists('load_route')) {

    /**
     * @param string $name Route name
     * @return void
     */
    function load_route(App $app, string $name): void
    {
        $load = function(App $app, string $script): void {
            require_once $script;
        };

        $routes = env_aware_file(Paths::Routes . '/' . $name);
        if(count($routes) > 0 && $script = end($routes)) {
            $load($app, $script);
        }
    }
}

if(! function_exists('load_dependency')) {

    /**
     * @param string $name Route name
     * @param string $basePath Base path
     * @return array<string,mixed>
     */
    function load_dependency(string $name, string $basePath = Paths::Dependencies): array
    {
        $load = function(string $script): array {
            $result = require $script;
            assert(is_array($result), "Dependency file '{$script}' must return array");
            return $result;
        };

        return array_reduce(
            env_aware_file($basePath . '/' . $name),
            fn(array $acc, string $item): array => array_merge($acc, $load($item)),
            []
        );
    }
}

if(! function_exists('typeof')) {

    /**
     * @param mixed $value
     * @return string
     */
    function typeof($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}

if(! function_exists('rglob')) {

    /**
     * @param string $pattern
     * @param int $flags
     * @param ?callable $filter
     * @return array<string>
     */
    function rglob(string $pattern, int $flags = 0, ?callable $filter = null): array {
        $files = glob($pattern, $flags);

        if(!is_null($filter))
            $files = array_filter($files, $filter);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $_files = rglob($dir . '/' . basename($pattern), $flags, $filter);

            if(!is_null($filter))
                $_files = array_filter($_files, $filter);

            $files = array_merge($files, $_files);
        }

        return $files;
    }
}