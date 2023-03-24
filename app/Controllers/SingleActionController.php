<?php

declare(strict_types=1);

namespace App\Controllers;

use Laminas\Diactoros\Response;
use SlimEdge\Route\Attributes\Route;

#[Route\Get('/')]
class SingleActionController
{
    public function __invoke()
    {
        return new Response\TextResponse("Hello World from single action controller");
    }
}