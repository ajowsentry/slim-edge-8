<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTO\UserDTO;
use Laminas\Diactoros\Response;
use SlimEdge\Route\Attributes\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicController
{
    #[Route\Get("/basic")]
    public function get(): ResponseInterface
    {
        return new Response\TextResponse("Hello World from basic controller");
    }
    
    #[Route\Post("/login")]
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $formData = UserDTO::fromRequest($request);
        return new Response\JsonResponse($formData);
    }
}