<?php
declare(strict_types=0);

namespace App\Controllers\LogReader;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimEdge\HttpLog;
use SlimEdge\Route\Attributes\Route;

class Controller
{
    #[Route\Get("/log/read")]
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
        foreach($reader->query($timestamp * 1000) as $json) {
            if($offset > 0) {
                $offset--;
                continue;
            }

            if($limit-- <= 0) {
                break;
            }
            
            $response->getBody()->write($json);
            $written = true;
        }

        if($written)
            $response->getBody()->seek(-1, SEEK_CUR);

        $response->getBody()->write(']');

        return $response->withHeader('Content-Type', 'application/json');
    }
}