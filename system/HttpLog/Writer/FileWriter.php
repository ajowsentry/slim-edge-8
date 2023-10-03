<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Writer;

use DateTime;
use DirectoryIterator;
use Exception;
use DateTimeZone;

class FileWriter extends BaseWriter
{
    /**
     * {@inheritdoc}
     */
    public function writeLog(array $logData): bool
    {
        try {
            $dateTime = new DateTime('now', new DateTimeZone('UTC'));
            $directory = "{$this->config->path}/" . $dateTime->format('Ym');

            if(!file_exists($directory))
                mkdir($directory, 0777, true);

            $date = $dateTime->format('Y-m-d');
            $filePath = "{$directory}/log_{$date}.log";
            $maxFileSize = $this->config->maxFileSize;
            $number = 0;
            do {
                $numPad = str_pad_left(strval($number), 4, '0');
                $filePath = "{$directory}/log_{$date}_{$numPad}.log";
            }
            /** @phpstan-ignore-next-line */
            while(!is_null($maxFileSize) && file_exists($filePath) && filesize($filePath) > $maxFileSize && ++$number);

            $payloadStream = create_stream($filePath, 'a');
            $startPosition = $payloadStream->tell();
            $payloadStream->write(json_encode($logData, JSON_UNESCAPED_SLASHES) . ',' . PHP_EOL);

            $filePath = "{$directory}/log.idx";
            $indexData = pack('PvP', $logData['timestamp'], $number, $startPosition);            
            $indexStream = create_stream($filePath, 'a');
            $indexStream->write($indexData);

            return true;
        }
        catch(Exception) {
            return false;
        }
        finally {
            if(isset($payloadStream))
                $payloadStream->close();

            if(isset($indexStream))
                $indexStream->close();
        }
    }

    public function deleteOldLogs(): void {
        if(is_null($this->config->maxDays))
            return;
        
        $maxDays = $this->config->maxDays;
        $date = new DateTime("-{$maxDays} days");
        $period = $date->format('Ym');
        $baseDir = $this->config->path;

        foreach(new DirectoryIterator($baseDir) as $dir) {
            if($dir->isDir() && !$dir->isDot()) {
                if($dir->getFilename() < $period)
                    delete_dir($dir->getRealPath());

                elseif($dir->getFilename() > $period)
                    break;
            }
            elseif(!$dir->isDir())
                break;
        }

        foreach(glob($baseDir . '/' . $period . '/log_*.log') as $log) {
            $logDate = substr(basename($log), 4, 10);
            if($logDate <= $date)
                @unlink($log);
        }
    }
}
