<?php

declare(strict_types=1);

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

if(! function_exists('create_response')) {

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    function create_response(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = container(ResponseFactoryInterface::class);
        return $responseFactory->createResponse($code, $reasonPhrase);
    }
}

if(! function_exists('create_stream')) {

    /**
     * @param string|resource|StreamInterface $filename
     * @param string $mode
     */
    function create_stream($filename, $mode = 'r+'): StreamInterface
    {
        if($filename instanceof StreamFactoryInterface) {
            return $filename;
        }

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = container(StreamFactoryInterface::class);

        if(is_string($filename)) {
            return $streamFactory->createStreamFromFile($filename, $mode);
        }
        elseif(is_resource($filename)) {
            return $streamFactory->createStreamFromResource($filename);
        }
        
        $type = typeof($filename);
        throw new InvalidArgumentException("Could not resolve {$type}");
    }
}

if(! function_exists('create_stream_from_file')) {

    /**
     * @param string $filename
     * @param string $mode
     * @return StreamInterface
     */
    function create_stream_from_file(string $filename, string $mode = 'r+'): StreamInterface
    {
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = container(StreamFactoryInterface::class);
        return $streamFactory->createStreamFromFile($filename, $mode);
    }
}

if(! function_exists('create_stream_from_resource')) {

    /**
     * @param resource $resource
     * @return StreamInterface
     */
    function create_stream_from_resource($resource): StreamInterface
    {
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = container(StreamFactoryInterface::class);
        return $streamFactory->createStreamFromResource($resource);
    }
}

if(! function_exists('create_server_request')) {

    /**
     * @return ServerRequestInterface
     */
    function create_server_request(): ServerRequestInterface
    {
        /** @var ServerRequestCreatorInterface $serverRequestCreator */
        $serverRequestCreator = container(ServerRequestCreatorInterface::class);
        return $serverRequestCreator->createServerRequestFromGlobals();
    }
}