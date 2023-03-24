<?php

declare(strict_types=1);

namespace App\Controllers;

use Laminas\Diactoros\Response;
use SlimEdge\Route\Attributes\Route;

class BasicController
{
    #[Route\Get("/basic")]
    public function get()
    {
        return new Response\TextResponse("Hello World from basic controller");
    }
}