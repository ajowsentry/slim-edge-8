<?php

declare(strict_types=1);

namespace App\Controllers;

use Laminas\Diactoros\Response;
use SlimEdge\Route\Attributes\Route;
use Psr\Http\Message\ResponseInterface;

#[Route\Get('/')]
class SingleActionController
{
    public function __invoke(): ResponseInterface
    {
        return new Response\TextResponse("Hello World from single action controller");
    }
}