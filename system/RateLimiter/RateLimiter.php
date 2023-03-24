<?php

declare(strict_types=1);

namespace SlimEdge\RateLimiter;

use Attribute;
use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
class RateLimiter implements MiddlewareInterface
{
    /**
     * @var string $key
     */
    private string $key;

    /**
     * @var int $limit
     */
    private int $limit;

    /**
     * @var int $ttl
     */
    private int $ttl;

    /**
     * @param string $key
     * @param int $limit
     * @param int $ttl
     */
    public function __construct(string $key, int $limit = 100, int $ttl = 5)
    {
        $this->key = $key;
        $this->limit = $limit;
        $this->ttl = $ttl;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if(($retryAfter = $this->tryAccess()) === true)
            return $handler->handle($request);
        
        $response = create_response(429, 'Too Many Requests')
            ->withHeader('Retry-After', strval($retryAfter))
            ->withHeader('Content-Type', 'application/json');
        
        $response->getBody()->write(json_encode([
            'code' => 429,
            'message' => "Too Many Requests.",
        ]));

        return $response;
    }

    private function tryAccess(): true|int
    {
        /** @var CacheInterface */
        $cache = container(CacheInterface::class);

        $key = 'RateLimiter#' . get_ip_address() . '#' . $this->key;
        
        $time = time();
        $bucket = $cache->get($key, []);

        while(count($bucket) > 0 && ($time - $bucket[0]) > $this->ttl)
            array_shift($bucket);

        if(count($bucket) >= $this->limit)
            return $time - $bucket[0] - $this->ttl;

        array_push($bucket, $time);
        $cache->set($key, $bucket, $this->ttl);

        return true;
    }
}