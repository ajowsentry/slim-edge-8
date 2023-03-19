<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

use Attribute;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequireCredential implements MiddlewareInterface
{
    protected Decoder $decoder;

    protected Config $config;

    public function __construct(array|Config $config)
    {
        $this->decoder = new Decoder($config);
        $this->config = $config instanceof Config ? $config : new Config($config);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler)
        : ResponseInterface
    {
        $token = $this->fetchToken($request);

        if(empty($token)) {
            throw new HttpUnauthorizedException($request);
        }
        
        try {
            $credential = $this->decoder->decode($token);

            $request = $request
                ->withAttribute('credential', $credential)
                ->withAttribute('token', $token);

            return $handler->handle($request);
        }
        catch(UnexpectedValueException $ex) {
            throw new JWTException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function fetchToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine($this->config->getHeader());
        preg_match($this->config->getPattern(), $header, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }
}