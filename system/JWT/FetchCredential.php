<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

use Attribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class FetchCredential extends RequireCredential
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $token = $this->fetchToken($request);
        $request = $request->withAttribute('token', $token);
        try {
            $credential = $this->decoder->decode($token);
            $request = $request->withAttribute('credential', $credential);
        }
        catch(HttpUnauthorizedException|JWTException) {
            /** @ignore */
        }
        finally {
            return $handler->handle($request);
        }
    }
}