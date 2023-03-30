<?php

declare(strict_types=1);

namespace SlimEdge;

use Slim\App;
use ErrorException;
use SlimEdge\Support\Paths;
use SlimEdge\Factory\AppFactory;
use Psr\Container\ContainerInterface;
use SlimEdge\Factory\ContainerFactory;
use SlimEdge\Factory\ConsoleAppFactory;
use Symfony\Component\Console\Application;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel
{
    /**
     * @var ContainerInterface $container
     */
    private static ?ContainerInterface $container = null;
    
    /**
     * @var App $app
     */
    private static ?App $app = null;

    /**
     * @var Application $consoleApp
     */
    private static ?Application $consoleApp = null;

    final public function __construct() { }

    /**
     * @return ContainerInterface
     */
    public final static function getContainer(): ContainerInterface
    {
        return self::$container ??= ContainerFactory::create();
    }

    /**
     * @return App
     */
    public final static function getApp(): App
    {
        return self::$app ??= AppFactory::create(self::getContainer());
    }

    /**
     * @return Application
     */
    public final static function getConsoleApp(): Application
    {
        return self::$consoleApp ??= ConsoleAppFactory::create(self::getContainer());
    }

    /**
     * @return static
     */
    public static function boot(): static
    {
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_EXCEPTION, 1);

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (0 === error_reporting())
                return false;

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        self::loadHelpers();

        $config = static::getContainer()->get('config');
        date_default_timezone_set($config['timezone'] ?? 'UTC');

        return new static;
    }

    /**
     * @param ?ServerRequestInterface $serverRequest
     * @return void
     */
    public function runApp(?ServerRequestInterface $serverRequest = null): void
    {
        static::getApp()->run($serverRequest);
    }

    /**
     * @param ?InputInterface $input
     * @param ?OutputInterface $output
     * @return int
     */
    public function runConsoleApp(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        return static::getConsoleApp()->run($input, $output);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        is_cli() ? $this->runConsoleApp() : $this->runApp();
    }

    /**
     * @return void
     */
    private static function loadHelpers(): void
    {
        require_once Paths::System . '/helpers/_autoload.php';
        if(is_dir($dir = Paths::Helpers)) {
            foreach(glob($dir . '/*.php') as $filename) {
                $key = substr(basename($filename), 0, -4);
                if(false === strpos($key, '.')) {
                    load_helper($key);
                }
            }
        }
    }
}