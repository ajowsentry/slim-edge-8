<?php
declare(strict_types=1);

namespace App\Controllers\Storage\Create;

use DI\Attribute\Inject;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\Route\Attributes\Route;

class Controller
{
    #[Inject]
    private Model $model;

    #[Route\Delete('/storage/create[/{path:.+}]')]
    public function __invoke(ServerRequestInterface $request)
    {
        $formData = DTO::fromRequest($request);
        $result = $this->model->create($formData, $request->getBody());
        if($result === false)
            return new Response\JsonResponse(['code' => 400, 'message' => 'Bad Request'], 400);
        
        return new Response\JsonResponse([
            'code' => 200,
            'message' => 'OK',
            'data' => ['files' => $result],
        ]);
    }
}