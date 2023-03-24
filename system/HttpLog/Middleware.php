<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

use Exception;
use Slim\Routing\RouteContext;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    protected const ContextNone = 0;

    protected const ContextQuery = 1;

    protected const ContextFormData = 2;

    protected const ContextBody = 4;

    protected const ContextUploadedFiles = 8;

    protected const BodyContent = 0;

    protected const BodyIgnored = 1;

    protected const BodyToFile = 2;

    /**
     * @var ?string $requestHash
     */
    protected static ?string $requestHash = null;

    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * @var null|false|Writer\BaseWriter $writer
     */
    private null|false|Writer\BaseWriter $writer = null;

    /**
     * @param null|array<string,mixed>|Config $config
     */
    public function __construct(null|array|Config $config = null)
    {
        $this->config = $config instanceof $config ? $config : new Config($config);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        try {
            $this->logRequest($request);
            $response = $handler->handle($request);
            $this->logResponse($request, $response);
            return $response;
        }
        catch(Exception $ex) {
            $this->logError($ex);
            throw $ex;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return void
     */
    private function logRequest(ServerRequestInterface $request): void
    {
        $writer = $this->getWriter();
        if(!$writer)
            return;

        $config = $this->config->logRequest;

        $route = RouteContext::fromRequest($request)->getRoute();
        if(!$config->checkRoute($route))
            return;

        $routeConfig = $this->config->getConfigForRoute($route, 'logRequest');
        if(!is_null($routeConfig))
            $config->override($routeConfig);

        if(!$config->checkMethod($request->getMethod()))
            return;

        $streamAnalyzer = $this->analyzeStream($request->getBody());

        $logData = new LogData('request', [
            'method'    => $request->getMethod(),
            'ipAddress' => get_ip_address(),
            'url'       => (string) $request->getUri(),
            'headers'   => $config->filterHeaders($request->getHeaders()),
            'bodySize'  => $streamAnalyzer->size,
        ]);

        $bodyContext = self::BodyContent;
        $logContext = self::ContextNone;
        $queryParams = null;
        $formData = null;
        $body = null;
        $uploadedFiles = null;

        if($streamAnalyzer->isBinary || ($config->maxBody && $streamAnalyzer->size > $config->maxBody)) {
            $bodyContext = $config->ignoreOnMax
                ? self::BodyIgnored
                : self::BodyToFile;
        }

        if($config->logQuery) {
            $logContext |= self::ContextQuery;
            $queryParams = $request->getQueryParams();
        }

        if($config->logFormData) {
            $logContext |= self::ContextFormData;
            $formData = $request->getParsedBody();
        }

        if($config->logBody) {
            $logContext |= self::ContextBody;
            switch($bodyContext) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $body = $this->storeAnalyzedStream($streamAnalyzer);
                break;

                default:
                $body = (string) $request->getBody();
                break;
            }
        }

        if($config->logUploadedFiles) {
            $logContext |= self::ContextUploadedFiles;
            $uploadedFiles = $this->processUploadedFiles($request->getUploadedFiles());
        }

        $logData->append('bodyContext', $bodyContext);
        $logData->append('logContext', $logContext);
        $logData->append('queryParams', $queryParams);
        $logData->append('formData', $formData);
        $logData->append('body', $body);
        $logData->append('uploadedFiles', $uploadedFiles);

        $logOption = $logData->finish();
        static::$requestHash = $logOption['hash'];
        $writer->writeLog($logOption);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    private function logResponse(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $writer = $this->getWriter();
        if(!$writer) {
            return;
        }

        $config = $this->config->logResponse;

        $route = RouteContext::fromRequest($request)->getRoute();
        if(!$this->config->logRequest->checkRoute($route) || !$config->checkRoute($route)) {
            return;
        }

        $routeConfig = $this->config->getConfigForRoute($route, 'logResponse');
        if($routeConfig) {
            $config->override($routeConfig);
        }

        if(!$config->checkStatusCode($response->getStatusCode())) {
            return;
        }

        $streamAnalyzer = $this->analyzeStream($response->getBody());

        $logData = new LogData('response', [
            'requestHash' => static::$requestHash,
            'headers'     => $config->filterHeaders($response->getHeaders()),
            'bodySize'    => $streamAnalyzer->size,
        ]);

        $bodyContext = self::BodyContent;
        $body = null;

        if($streamAnalyzer->isBinary || ($config->maxBody && $streamAnalyzer->size > $config->maxBody)) {
            if($config->ignoreOnMax) {
                $bodyContext = self::BodyIgnored;
            }
            else {
                $bodyContext = self::BodyToFile;
            }
        }

        if($config->logBody) {
            switch($bodyContext) {
                case self::BodyIgnored: break;

                case self::BodyToFile:
                $body = $this->storeAnalyzedStream($streamAnalyzer);
                break;

                default:
                $body = (string) $response->getBody();
                break;
            }
        }
        else $bodyContext = self::BodyIgnored;

        $logData->append('bodyContext', $bodyContext);
        $logData->append('body', $body);

        $logOption = $logData->finish();
        $writer->writeLog($logOption);
    }

    /**
     * @param Exception $ex
     * @return void
     */
    private function logError(Exception $ex): void
    {
        $writer = $this->getWriter();
        if(!$writer || !$this->config->logErrors) return;

        $logData = new LogData('error', [
            'requestHash'  => static::$requestHash,
            'errorClass'   => get_class($ex),
            'errorCode'    => $ex->getCode(),
            'errorMessage' => $ex->getMessage(),
            'errorFile'    => $ex->getFile(),
            'errorLine'    => $ex->getLine(),
        ]);

        $logOption = $logData->finish();
        $writer->writeLog($logOption);
    }

    /**
     * @param array<int|string,mixed> $uploadedFiles
     * @return array<int|string,mixed>
     */
    private function processUploadedFiles(array $uploadedFiles): array
    {
        $result = [];
        foreach($uploadedFiles as $index => $uploadedFile) {
            if(is_array($uploadedFile)) {
                $result[$index] = $this->processUploadedFiles($uploadedFile);
            }
            else {
                /** @var UploadedFileInterface $uploadedFile */
                $streamAnalyzer = $this->analyzeStream($uploadedFile->getStream());
                $result[$index] = $this->storeAnalyzedStream($streamAnalyzer);
            }
        }
        return $result;
    }

    /**
     * @param StreamInterface $stream
     * @return StreamAnalyzer
     */
    private function analyzeStream(StreamInterface $stream): StreamAnalyzer
    {
        return (new StreamAnalyzer($stream))->analyze();
    }

    /**
     * Store analyzed stream to new log file as reference
     * @param StreamAnalyzer $streamAnalyzer
     * @return string File path relative to log path
     */
    private function storeAnalyzedStream(StreamAnalyzer $streamAnalyzer): string
    {
        $fileName = $this->uuidFormat($streamAnalyzer->hash) . '-' . $streamAnalyzer->size;
        $path = '/files/' . substr($fileName, 0, 2);
        $directory = $this->config->path . $path;
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = "$directory/$fileName";
        if(!file_exists($filePath)) {
            $streamAnalyzer->stream->rewind();
            try {
                $stream = create_stream($filePath, 'w+');
                while(!$streamAnalyzer->stream->eof()) {
                    $content = $streamAnalyzer->stream->read(1024000);
                    $stream->write($content);
                }
            }
            finally {
                if(isset($stream))
                    $stream->close();
            }
        }

        return "{$path}/{$fileName}";
    }

    /**
     * @return ?Writer\BaseWriter
     */
    private function getWriter(): ?Writer\BaseWriter
    {
        if(is_null($this->writer)) {
            $this->writer = false;
            $writerClass = $this->config->writer;
            if(is_subclass_of($writerClass, Writer\BaseWriter::class)) {
                $this->writer = new $writerClass($this->config);
            }
        }

        return $this->writer ?: null;
    }

    /**
     * @param string $value
     * @return string
     */
    private function uuidFormat(string $value): string
    {
        // 8-4-4-4-12 => xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        return sprintf("%s-%s-%s-%s-%s",
            substr($value, 0, 8),
            substr($value, 8, 4),
            substr($value, 12, 4),
            substr($value, 16, 4),
            substr($value, 20),
        );
    }
}
