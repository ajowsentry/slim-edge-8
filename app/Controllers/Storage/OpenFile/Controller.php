<?php
declare(strict_types=1);

namespace App\Controllers\Storage\OpenFile;

use DI\Attribute\Inject;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\Route\Attributes\Route;

class Controller
{
    #[Inject]
    private Model $model;

    #[Route\Get('/storage/open[/{path:.+}]', 'storageOpenFile')]
    public function __invoke(ServerRequestInterface $request)
    {
        $formData = DTO::fromRequest($request);
        $result = $this->model->openFile($formData);
        if($result === 0)
            return new Response\JsonResponse(['code' => 404, 'message' => 'Not found'], 404);
        
        return create_response_from_file($result);
    }
}