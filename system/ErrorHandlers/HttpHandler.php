<?php

declare(strict_types=1);

namespace SlimEdge\ErrorHandlers;

use Throwable;
use Slim\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;

class HttpHandler implements ErrorHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param HttpException $exception
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     * 
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        if($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = $exception->getAllowedMethods();
            if(count($allowedMethods) === 1 && $allowedMethods[0] === 'OPTIONS') {
                return $this->__invoke(
                    $request,
                    new HttpNotFoundException($request),
                    $displayErrorDetails,
                    $logErrors,
                    $logErrorDetails
                );
            }
        }

        $payload = [
            'code'    => $exception->getCode(),
            'message' => $exception->getDescription(),
        ];

        if($exception instanceof HttpMethodNotAllowedException) {
            $payload['allowedMethods'] = $exception->getAllowedMethods();
        }

        $responseCode = $exception->getCode();
        $reasonPhrase = $exception->getMessage();
        $response = create_response($responseCode, $reasonPhrase)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode($payload));

        return $response;
    }
}