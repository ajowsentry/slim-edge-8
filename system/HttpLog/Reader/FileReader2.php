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
     * @param string|int $fromDate Date string
     * @param int $rowOffset Row offset
     * @return Generator<int,string,null,void>
     */
    public function query(string|int $fromDate): Generator
    {
        $timestamp = is_int($fromDate) ? $fromDate : get_timestamp($fromDate);
        $indexPath = $this->findIndexFile($timestamp);

        if(false === $indexPath)
            return; // Date out of range

        $indexFile = fopen($indexPath, 'r');

        try {
            if(false === $indexFile)
                return;

            fseek($indexFile, 0, SEEK_END);

            $left = 0;
            $right = intdiv(ftell($indexFile), 18);

            $mid = -1;
            while($left <= $right) {
                $mid = intdiv(($left + $right), 2);
                fseek($indexFile, 18 * $mid);
                $bin = fread($indexFile, 8);
                if(strlen($bin) < 8)
                    break;

                [, $midTimestamp] = unpack('P', $bin);

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
            if(strlen($metadata) < 18)
                return;

            /**
             * @var int $startTs
             * @var int $page
             * @var int $offset
             */
            extract(unpack('PstartTs/vpage/Poffset', $metadata));
            $startTs /= 1000;
            $dateTime = new DateTime("@{$startTs}");
            foreach($this->iterateJson($dateTime, $page, $offset) as $json) {
                yield $json;
            }
        }
        finally {
            fclose($indexFile);
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
        $startPeriod = $dateTime->format('Ym');
        $startDate = $dateTime->format('Y-m-d');

        foreach($this->iteratePeriodFolder($startPeriod) as $folder) {
            if($startPeriod != basename($folder))
                $page = 0;

            foreach($this->iteratePageFile($folder, $startDate, $page) as $file) {
                if(substr(basename($file), 4, 10) != $startDate)
                    $offset = 0;

                try {
                    $logFile = create_stream($file, 'r');
                    if($offset > 0) {
                        $logFile->seek($offset);
                    }

                    foreach($this->lineIterator($logFile) as $line) {
                        yield $line;
                    }
                }
                finally {
                    $offset = 0;
                    if(isset($logFile))
                        $logFile->close();
                }
            }
        }
    }

    /**
     * @param string $startPeriod
     */
    private function iteratePeriodFolder($startPeriod)
    {
        $periodFound = false;
        $periodIterator = new DirectoryIterator($this->config->path);
        while(($periodFileInfo = $periodIterator->current())->valid()) {
            if($periodFileInfo->isDir() && !$periodFileInfo->isDot()) {
                if(!$periodFound && $periodFileInfo->getFilename() >= $startPeriod) {
                    $periodFound = true;
                }

                if($periodFound) {
                    yield $periodFileInfo->getRealPath();
                }
            }

            $periodIterator->next();
        }
    }

    /**
     * @param string $periodFolder
     * @param string $dateString
     * @param int $page
     */
    private function iteratePageFile($periodFolder, $dateString, $page)
    {
        $pageFound = false;
        $startPage = "log_{$dateString}_" . str_pad_left(strval($page), 4, '0') . '.log';
        $pageIterator = new DirectoryIterator($periodFolder);
        while(($pageFileInfo = $pageIterator->current())->valid()) {
            if($pageFileInfo->isFile() && $pageFileInfo->getExtension() == 'log') {
                if(!$pageFound && $pageFileInfo->getFilename() >= $startPage) {
                    $pageFound = true;
                }

                if($pageFound) {
                    yield $pageFileInfo->getRealPath();
                }
            }

            $pageIterator->next();
        }
    }

    /**
     * @param int $timestamp
     * @return string|false
     */
    private function findIndexFile(int $timestamp): string|false
    {
        $indexFiles = [];
        foreach(new DirectoryIterator($this->config->path) as $fileInfo) {
            if(!$fileInfo->isDot() && $fileInfo->isDir() && is_numeric($fileInfo->getFilename()) && strlen($fileInfo->getFilename()) == 6) {
                array_push($indexFiles, $fileInfo->getRealPath() . '/log.idx');
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
            // echo 'midTimestamp: ' . $midTimestamp . PHP_EOL;
            // echo 'left : ' . $left . PHP_EOL;
            // echo 'right: ' . $right . PHP_EOL;
            // echo '------------------------------' . PHP_EOL;
            fclose($midFile);

            if($midTimestamp == $timestamp) {
                return $filePath;
            }

            if($midTimestamp < $timestamp) {
                if($mid < count($indexFiles) - 1) {
                    $nextFilePath = $indexFiles[$mid + 1];
                    $nextFile = fopen($nextFilePath, 'r');
                    [, $nextTimestamp] = unpack('P', fread($nextFile, 8));
                    // echo 'nextTimestamp: ' . $nextTimestamp . PHP_EOL;
                    // echo '------------------------------' . PHP_EOL;
                    fclose($nextFile);
                    if($timestamp < $nextTimestamp)
                        return $nextFilePath;
                }

                $left = $mid + 1;
            }

            elseif($midTimestamp > $timestamp) {
                if($mid > 0) {
                    $prevFilePath = $indexFiles[$mid - 1];
                    $prevFile = fopen($prevFilePath, 'r');
                    [, $prevTimestamp] = unpack('P', fread($prevFile, 8));
                    // echo 'prevTimestamp: ' . $prevTimestamp . PHP_EOL;
                    // echo '------------------------------' . PHP_EOL;
                    fclose($prevFile);
                    if($timestamp > $prevTimestamp)
                        return $prevFilePath;
                }

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