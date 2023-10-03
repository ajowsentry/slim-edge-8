<?php
declare(strict_types=0);

namespace App\Controllers\LogReader;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\HttpLog;
use SlimEdge\Route\Attributes\Route;

class Controller
{
    #[Route\Get("/log/read", 'readLog')]
    public function readLog(ServerRequestInterface $request): ResponseInterface
    {
        $formData = DTO::fromRequest($request);

        $config = new HttpLog\Config(container('config.http_logger'));
        $reader = new HttpLog\Reader\FileReader2($config);

        $timestamp = $formData->date->getTimestamp();
        $limit = $formData->limit;
        $offset = $formData->offset;
        
        $response = create_response();
        $response->getBody()->write('[');
        $written = false;
        foreach($reader->query($timestamp, $offset) as $json) {
            if($limit-- <= 0)
                break;
            
            $written = true;
            $response->getBody()->write($json);
        }

        if($written)
            $response->getBody()->seek(-1, SEEK_CUR);

        $response->getBody()->write(']');

        return $response->withHeader('Content-Type', 'application/json');
    }
}