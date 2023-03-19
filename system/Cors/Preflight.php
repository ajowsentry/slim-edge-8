<?php

declare(strict_types=1);

namespace SlimEdge\Cors;

use Psr\Http\Message\ResponseInterface;

class Preflight
{
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}