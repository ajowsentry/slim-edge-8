<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Reader;

use DateTime;
use Generator;
use DateInterval;
use DateTimeZone;
use DirectoryIterator;
use SlimEdge\HttpLog\Config;
use Psr\Http\Message\StreamInterface;

class FileReader2
{
    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * @var int $page
     */
    private int $page = 0;

    /**
     * @var int $pointerOffset
     */
    private int $pointerOffset = 0;

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
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $fromDate Date string
     * @return Generator<int,string,null,void>
     */
    public function query(string $fromDate): Generator
    {
        $timestamp = get_timestamp($fromDate);
        $indexPath = $this->findIndexFile($timestamp);
        if(false === $indexPath) {
            return; // Date out of range
        }

        $indexFile = fopen($indexPath, 'r');
        fseek($indexFile, 0, SEEK_END);

        $left = 0;
        $right = intdiv(ftell($indexFile), 18);

        while($left <= $right) {
            $mid = intdiv(($left + $right), 2);
            fseek($indexFile, 18 * $mid);
            [, $midTimestamp] = unpack('P', fread($indexFile, 8));

            if($midTimestamp == $timestamp) {
                break;
            }
            elseif($midTimestamp < $timestamp) {
                $left = $mid + 1;
            }
            else {
                $right = $mid - 1;
            }
        }

        fseek($indexFile, 18 * $mid);
        $metadata = fread($indexFile, 18);
        [, $startTs, $page, $offset] = unpack('P/v/P', $metadata);

        $dateTime = new DateTime("@{$startTs}");
        foreach($this->iterateJson($dateTime, $page, $offset) as $json) {
            yield $json;
        }
    }

    /**
     * @param DateTime $dateTime
     * @param int $page
     * @param int $offset
     * 
     * @return Generator<int,string,null,void>
     */
    public function iterateJson(DateTime $dateTime, int $page = 0, int $offset = 0): Generator
    {
        $dateTime = $dateTime->setTimezone(new DateTimeZone('UTC'));
        $pageFound = false;
        $dateFound = false;
        while(true) {
            $path = $this->config->path
                . '/' . $dateTime->format('Ym')
                . '/log_' . $dateTime->format('Y-m-d')
                . '_' . str_pad_left(strval($page), 4, '0')
                . '.log';

            if(!file_exists($path)) {
                if($pageFound) {
                    $page++;
                    $pageFound = false;
                }
                elseif($dateFound) {
                   $dateTime = $dateTime->add(new DateInterval('P1D'));
                   $page = 0;
                   $dateFound = false; 
                }
                else {
                    break;
                }

                continue;
            }

            $pageFound = true;
            $dateFound = true;
            $this->page = $page;

            try {
                $logFile = create_stream($path, 'r');
                if($offset > 0) {
                    $logFile->seek($offset);
                }

                foreach($this->lineIterator($logFile) as $line) {
                    yield $line;
                }
            }
            finally {
                if(isset($logFile))
                    $logFile->close();
            }

            $offset = 0;
        }
    }

    private function findIndexFile(int $timestamp): string|false
    {
        $indexFiles = [];
        foreach(new DirectoryIterator($this->config->path) as $fileInfo) {
            if($fileInfo->isDir() && !$fileInfo->isDot() && is_numeric($fileInfo->getFilename()) && strlen($fileInfo->getFilename()) == 6) {
                array_push($indexFiles, $fileInfo->getPath() . '/log.idx');
            }
        }

        if(count($indexFiles) === 0) {
            return false;
        }

        $left = 0;
        $right = count($indexFiles) - 1;

        while($left <= $right) {
            $mid = intdiv(($left + $right), 2);

            $filePath = $indexFiles[$mid];
            $midFile = fopen($filePath, 'r');
            [, $midTimestamp] = unpack('P', fread($midFile, 8));
            fclose($midFile);

            if($midTimestamp == $timestamp) {
                return $filePath;
            }
            elseif($midTimestamp < $timestamp) {
                $left = $mid + 1;
            }
            else {
                $right = $mid - 1;
            }
        }

        if($mid > 0) {
            $prevNearestFilePath = $indexFiles[$mid - 1];
            $prevFile = fopen($prevNearestFilePath, 'r');
            fseek($prevFile, -18, SEEK_SET);
            [, $prevNearestTimestamp] = unpack('P', fread($prevFile, 8));
            fclose($prevFile);

            // Filepath is previous nearest to searched timestamp
            if($prevNearestTimestamp < $timestamp) {
                return $prevNearestFilePath;
            }
        }

        // Filepath is next nearest to searched timestamp
        if($timestamp < $midTimestamp) {
            return $filePath;
        }

        return false;
    }

    /**
     * @param StreamInterface $stream
     * @return Generator<int,string,null,void>
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