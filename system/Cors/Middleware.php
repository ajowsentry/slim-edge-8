<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * @param array<string,mixed>|Config $config
     */
    public function __construct(array|Config $config)
    {
        $this->config = $config instanceof Config ? $config : new Config($config);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $response = $handler->handle($request);

        if($this->config->forRequest($request)->enabled) {
            $analyzer = new Analyzer($this->config, $request);
            $corsHeaders = $analyzer->analyze();
            foreach($corsHeaders as $key => $value)
                $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}