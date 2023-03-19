<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

final class ConfigFactory
{
    /**
     * @param string[] $configFiles
     * @return array<string,mixed>
     */
    public static function create(array $configFiles = []): array
    {
        $config = [];
        $load = function(string $path): array {
            $config = require $path;
            assert(is_array($config), "Config file '{$path}' must return array");
            return $config;
        };

        foreach($configFiles as $path) {
            $config = array_merge_deep($config, $load($path));
        }

        return $config;
    }

    private function __construct() { }
}