<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Reader;

use DateTime;
use Generator;
use Psr\Http\Message\StreamInterface;
use SlimEdge\HttpLog\Config;

class FileReader
{
    /**
     * @var int $page
     */
    private int $page = 0;

    /**
     * @var int $pointerOffset
     */
    private int $pointerOffset = 0;

    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPointerOffset(): int
    {
        return $this->pointerOffset;
    }

    /**
     * @param int $value
     * @return static
     */
    public function setPage(int $value): static
    {
        $this->page = $value;
        return $this;
    }

    /**
     * @param int $value
     * @return static
     */
    public function setPointerOffset(int $value): static
    {
        $this->pointerOffset = $value;
        return $this;
    }

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $date
     * @param string $fromTime
     * @param string $toTime
     * @return Generator<string>
     */
    public function streamLog(string $date, string $fromTime = '00:00:00', string $toTime = '24:00:00'): Generator
    {
        $dateTime = new DateTime($date);
        $fromTs = strtotime("{$date} {$fromTime}");
        $toTs = strtotime("{$date} {$toTime}");
        
        foreach($this->getFilePaths($dateTime, $this->page) as [$logPath, $lastModified, $page]) {
            if($fromTs > $lastModified)
                continue;

            try {
                $logFile = create_stream($logPath, 'r');
                if($this->page == $page && $this->pointerOffset > 0)
                    $logFile->seek($this->pointerOffset);

                $this->page = $page;

                foreach($this->lineIterator($logFile) as $line) {
                    $offset = strpos($line, '"datetime"');
                    $time = substr($line, $offset + 23, 8);

                    if($time < $fromTime)
                        continue;

                    elseif($time > $toTime)
                        break;

                    yield $line;
                }
            }
            finally {
                if(isset($logFile))
                    $logFile->close();
            }

            if($toTs < $lastModified)
                break;
        }
    }

    /**
     * @param DateTime $dateTime
     * @param int $startPage
     * @return Generator<list<string|int>>
     */
    private function getFilePaths(DateTime $dateTime, int $startPage = 0): Generator
    {
        while(false !== ($filePath = $this->getFilePath($dateTime, $startPage++))) {
            yield $filePath;
        }
    }

    /**
     * @param DateTime $dateTime
     * @param int $page
     * @return false|list<string|int>
     */
    private function getFilePath(DateTime $dateTime, int $page): false|array
    {
        $path = $this->config->path
            . '/' . $dateTime->format('Ym')
            . '/log_' . $dateTime->format('Y-m-d')
            . '_' . str_pad_left(strval($page), 4, '0')
            . '.log';

        if(!file_exists($path))
            return false;

        return [$path, filemtime($path), $page];
    }

    /**
     * @param StreamInterface $stream
     * @return Generator<string>
     */
    private function lineIterator(StreamInterface $stream): Generator
    {
        $remainder = '';

        try {
            while(!$stream->eof() && false !== ($content = $stream->read(1024))) {
                $remainder .= $content;

                $lines = explode(PHP_EOL, $remainder);
                $count = count($lines);

                for($i = 0; $i < $count - 1; $i++)
                    yield $lines[$i];
                
                $remainder = $lines[$count - 1];
            }
        }
        finally {
            $this->pointerOffset = $stream->tell() - strlen($remainder);
        }

        if(strlen($remainder) > 0)
            yield $remainder;

        $this->pointerOffset = $stream->tell();
    }
}