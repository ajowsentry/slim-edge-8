<?php

declare(strict_types=1);

use SlimEdge\Kernel;
use Slim\Routing\RouteContext;
use Slim\Interfaces\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

if(! function_exists('container')) {

    /**
     * @param string $key
     * @return mixed
     */
    function container(string $key): mixed
    {
        if(Kernel::getContainer()->has($key)) {
            return Kernel::getContainer()->get($key);
        }

        return null;
    }
}

if (!function_exists('route')) {

    /**
     * @param ServerRequestInterface|string $request Server request or route name
     * @return ?RouteInterface
     */
    function route($request): ?RouteInterface
    {
        if($request instanceof ServerRequestInterface) {
            return RouteContext::fromRequest($request)->getRoute();
        }

        try {
            $routeCollector = Kernel::getApp()->getRouteCollector();
            return $routeCollector->getNamedRoute($request);
        }
        catch(\RuntimeException) { /** Ignored */ }

        return null;
    }
}