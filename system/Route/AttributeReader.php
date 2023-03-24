<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\App;
use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;
use SlimEdge\Support\Paths;
use SlimEdge\Route\Attributes\Route;
use Psr\Http\Server\MiddlewareInterface;

class AttributeReader
{
    /**
     * @var string[] $folders
     */
    private readonly array $folders;

    /**
     * @param App $app
     * @param string[] $folders Controller folders
     */
    public static function register(App $app, array $folders): void
    {
        $self = new self($folders);
        foreach($self->getRoutes() as $route) {
            $route->register($app);
        }
    }

    /**
     * @param string[] $folders Controller folders
     */
    public function __construct(array $folders)
    {
        $this->folders = array_map(function(string $folder): string {
            return Paths::App . '/' . $folder;
        }, $folders);
    }

    /**
     * @return array<RouteDefiner|RouteGroupDefiner>
     */
    public function getRoutes(): array
    {
        if($this->fetchRoutesFromCache($result)) {
            return $result;
        }

        $resolvedRoutes = [];
        foreach($this->getControllerClasses() as $controllerClass) {
            array_push($resolvedRoutes, ...$this->getControllerRoutes(new ReflectionClass($controllerClass)));
        }
        
        set_cache('routes', $resolvedRoutes, 'routes');

        return $resolvedRoutes;
    }

    /**
     * @param ?array<RouteDefiner|RouteGroupDefiner> &$result
     * @return bool
     */
    public function fetchRoutesFromCache(?array &$result): bool
    {
        $result = get_cache('routes', null, 'routes');
        return !is_null($result);
    }

    /**
     * @return string[]
     */
    public function getControllerClasses(): array
    {
        $classes = [];
        foreach($this->folders as $folder) {
            foreach(rglob($folder . '/*.php') as $file) {
                $controller = str_replace(
                    [Paths::App, '.php', '/'],
                    ['App', '', '\\'],
                    $file
                );

                if(class_exists($controller)) {
                    array_push($classes, $controller);
                }
            }
        }

        return $classes;
    }

    /**
     * @param ReflectionClass $class
     * @return array<RouteDefiner|RouteGroupDefiner>
     */
    public function getControllerRoutes(ReflectionClass $class): array
    {
        $resolvedRoutes = [];

        $methodRoutes = [];
        foreach($class->getMethods() as $method) {
            array_push($methodRoutes, ...$this->getMethodRoutes($method));
        }

        $middlewares = $this->getMiddlewares($class);

        $routeAttributes = $class->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
        foreach($routeAttributes as $routeAttribute) {
            $route = $routeAttribute->newInstance();
            array_push($methodRoutes, new RouteDefiner(
                $route->methods,
                $route->pattern,
                $route->name,
                $class->getName(),
                $route->arguments,
                $middlewares,
            ));
        }

        if(count($methodRoutes) > 0) {

            $groupRoutes = array_map(
                fn(ReflectionAttribute $groupAttribute) => new RouteGroupDefiner(
                    $groupAttribute->newInstance()->pattern,
                    $middlewares,
                    $methodRoutes,
                ),
                $class->getAttributes(Route\Group::class)
            );

            if(count($groupRoutes) > 0) {
                $resolvedRoutes = $groupRoutes;
            }
            elseif(count($middlewares) > 0) {
                array_push($resolvedRoutes, new RouteGroupDefiner('', $middlewares, $methodRoutes));
            }
            else {
                $resolvedRoutes = $methodRoutes;
            }
        }


        return $resolvedRoutes;
    }

    /**
     * @param ReflectionMethod $method
     * @return RouteDefiner[]
     */
    public function getMethodRoutes(ReflectionMethod $method): array
    {
        $resolvedRoutes = [];

        if($method->isPublic() && !$method->isStatic()) {
            $routeAttributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
            if(count($routeAttributes) > 0) {
                $callable = [$method->getDeclaringClass()->getName(), $method->getName()];
                $middlewares = $this->getMiddlewares($method);
                foreach($routeAttributes as $routeAttribute) {
                    $route = $routeAttribute->newInstance();
                    array_push($resolvedRoutes, new RouteDefiner(
                        $route->methods,
                        $route->pattern,
                        $route->name,
                        $callable,
                        $route->arguments,
                        $middlewares,
                    ));
                }
            }
        }

        return $resolvedRoutes;
    }

    /**
     * @param ReflectionMethod|ReflectionClass $reflection
     * @return MiddlewareDefiner[]
     */
    public function getMiddlewares(ReflectionMethod|ReflectionClass $reflection): array
    {
        $resolvedMiddlewares = [];

        $middlewareAttributes = $reflection->getAttributes();
        foreach($middlewareAttributes as $middlewareAttribute) {
            if($middlewareAttribute->getName() === Route\Middleware::class) {
                $middlewareDefinition = $middlewareAttribute->newInstance();
                array_push($resolvedMiddlewares, new MiddlewareDefiner(
                    $middlewareDefinition->middleware,
                    $middlewareDefinition->arguments,
                ));
            }
            elseif(is_subclass_of($middlewareAttribute->getName(), MiddlewareInterface::class)) {
                array_push($resolvedMiddlewares, new MiddlewareDefiner(
                    $middlewareAttribute->getName(),
                    $middlewareAttribute->getArguments(),
                ));
            }
        }

        return $resolvedMiddlewares;
    }
}