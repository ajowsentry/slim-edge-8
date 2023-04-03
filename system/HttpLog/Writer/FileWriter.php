<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Writer;

use DateTime;
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
            while(!is_null($maxFileSize) && file_exists($filePath) && filesize($filePath) > $maxFileSize && ++$number);

            $payloadStream = create_stream($filePath, 'a');
            $startPosition = $payloadStream->tell();
            $payloadStream->write(json_encode($logData) . ',' . PHP_EOL);

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
}
