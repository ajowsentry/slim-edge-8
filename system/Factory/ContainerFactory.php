<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use DI;
use Psr\Container\ContainerInterface;
use SlimEdge\Support\Paths;

final class ContainerFactory
{
    /**
     * @var array<string, mixed>
     */
    private $definition = [];

    /**
     * @return ContainerInterface
     */
    public static function create(): ContainerInterface
    {
        $factory = new self;
        return $factory
            ->registerConfig()
            ->registerDependecies()
            ->build();
    }

    private function build(): ContainerInterface
    {
        $builder = new DI\ContainerBuilder();

        $builder->useAttributes(true)->useAutowiring(true);

        $config = load_config();
        $compileContainer = $config['compileContainer'] ?? false;

        assert(
            is_bool($compileContainer),
            "Compile container option must be boolean"
        );
        
        if ($compileContainer) {
            $builder->enableCompilation(Paths::Cache . '/di');
        }

        $this->definition['config'] = DI\value($config);

        return $builder->addDefinitions($this->definition)->build();
    }
    
    /**
     * @return static
     */
    private function registerConfig(): static
    {
        $configDefinition = [];
        $pattern = Paths::Config . '/*.php';
        $pushDefinition = function(string $key, string $script) use (&$configDefinition): void {
            if($key !== 'config' && strpos($key, '.') === false) {
                if (!isset($configDefinition[$key]))
                    $configDefinition[$key] = [];

                array_push($configDefinition[$key], $script);
            }
        };

        foreach(glob($pattern) as $script) {
            $key = substr(basename($script), 0, -4);
            $pushDefinition($key, $script);
        }

        if(defined('ENVIRONMENT')) {
            $pattern = Paths::Config . '/*.' . ENVIRONMENT . '.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -(strlen(ENVIRONMENT) + 5));
                $pushDefinition($key, $script);
            }

            $pattern = Paths::Config . '/' . ENVIRONMENT . '/*.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -4);
                $pushDefinition($key, $script);
            }
        }

        foreach ($configDefinition as $key => $scripts) {
            $this->definition['config.' . $key] = DI\factory([ConfigFactory::class, 'create'])
                ->parameter('configFiles', $scripts);
        }

        return $this;
    }

    /**
     * @return static
     */
    private function registerDependecies(): static
    {
        $load = function(string $key, string $script): array {
            if(false === strpos($key, '.')) {
                $result = require $script;
                assert(is_array($result), "Dependency definition must be array");
                return $result;
            }
            
            return [];
        };

        $pattern = Paths::System . '/dependencies/*.php';
        foreach(glob($pattern) as $script) {
            $key = substr(basename($script), 0, -4);
            $definition = $load($key, $script);
            $this->definition = array_merge($this->definition, $definition);
        }

        $pattern = Paths::Dependencies . '/*.php';
        foreach(glob($pattern) as $script) {
            $key = substr(basename($script), 0, -4);
            $definition = $load($key, $script);
            $this->definition = array_merge($this->definition, $definition);
        }

        if(defined('ENVIRONMENT')) {
            $pattern = Paths::Dependencies . '/*.' . ENVIRONMENT . '.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -(strlen(ENVIRONMENT) + 5));
                $definition = $load($key, $script);
                $this->definition = array_merge($this->definition, $definition);
            }

            $pattern = Paths::Dependencies . '/' . ENVIRONMENT . '/*.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -4);
                $definition = $load($key, $script);
                $this->definition = array_merge($this->definition, $definition);
            }
        }

        return $this;
    }

    private function __construct() { }
}