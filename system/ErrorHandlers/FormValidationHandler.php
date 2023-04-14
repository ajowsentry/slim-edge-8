<?php

declare(strict_types=1);

namespace SlimEdge\ErrorHandlers;

use Throwable;
use Slim\Middleware\ErrorMiddleware;
use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Exceptions\NestedValidationException;

class FormValidationHandler implements ErrorHandlerInterface
{
    /**
     * @param ValidationException $exception
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        $payload = [
            'code' => 403,
            'message' => $exception->getMessage(),
        ];

        if($exception instanceof NestedValidationException) {
            $payload['errors'] = $exception->getMessages();
        }

        $response = create_response(403)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($payload));

        return $response;
    }

    /**
     * @return bool
     */
    public static function register(): bool
    {
        /** @var ?ErrorMiddleware $errorMiddleware */
        $errorMiddleware = container(ErrorMiddleware::class);
        if(is_null($errorMiddleware)) {
            return false;
        }

        $errorMiddleware->setErrorHandler(ValidationException::class, self::class, true);
        return true;
    }
}