<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Writer;

use Exception;

class FileWriter extends BaseWriter
{
    /**
     * {@inheritdoc}
     */
    public function writeLog(array $logData): bool
    {
        try {
            $json = json_encode($logData);
            $date = date('Y-m-d');
            $directory = "{$this->config->path}/" . date('Ym');
            if(!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filePath = "{$directory}/log_{$date}.log";
            $maxFileSize = $this->config->maxFileSize;
            $number = 0;
            do {
                $numPad = str_pad_left(strval($number), 4, '0');
                $filePath = "{$directory}/log_{$date}_{$numPad}.log";
            }
            while(!is_null($maxFileSize) && file_exists($filePath) && filesize($filePath) > $maxFileSize && $number++);

            try {
                $payloadStream = create_stream($filePath, 'a');
                $payloadStream->write($json . ',' . PHP_EOL);
                $lastPosition = $payloadStream->tell();
            }
            finally {
                if(isset($payloadStream))
                    $payloadStream->close();
            }

            try {
                $filePath = "{$directory}/log.idx";
                $indexData = pack('PvP', $logData['timestamp'], $number, $lastPosition);            
                $indexStream = create_stream($filePath, 'a');
                $indexStream->write($indexData);
            }
            finally {
                if(isset($indexStream))
                    $indexStream->close();
            }

            return true;
        }
        catch(Exception) {
            return false;
        }
    }
}
