<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use DI;
use Psr\Container\ContainerInterface;
use SlimEdge\Support\Paths;

final class ContainerFactory
{
    /**
     * @var array<string, mixed> $definition
     */
    private array $definition = [];
    
    /**
     * @var array<string, mixed> $config
     */
    private array $config = [];

    /**
     * @return ContainerInterface
     */
    public static function create(): ContainerInterface
    {
        $factory = new self;
        $factory->config = load_config();
        return $factory->build();
    }

    private function build(): ContainerInterface
    {
        $compileContainer = $this->config['compileContainer'] ?? false;

        assert(
            is_bool($compileContainer),
            "Compile container option must be boolean"
        );

        $directory = Paths::Cache . '/di';
        if($compileContainer && file_exists($compiledPath = $directory . '/CompiledContainer.php')) {
            call_user_func(function($path) { require_once $path; }, $compiledPath);
            $builder = new DI\ContainerBuilder('CompiledContainer');
        }
        else {
            $builder = new DI\ContainerBuilder();

            $this->definition['config'] = DI\value($this->config);
            $this->registerConfig()->registerDependecies();
            $builder->addDefinitions($this->definition);
        }

        if ($compileContainer) {
            $builder->enableCompilation($directory);
        }

        return $builder
            ->addDefinitions($this->getAlwaysLoadDependencies())
            ->useAttributes(true)
            ->useAutowiring(true)
            ->build();
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

        $alwaysLoad = [
            'database',
            'hashids',
        ];

        $pattern = Paths::System . '/dependencies/*.php';
        foreach(glob($pattern) as $script) {
            $key = substr(basename($script), 0, -4);
            if(!in_array($key, $alwaysLoad)) {
                $definition = $load($key, $script);
                $this->definition = array_merge($this->definition, $definition);
            }
        }

        $alwaysLoad = $this->config['alwaysLoadDependencies'] ?? [];

        $pattern = Paths::Dependencies . '/*.php';
        foreach(glob($pattern) as $script) {
            $key = substr(basename($script), 0, -4);
            if(!in_array($key, $alwaysLoad)) {
                $definition = $load($key, $script);
                $this->definition = array_merge($this->definition, $definition);
            }
        }

        if(defined('ENVIRONMENT')) {
            $pattern = Paths::Dependencies . '/*.' . ENVIRONMENT . '.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -(strlen(ENVIRONMENT) + 5));
                if(!in_array($key, $alwaysLoad)) {
                    $definition = $load($key, $script);
                    $this->definition = array_merge($this->definition, $definition);
                }
            }

            $pattern = Paths::Dependencies . '/' . ENVIRONMENT . '/*.php';
            foreach(glob($pattern) as $script) {
                $key = substr(basename($script), 0, -4);
                if(!in_array($key, $alwaysLoad)) {
                    $definition = $load($key, $script);
                    $this->definition = array_merge($this->definition, $definition);
                }
            }
        }

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    private function getAlwaysLoadDependencies(): array {
        $dependencies = $this->config['alwaysLoadDependencies'] ?? [];

        $result = array_merge(
            load_dependency('database', Paths::System . '/dependencies'),
            load_dependency('hashids', Paths::System . '/dependencies'),
        );

        foreach($dependencies as $dependency) {
            $result = array_merge($result, load_dependency($dependency));
        }

        return $result;
    }

    private function __construct() { }
}