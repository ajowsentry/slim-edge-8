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
    public function query(string|int $fromDate, $rowOffset = 0): Generator
    {
        $timestamp = is_int($fromDate) ? $fromDate : get_timestamp($fromDate);
        $indexPath = $this->findIndexFile($timestamp);
        if(false === $indexPath)
            return; // Date out of range

        $indexFile = fopen($indexPath, 'r');
        if(false === $indexFile)
            return;

        fseek($indexFile, 0, SEEK_END);

        $left = 0;
        $right = intdiv(ftell($indexFile), 18);

        $mid = -1;
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

        /**
         * @var int $startTs
         * @var int $page
         * @var int $offset
         */
        extract(unpack('PstartTs/vpage/Poffset', $metadata));
        $startTs /= 1000;
        $dateTime = new DateTime("@{$startTs}");
        foreach($this->iterateJson2($dateTime, $page, $offset) as $json) {
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
    public function iterateJson2(DateTime $dateTime, int $page = 0, int $offset = 0): Generator
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

        // Find nearest period folder
        $periodFound = false;
        $periodIterator = new DirectoryIterator($this->config->path);
        while(($periodFileInfo = $periodIterator->current())->valid()) {
            if($periodFileInfo->isDir() && !$periodFileInfo->isDot() && $periodFileInfo->getFilename() == $startPeriod) {
                $periodFound = true;
                break;
            }

            $periodIterator->next();
        }

        if(!$periodFound) {
            $page = 0;
        }
        
        $startPage = str_pad_left(strval($page), 4, '0');
        $pageIterator = new DirectoryIterator($periodFileInfo->getRealPath());

        // Find nearest page file
        $pageFound = false;
        while(($pageFileInfo = $pageIterator->current())->valid()) {
            if($pageFileInfo->isFile() && $pageFileInfo->getExtension() == 'log') {
                $page = substr($pageFileInfo->getFilename(), -8, 4);
                if($page == $startPage) {
                    $pageFound = true;
                    break;
                }
            }

            $pageIterator->next();
        }

        if(!$pageFound) {
            $offset = 0;
        }

        while(($periodFileInfo = $periodIterator->current())->valid()) {
            // if($periodFileInfo->getFilename() == $startPeriod) {
            //     break;
            // }

            $pageIterator = new DirectoryIterator($periodFileInfo->getRealPath());
            $pageStart = false;
            while(($pageFileInfo = $pageIterator->current())->valid()) {
                if(!$pageFileInfo->isFile() || $pageFileInfo->getExtension() !== 'log') {
                    $pageIterator->next();
                    continue;
                }

                if(!$pageStart) {
                    $currentPage = intval(substr($pageFileInfo->getFilename(), -8, 4));
                    $currentDate = substr($pageFileInfo->getFilename(), 4, 10);
                    if($currentDate < $startDate || ($currentDate == $startDate && $currentPage < $page)) {
                        $pageIterator->next();
                        continue;
                    }
                }

                $pageStart = true;
                try {
                    $logFile = create_stream($pageFileInfo->getPath(), 'r');
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

                $pageIterator->next();
            }

            $periodIterator->next();
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