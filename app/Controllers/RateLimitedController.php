<?php

declare(strict_types=1);

namespace App\Controllers;

use Laminas\Diactoros\Response;
use SlimEdge\Route\Attributes\Route;
use SlimEdge\RateLimiter\RateLimiter;
use Psr\Http\Message\ResponseInterface;

class RateLimitedController
{
    #[Route\Get("/rate-limited")]
    #[RateLimiter('ex', limit: 5, ttl: 30)]
    public function get(): ResponseInterface
    {
        return new Response\TextResponse("Hello World from rate limited controller");
    }
}