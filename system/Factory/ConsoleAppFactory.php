<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use SlimEdge\Support\Paths;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

final class ConsoleAppFactory
{
    /**
     * @var array<string,mixed> $config
     */
    private $config = [];

    /**
     * @var Application $consoleApp
     */
    private $consoleApp;

    /**
     * @param ContainerInterface $container
     * @return Application
     */
    public static function create(ContainerInterface $container): Application
    {
        $factory = new self;

        $factory->config = $container->has('config.console')
            ? $container->get('config.console')
            : [];

        $factory->consoleApp = new Application(
            $factory->config['name'] ?? 'Slim Edge',
            $factory->config['version'] ?? 'UNKNOWN',
        );

        $factory->registerCommands();

        return $factory->consoleApp;
    }

    /**
     * @return void
     */
    public function registerCommands(): void
    {
        $pattern = Paths::System . '/Commands/*.php';

        foreach(rglob($pattern) as $item) {
            $class = str_replace('/', '\\', "SlimEdge\\Commands\\" . substr($item, strlen($pattern) - 5, -4));
            $this->consoleApp->add(new $class);
        }

        if($this->config['autoload'] ?? false) {
            $commandFolder = $this->config['folder'];
            $pattern = Paths::App . '/' . $commandFolder . '/*.php';

            foreach(rglob($pattern) as $item) {
                $class = str_replace('/', '\\', "App\\" . $commandFolder . "\\" . substr($item, strlen($pattern) - 5, -4));
                $this->consoleApp->add(new $class);
            }
        }
        else {
            foreach($this->config['commands'] ?? [] as $command) {
                $this->consoleApp->add(new $command);
            }
        }
    }
}