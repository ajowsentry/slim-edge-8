<?php

declare(strict_types=1);

namespace SlimEdge\ErrorHandlers;

use Slim\App;
use Psr\Http\Message\ResponseInterface;

final class ErrorsHandler
{
    /**
     * @param App $app
     * @param string $basePath
     * @return void
     */
    public static function register(App $app, string $basePath = '/err'): void
    {
        $app->get($basePath . '/400', [self::class, 'error400']);
        $app->get($basePath . '/401', [self::class, 'error401']);
        $app->get($basePath . '/403', [self::class, 'error403']);
        $app->get($basePath . '/404', [self::class, 'error404']);
        $app->get($basePath . '/410', [self::class, 'error410']);
        $app->get($basePath . '/413', [self::class, 'error413']);
        $app->get($basePath . '/500', [self::class, 'error500']);
        $app->get($basePath . '/501', [self::class, 'error501']);
    }

    /**
     * @return ResponseInterface
     */
    public function error400(): ResponseInterface
    {
        $response = create_response(400);

        $response->getBody()->write(json_encode([
            'code' => 400,
            'message' => "The server cannot or will not process the request due to an apparent client error.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error401(): ResponseInterface
    {
        $response = create_response(401);

        $response->getBody()->write(json_encode([
            'code' => 401,
            'message' => "The request requires valid user authentication.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error403(): ResponseInterface
    {
        $response = create_response(403);

        $response->getBody()->write(json_encode([
            'code' => 403,
            'message' => "You don't have permission to access this resource.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error404(): ResponseInterface
    {
        $response = create_response(404);

        $response->getBody()->write(json_encode([
            'code' => 404,
            'message' => "You don't have permission to access this resource.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error410(): ResponseInterface
    {
        $response = create_response(410);

        $response->getBody()->write(json_encode([
            'code' => 410,
            'message' => "The target resource is no longer available at the origin server.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error413(): ResponseInterface
    {
        $response = create_response(413);

        $response->getBody()->write(json_encode([
            'code' => 413,
            'message' => "The amount of data provided in the request exceeds the capacity limit.",
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function error500(): ResponseInterface
    {
        $response = create_response(500);

        $response->getBody()->write(json_encode([
            'code' => 500,
            'message' => "Unexpected condition encountered preventing server from fulfilling request.",
        ]));

        return $response;
    }

    public function error501(): ResponseInterface
    {
        $response = create_response(501);

        $response->getBody()->write(json_encode([
            'code' => 501,
            'message' => "The server does not support the functionality required to fulfill the request.",
        ]));

        return $response;
    }
}
