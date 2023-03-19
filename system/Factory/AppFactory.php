<?php

declare(strict_types=1);

namespace SlimEdge\Factory;

use Slim\App;
use SlimEdge\Cors;
use DI\Bridge\Slim;
use SlimEdge\Route;
use SlimEdge\Support\Paths;
use SlimEdge\Cors\Preflight;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpException;
use SlimEdge\ErrorHandlers\HttpHandler;

final class AppFactory
{
    /**
     * @var array<string,mixed> $config
     */
    private $config = [];

    /**
     * @var App $app
     */
    private $app;

    /**
     * @param ContainerInterface $container
     * @return App
     */
    public static function create(ContainerInterface $container): App
    {
        $factory = new self;
        $factory->app = Slim\Bridge::create($container);
        $factory->config = $container->get('config');

        $factory->registerRoutes()
            ->registerMiddlewares();

        return $factory->app;
    }

    /**
     * @return static
     */
    public function registerRoutes(): static
    {
        $this->app->addRoutingMiddleware();

        $config = $this->app->getContainer()->get('config.routing');

        if($config['caching'] ?? false) {
            $routerCacheFile = Paths::Cache . '/routeDispatcher.php';
            $this->app->getRouteCollector()->setCacheFile($routerCacheFile);
        }

        foreach($config['routes'] ?? [] as $route)
            load_route($this->app, $route);

        $routeBasePath = $config['basePath'] ?? get_base_path();
        if(!is_null($routeBasePath))
            $this->app->setBasePath($routeBasePath);

        $attributeRouting = $config['attributeDiscovery'] ?? true;
        if($attributeRouting) {
            $attributeRoutingFolder = (array) ($config['attributeDiscoveryFolder'] ?? 'Controllers');
            Route\AttributeReader::register($this->app, $attributeRoutingFolder);
        }

        $this->app->options('{uri:.+}', Preflight::class)->setName('preflight');

        return $this;
    }

    /**
     * @return static
     */
    public function registerMiddlewares(): static
    {
        $middlewares = $this->config['middlewares'] ?? [];
        foreach($middlewares as $middleware) {
            $this->app->add($middleware);
        }

        if($this->config['enableBodyParsing'] ?? true) {
            $this->app->addBodyParsingMiddleware();
        }

        $this->registerErrorHandler();
        $this->app->add(Cors\Middleware::class);

        return $this;
    }

    /**
     * @return static
     */
    public function registerErrorHandler(): static
    {
        if($this->config['enableErrorHandler'] ?? true) {
            $middleware = $this->app->addErrorMiddleware(
                $this->config['displayErrorDetails'] ?? false,
                $this->config['logErrors'] ?? false,
                $this->config['logErrorDetails'] ?? false
            );

            $middleware->setErrorHandler(HttpException::class, HttpHandler::class, true);

            $handlers = $this->config['handlers'] ?? [];
            foreach($handlers as $handler => $types) {
                $middleware->setErrorHandler($types, $handler, true);
            }
        }

        return $this;
    }

    private function __construct() { }
}