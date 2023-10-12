<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

use Exception;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\UploadedFileInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    protected const ContextNone = 0;

    protected const ContextQuery = 1;

    protected const ContextFormData = 2;

    protected const ContextBody = 4;

    protected const ContextUploadedFiles = 8;

    protected const BodyIgnored = 0;

    protected const BodyContent = 1;

    protected const BodyToFile = 2;

    protected const BodyParsed = 3;

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
        $path = substr($request->getUri()->getPath(), strlen(get_base_path()));

        if(!$config->checkPath($path))
            return;

        $routeConfig = $this->config->getConfigForPath($path, 'logRequest');
        if(!is_null($routeConfig))
            $config->override($routeConfig);

        if(!$config->checkMethod($request->getMethod()))
            return;

        $streamAnalyzer = $this->analyzeStream($request->getBody());

        $logData = new LogData('request', [
            'method'    => $request->getMethod(),
            'ipAddress' => get_ip_address(),
            'url'       => (string) $request->getUri()->withQuery(''),
            'headers'   => (object) $config->filterHeaders($request->getHeaders()),
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
            $queryParams = $request->getQueryParams();

            if(!empty($queryParams))
                $logContext |= self::ContextQuery;
        }

        if($config->logFormData) {
            $formData = $request->getParsedBody();

            if(!empty($formData)) {
                $bodyContext = self::BodyParsed;
                $logContext |= self::ContextFormData;
            }
        }

        if($config->logBody) {

            switch($bodyContext) {
                case self::BodyIgnored:
                case self::BodyParsed:
                    break;

                case self::BodyToFile:
                $body = $this->storeAnalyzedStream($streamAnalyzer);
                $logContext |= self::ContextBody;
                break;

                default:
                $body = (string) $request->getBody();
                if(!empty($body))
                    $logContext |= self::ContextBody;
                break;
            }
        }

        if($config->logUploadedFiles) {
            $_uploadedFiles = $request->getUploadedFiles();
            if(!empty($_uploadedFiles)) {
                $logContext |= self::ContextUploadedFiles;
                $uploadedFiles = $this->processUploadedFiles($_uploadedFiles);
            }
        }

        $logData->append('logContext', $logContext);
        if($logContext & self::ContextBody) {
            $logData->append('bodyContext', $bodyContext);
            $logData->append('bodySize', $streamAnalyzer->size);
            $logData->append('body', $body);
        }

        if($logContext & self::ContextQuery) {
            $logData->append('queryParams', $queryParams);
        }

        if($logContext & self::ContextFormData) {
            $logData->append('bodyContext', $bodyContext);
            $logData->append('bodySize', $streamAnalyzer->size);
            $logData->append('formData', $formData);
        }

        if($logContext & self::ContextUploadedFiles) {
            $logData->append('uploadedFiles', $uploadedFiles);
        }

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
        $path = substr($request->getUri()->getPath(), strlen(get_base_path()));

        if(!$config->checkPath($path))
            return;

        $routeConfig = $this->config->getConfigForPath($path, 'logResponse');
        if(!is_null($routeConfig))
            $config->override($routeConfig);

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

        if($response instanceof JsonResponse) {
            $logData->append('bodyContext', self::BodyParsed);
            $logData->append('body', $response->getPayload());
        }
        else {
            $logData->append('bodyContext', $bodyContext);
            $logData->append('body', $body);
        }

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
            'errorFile'    => substr($ex->getFile(), strlen(BASEPATH)),
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
        $hash = separate_string($streamAnalyzer->hash, '-', 8, 4, 4, 4);
        $fileName = $hash . '-' . base_convert(strval($streamAnalyzer->size), 10, 16);
        $path = '/files/' . substr($fileName, 0, 2);
        $directory = $this->config->path . $path;
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = "{$directory}/{$fileName}";
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
        else touch($filePath);

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
}
