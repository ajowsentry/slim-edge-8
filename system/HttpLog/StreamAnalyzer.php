<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;
use Psr\Http\Message\StreamInterface;

/**
 * Analyze stream size and hash
 */
final class StreamAnalyzer
{
    /**
     * @var StreamInterface $stream
     */
    public StreamInterface $stream;

    /**
     * @var int $size
     */
    public int $size;

    /**
     * @var string $hash
     */
    public string $hash;

    /**
     * @var bool $isBinary
     */
    public bool $isBinary;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return self
     */
    public function analyze()
    {
        $this->stream->rewind();
        $hash = hash_init('md5');
        $size = 0;
        $isBinary = false;

        while(!$this->stream->eof()) {
            $content = $this->stream->read(1024);
            $size += strlen($content);
            $isBinary = $this->isBinary || is_binary($content);
            hash_update($hash, $content);
        }

        $this->hash = hash_final($hash);
        $this->size = $size;
        $this->isBinary = $isBinary;

        return $this;
    }
}