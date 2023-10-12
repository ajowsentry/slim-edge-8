<?php
declare(strict_types=1);

namespace App\Controllers\Storage\Create;

use Psr\Http\Message\StreamInterface;
use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     * @param StreamInterface $stream
     */
    public function create($formData, $stream)
    {
        $path = Paths::Storage . '/' . ltrim($formData->path, '/');
        if(file_exists($path)) {
            if(is_file($path) && !$formData->append && !$formData->overwrite)
                return false;

            if(is_dir($path) && $formData->type == 'dir')
                return ['path' => $formData->path, 'type' => 'dir', 'is_new' => false];
            
            if(is_dir($path))
                return false;
        }

        if($formData->type == 'dir') {
            mkdir($path, 0777, true);
            return ['path' => $formData->path, 'type' => 'dir', 'is_new' => true];
        }

        if(!file_exists($dirpath = dirname($path))) {
            mkdir($dirpath, 0777, true);
        }

        if($formData->overwrite && file_exists($path)) {
            unlink($path);
        }

        $isNewFile = !file_exists($path);

        $handle = fopen($path, $formData->append ? 'a+' : 'w+');
        if($formData->offset && file_exists($path)) {
            fseek($handle, $formData->offset);
        }

        $stream->rewind();
        $body = $stream->detach();
        stream_copy_to_stream($body, $handle);

        return [
            'path' => $formData->path,
            'type' => 'file',
            'is_new' => $isNewFile,
            'written' => ftell($body),
            'size' => ftell($handle),
        ];

        // if($formData->append)
        
        // if(false === $path)
        //     return false;

        // if(is_file($path)) {
        //     $size = filesize($path);
        //     unlink($path);
            
        //     return [['path' => $formData->path, 'size' => $size]];
        // }
        // elseif(is_dir($path)) {
        //     $result = [];

        //     foreach(dir_files($path) as $file) {
        //         array_push($result, [
        //             'path' => substr($file, strlen(Paths::Storage)),
        //             'size' => filesize($path),
        //         ]);
        //     }

        //     delete_dir($path);
        //     return $result;
        // }
    }
}