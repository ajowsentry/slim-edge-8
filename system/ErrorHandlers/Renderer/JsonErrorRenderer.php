<?php
declare(strict_types=1);

namespace SlimEdge\ErrorHandlers\Renderer;
use Slim\Error\Renderers\JsonErrorRenderer as BaseRenderer;
use Throwable;

class JsonErrorRenderer extends BaseRenderer
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $error = ['message' => $this->getErrorTitle($exception)];

        if ($displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }

        return (string) json_encode($error, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string|int>
     */
    private function formatExceptionFragment(Throwable $exception): array
    {
        /** @var int|string $code */
        $code = $exception->getCode();
        return [
            'type' => get_class($exception),
            'code' => $code,
            'message' => $exception->getMessage(),
            'file' => substr($exception->getFile(), strlen(BASEPATH) + 1),
            'line' => $exception->getLine(),
        ];
    }
}