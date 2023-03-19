<?php

declare(strict_types=1);

namespace SlimEdge\Route;

use Slim\Routing\RouteCollectorProxy;
use Slim\Interfaces\RouteGroupInterface;

class GroupResolver
{
    /**
     * @var RouteDefiner[] $childRoutes
     */
    private array $childRoutes;

    /**
     * @param RouteDefiner[] $routes
     */
    public function __construct(array $routes)
    {
        $this->childRoutes = $routes;
    }

    public function __invoke(RouteCollectorProxy|RouteGroupInterface $routeCollector): void
    {
        foreach($this->childRoutes as $routeDefiner) {
            $routeDefiner->register($routeCollector);
        }
    }
}