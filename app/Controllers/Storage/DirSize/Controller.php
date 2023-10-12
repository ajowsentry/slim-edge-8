<?php
declare(strict_types=1);

namespace App\Controllers\Storage\DirSize;

use DI\Attribute\Inject;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\Route\Attributes\Route;

class Controller
{
    #[Inject]
    private Model $model;

    #[Route\Get('/storage/dirsize[/{path:.+}]')]
    public function __invoke(ServerRequestInterface $request)
    {
        $formData = DTO::fromRequest($request);
        $result = $this->model->dirSize($formData);
        if($result === false)
            return new Response\JsonResponse(['code' => 404, 'message' => 'Not found'], 404);
        
        return new Response\JsonResponse([
            'code' => 200,
            'message' => 'OK',
            'data' => [
                'size' => $result
            ],
        ]);
    }
}